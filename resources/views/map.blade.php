@extends('layouts.app')

@section('title', 'Map - CoffeePahoman (Dark)')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height:70vh; }
    .leaflet-control, .leaflet-popup-content-wrapper {
        background:#1a1a1a !important;
        color:#fef3c7 !important;
        border:1px solid #78350f !important;
    }
    .map-icon { color:#b45309; font-size:22px; text-shadow: 0 1px 2px rgba(0,0,0,0.4); }
    .map-icon.active { color:#fbbf24; filter: drop-shadow(0 0 8px rgba(251,191,36,0.8)); }
    .list-item.active { border-color:#fbbf24; background:rgba(120,53,15,0.35); }
</style>
@endpush

@section('content')
  <div class="flex justify-between items-center mb-6" data-aos="fade-down">
    <h1 class="text-2xl font-semibold text-amber-50">Map / WebGIS</h1>
    <div class="flex gap-3">
      <button id="downloadBtn" class="px-3 py-2 bg-amber-600 text-black rounded text-sm">Download GeoJSON</button>
      <input id="searchInput" class="px-3 py-2 rounded text-sm bg-[#1a1a1a] border border-amber-800 text-amber-100" placeholder="Cari nama...">
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <section class="lg:col-span-3" data-aos="fade-right">
      <div id="map" class="rounded-lg shadow border border-amber-900/50"></div>
      <div class="mt-3 flex gap-3">
        <button id="locateBtn" class="px-3 py-2 bg-amber-600 text-black rounded">Locate Me</button>
      </div>
    </section>

    <aside class="card p-4 rounded" data-aos="fade-left" data-aos-delay="100">
      <h3 class="font-semibold text-amber-100">Listing Coffee Shop</h3>
      <div id="list" class="mt-3 space-y-2 text-sm"></div>
    </aside>
  </div>
@endsection

@push('scripts')
<script>
const shopsUrl = "{{ route('api.geojson', ['type' => 'shops'], false) }}";
const boundaryUrl = "{{ route('api.geojson', ['type' => 'boundary'], false) }}";

const map = L.map('map', { closePopupOnClick:false }).setView([-5.4295, 105.2625], 15);

const darkTiles = L.tileLayer(
  "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
  { attribution:'©OpenStreetMap, ©CARTO', maxZoom:19 }
).addTo(map);

const satTiles = L.tileLayer(
  "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
  { attribution:"Esri", maxZoom:19 }
);

const baseMaps = { "Dark Map": darkTiles, "Satellite": satTiles };
const shopsLayer = L.layerGroup().addTo(map);
const boundaryLayer = L.geoJSON(null, { style:{ color:"#f59e0b", weight:2, fillOpacity:0.05 } }).addTo(map);

const icon = L.divIcon({
  className: "",
  html:`<i class="fa-solid fa-mug-saucer map-icon"></i>`,
  iconSize:[22,22],
  iconAnchor:[11,22]
});
const iconActive = L.divIcon({
  className: "",
  html:`<i class="fa-solid fa-mug-saucer map-icon active"></i>`,
  iconSize:[26,26],
  iconAnchor:[13,26]
});

let shopsJSON = null;
let allFeatures = [];

const listEl = document.getElementById("list");
const searchInput = document.getElementById("searchInput");
const markerByKey = new Map();
const listItemByKey = new Map();
let activeKey = null;

function featureKey(f){
  return `${f.properties?.NAMA || ''}|${(f.geometry?.coordinates || []).join(',')}`;
}

function setActive(key, { center=true, openPopup=true, moveToTop=false } = {}){
  if(activeKey && markerByKey.has(activeKey)){
    markerByKey.get(activeKey).setIcon(icon);
  }
  if(activeKey && listItemByKey.has(activeKey)){
    listItemByKey.get(activeKey).classList.remove("active");
  }
  activeKey = key;
  if(!key) return;

  const marker = markerByKey.get(key);
  const item = listItemByKey.get(key);
  if(item){
    item.classList.add("active");
    if(moveToTop && listEl.firstChild !== item){
      listEl.prepend(item);
      if(typeof listEl.scrollTo === "function") listEl.scrollTo({top:0, behavior:"smooth"});
    }
  }
  if(marker){
    marker.setIcon(iconActive);
    if(center){
      const ll = marker.getLatLng();
      map.setView(ll, 17);
    }
    if(openPopup) marker.openPopup();
  }
}

const markerOptions = {
  pointToLayer:(f,latlng)=>{
    const key = featureKey(f);
    const marker = L.marker(latlng,{icon});
    markerByKey.set(key, marker);
    marker.on("click", ev=>{
      ev.originalEvent?.stopPropagation?.();
      setActive(key,{center:false, openPopup:true, moveToTop:true});
    });
    return marker;
  },
  onEachFeature:(f,l)=>{
    const p = f.properties || {};
    const detailUrl = "{{ route('detail') }}#" + encodeURIComponent(p.NAMA || '');
    l.bindPopup(
      `<div>
        <strong>${p.NAMA || 'Tanpa nama'}</strong><br>
        Buka: ${p.WAKTU_BUKA || '-'} - ${p.WKT_TUTUP || '-'}<br>
        Harga rata-rata: Rp ${p.HARGA || '-'}<br>
        Rating: ${p.RATING || '-'}<br>
        <a href="${detailUrl}" class="text-amber-300">Lihat detail</a>
      </div>`
    );
  }
};

function renderMarkers(features){
  shopsLayer.clearLayers();
  markerByKey.clear();
  if(!features.length) return;
  L.geoJSON({ type:"FeatureCollection", features }, markerOptions).addTo(shopsLayer);
}

function renderList(features){
  listEl.innerHTML = "";
  listItemByKey.clear();
  if(!features.length){
    listEl.innerHTML = "<div class='text-amber-400 text-sm'>Tidak ada hasil.</div>";
    return;
  }
  features.forEach(ft=>{
    const key = featureKey(ft);
    const div = document.createElement("div");
    div.className="list-item p-2 rounded border border-amber-800 cursor-pointer hover:bg-amber-900/40";
    div.innerHTML = `<div>${ft.properties.NAMA || 'Tanpa nama'}</div><div class='text-xs text-amber-400'>Buka ${ft.properties.WAKTU_BUKA || '-'} - ${ft.properties.WKT_TUTUP || '-'}</div>`;
    div.onclick = ()=>{
      setActive(key,{center:true, openPopup:true});
    };
    listItemByKey.set(key, div);
    listEl.appendChild(div);
  });
}

function focusToFeatures(features){
  if(!features.length) return;
  if(features.length === 1){
    const c = features[0].geometry.coordinates;
    map.setView([c[1],c[0]],17);
    return;
  }
  const bounds = L.latLngBounds(features.map(f=>[f.geometry.coordinates[1], f.geometry.coordinates[0]]));
  map.fitBounds(bounds, { maxZoom:17 });
}

function handleSearch(){
  if(!shopsJSON) return;
  const q = searchInput.value.trim().toLowerCase();
  const filtered = q ? allFeatures.filter(f=>(f.properties.NAMA || "").toLowerCase().includes(q)) : allFeatures;
  renderList(filtered);
  renderMarkers(filtered);
  focusToFeatures(filtered);
  setActive(filtered.length ? featureKey(filtered[0]) : null, { center:false, openPopup:true });
}

fetch(shopsUrl)
  .then(r=>r.json())
  .then(data=>{
    shopsJSON = data;
    allFeatures = data.features || [];
    renderList(allFeatures);
    renderMarkers(allFeatures);
    focusToFeatures(allFeatures);
    setActive(allFeatures.length ? featureKey(allFeatures[0]) : null, { center:false, openPopup:true });
  });

fetch(boundaryUrl)
  .then(r=>r.json())
  .then(data=>{
    boundaryLayer.addData(data);
  });

L.control.layers(baseMaps, { "Coffee Shop": shopsLayer, "Batas Wilayah": boundaryLayer }).addTo(map);

document.getElementById("downloadBtn").onclick = ()=>{
  if(!shopsJSON) return;
  const url = URL.createObjectURL(new Blob([JSON.stringify(shopsJSON)],{type:"application/json"}));
  const a = document.createElement("a");
  a.href=url; a.download="coffeeshops.geojson"; a.click();
};

document.getElementById("locateBtn").onclick = ()=>{
  map.locate({setView:true});
  map.on("locationfound", e=>{
    L.circle(e.latlng,{radius:20,color:"#fbbf24"}).addTo(map);
  });
};

searchInput.addEventListener("input", handleSearch);
</script>
@endpush
