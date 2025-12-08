@extends('layouts.app')

@section('title', 'Detail Coffee Shop - CoffeePahoman')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .card{background:#0f0f0f;border:1px solid rgba(120,53,15,0.25)}
    #detailMap{height:300px;border-radius:10px}
    .gallery img{height:140px;width:100%;object-fit:cover;border-radius:10px}
    .map-icon { color:#b45309; font-size:20px; text-shadow:0 1px 2px rgba(0,0,0,0.4); }
    .badge { background: rgba(251,191,36,0.12); border:1px solid rgba(251,191,36,0.4); }
</style>
@endpush

@section('content')
<header class="mb-6" data-aos="fade-down">
  <div class="flex items-center justify-between">
    <a href="{{ route('listing') }}" class="text-amber-200 text-lg">← Back</a>
    <h1 class="text-2xl font-semibold text-amber-50">Detail Coffee Shop</h1>
    <a href="{{ route('map') }}" class="text-amber-200">Map</a>
  </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <section class="lg:col-span-2 card p-6 rounded" data-aos="fade-right">
    <div id="title" class="flex items-start justify-between">
      <div>
        <h2 id="shopName" class="text-2xl font-bold text-amber-50">-</h2>
        <div id="shopAddr" class="text-amber-300 text-sm mt-1">-</div>
      </div>
      <div class="text-right">
        <div class="text-sm text-amber-300">Rating</div>
        <div id="shopRating" class="text-2xl font-bold text-white">-</div>
      </div>
    </div>

    <div id="gallery" class="mt-6 gallery grid grid-cols-1 md:grid-cols-3 gap-3"></div>

    <div class="mt-6">
      <h4 class="text-amber-200 font-semibold">Jam Operasional</h4>
      <p id="shopHours" class="text-amber-200 text-sm mt-2">-</p>
    </div>

    <div class="mt-6">
      <h4 class="text-amber-200 font-semibold">Info Harga</h4>
      <div id="shopPrice" class="text-amber-200 text-sm mt-2">-</div>
    </div>
  </section>

  <aside class="card p-4 rounded" data-aos="fade-left" data-aos-delay="150">
    <div id="detailMap"></div>
    <div class="mt-4 flex flex-col gap-3">
      <a id="directions" class="px-3 py-2 bg-amber-600 text-black rounded text-center" href="#" target="_blank">Buka di Google Maps</a>
      <button id="shareBtn" class="px-3 py-2 border border-amber-800 rounded text-amber-200">Share Link</button>
    </div>
  </aside>
</div>
@endsection

@push('scripts')
<script>
const shopsUrl = "{{ route('api.geojson', ['type' => 'shops'], false) }}";
// Tambahkan mapping foto berdasarkan ID (ambil dari properties.id pada GeoJSON)
const photoMap = {}; // isi sendiri: { 1: ['/images/coffee/1-1.jpg','/images/coffee/1-2.jpg'] }

function getShopId(){
  if(location.hash && location.hash.length>1) return decodeURIComponent(location.hash.slice(1));
  const params = new URLSearchParams(location.search);
  return params.get('name') ? params.get('name') : null;
}

function normalize(feature){
  return {
    id: feature.properties.id ?? feature.id ?? null,
    name: feature.properties.NAMA || 'Tanpa nama',
    rating: feature.properties.RATING || '-',
    buka: feature.properties.WAKTU_BUKA || '-',
    tutup: feature.properties.WKT_TUTUP || '-',
    harga: feature.properties.HARGA || '-',
    coords: feature.geometry.coordinates,
    address: 'Pahoman, Bandar Lampung'
  };
}

function renderShop(s){
  document.getElementById('shopName').innerText = s.name;
  document.getElementById('shopAddr').innerText = s.address;
  document.getElementById('shopRating').innerText = s.rating;
  document.getElementById('shopHours').innerText = `${s.buka} - ${s.tutup}`;
  document.getElementById('shopPrice').innerText = `Rata-rata: Rp ${s.harga}`;

  const gallery = document.getElementById('gallery');
  gallery.innerHTML = '';
  const coords = s.coords;
  const fallbackId = s.id || 1;
  const images = photoMap[s.id] || [
    `/images/coffee/${fallbackId}-1.jpg`,
    `/images/coffee/${fallbackId}-2.jpg`,
    `/images/coffee/${fallbackId}-3.jpg`,
  ];
  images.forEach(src=>{
    const img = document.createElement('img');
    img.src = src;
    img.alt = s.name;
    img.loading = 'lazy';
    img.className = 'shadow';
    gallery.appendChild(img);
  });

  const map = L.map('detailMap', {zoomControl:false}).setView([coords[1], coords[0]], 17);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{maxZoom:19, attribution:'©OpenStreetMap, ©CARTO'}).addTo(map);
  const icon = L.divIcon({ className:"", html:'<i class="fa-solid fa-mug-saucer map-icon"></i>', iconSize:[20,20], iconAnchor:[10,20] });
  L.marker([coords[1], coords[0]], {icon}).addTo(map).bindPopup(s.name).openPopup();

  document.getElementById('directions').href = `https://www.google.com/maps/search/?api=1&query=${coords[1]},${coords[0]}`;
  document.getElementById('shareBtn').addEventListener('click', ()=>{
    const url = location.origin + location.pathname + '#' + encodeURIComponent(s.name);
    navigator.clipboard.writeText(url).then(()=> alert('Link copied to clipboard'));
  });
}

fetch(shopsUrl).then(r=>r.json()).then(gj=>{
  const features = gj.features.map(normalize);
  const id = getShopId();
  let shop = null;
  if(id){
    shop = features.find(s=> s.name.toLowerCase() === id.toLowerCase()) || features.find(s=> s.name.toLowerCase().includes(id.toLowerCase()));
  }
  if(!shop) shop = features[0];
  if(shop) renderShop(shop);
});
</script>
@endpush
