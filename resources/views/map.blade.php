<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Map — CoffeePahoman (Dark)</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = { darkMode: 'class' }
  </script>

  <style>
    body { background:#0b0b0b; color:#fef3c7 }
    #map { height:70vh; }
    .leaflet-control, .leaflet-popup-content-wrapper {
        background:#1a1a1a !important;
        color:#fef3c7 !important;
        border:1px solid #78350f !important;
    }
  </style>
</head>

<body class="dark">

<header class="p-4 bg-[#0b0b0b] border-b border-amber-900/50">
  <div class="max-w-6xl mx-auto flex justify-between items-center">
    <a href="landing_dark_final.html" class="text-amber-200 text-lg">← Back</a>

    <div class="flex gap-3">
      <button id="downloadBtn" class="px-3 py-2 bg-amber-600 text-black rounded text-sm">Download GeoJSON</button>
      <input id="searchInput" class="px-3 py-2 rounded text-sm bg-[#1a1a1a] border border-amber-800 text-amber-100" placeholder="Cari nama...">
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto p-6">
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <section class="lg:col-span-3">
      <div id="map" class="rounded-lg shadow border border-amber-900/50"></div>

      <div class="mt-3 flex gap-3">
        <button id="locateBtn" class="px-3 py-2 bg-amber-600 text-black rounded">Locate Me</button>
        <button id="toggleHeat" class="px-3 py-2 border border-amber-700 rounded text-amber-200">Toggle Heatmap</button>
      </div>
    </section>

    <aside class="bg-[#1a1a1a] border border-amber-900/50 p-4 rounded">
      <h3 class="font-semibold text-amber-100">Listing Coffee Shop</h3>
      <div id="list" class="mt-3 space-y-2 text-sm"></div>
    </aside>

  </div>
</main>

<script>
const map = L.map('map').setView([-5.4295, 105.2625], 15);

const darkTiles = L.tileLayer(
  "https://tile.jawg.io/jawg-dark/{z}/{x}/{y}.png?access-token=ggK0D5bH2lO0tNWcQeD7JqTLqH4FZy2E5bxzhHcEQn4p9P5V8r",
  { attribution:"Jawg Maps", maxZoom:19 }
).addTo(map);

const satTiles = L.tileLayer(
  "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
  { attribution:"Esri", maxZoom:19 }
);

const baseMaps = { "Dark Map": darkTiles, "Satellite": satTiles };

const shopsLayer = L.layerGroup().addTo(map);

const icon = L.divIcon({
  className: "", 
  html:`<svg width="20" height="20" stroke="#fbbf24" fill="none" stroke-width="1.5" 
  viewBox="0 0 24 24"><path d="M8 2h8v4H8z"/><path d="M3 6h12v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V6z"/></svg>`,
  iconSize:[20,20], iconAnchor:[10,20]
});

let shopsJSON = null;

fetch("shops.geojson")
  .then(r=>r.json())
  .then(data=>{
    shopsJSON = data;

    L.geoJSON(data, {
      pointToLayer:(f,lat)=>L.marker(lat,{icon}),
      onEachFeature:(f,l)=>{
        l.bindPopup(
          `<div>
            <strong>${f.properties.name}</strong><br>
            ${f.properties.address}<br>
            Rating: ${f.properties.rating}
          </div>`
        );
      }
    }).addTo(shopsLayer);

    const list = document.getElementById("list");
    data.features.forEach(ft=>{
      const div = document.createElement("div");
      div.className="p-2 rounded border border-amber-800 cursor-pointer hover:bg-amber-900/40";
      div.innerHTML = `<div>${ft.properties.name}</div><div class='text-xs text-amber-400'>${ft.properties.address}</div>`;
      div.onclick = ()=>{
        const c = ft.geometry.coordinates;
        map.setView([c[1],c[0]],17);
      };
      list.appendChild(div);
    });
  });

L.control.layers(baseMaps, {"Coffee Shop": shopsLayer}).addTo(map);

document.getElementById("downloadBtn").onclick = ()=>{
  if(!shopsJSON) return;
  const url = URL.createObjectURL(new Blob([JSON.stringify(shopsJSON)],{type:"application/json"}));
  const a = document.createElement("a");
  a.href=url; a.download="shops.geojson"; a.click();
};

document.getElementById("locateBtn").onclick = ()=>{
  map.locate({setView:true});
  map.on("locationfound", e=>{
    L.circle(e.latlng,{radius:20,color:"#fbbf24"}).addTo(map);
  });
};

document.getElementById("searchInput").oninput = e=>{
  const q = e.target.value.toLowerCase();
  if(!shopsJSON) return;
  const match = shopsJSON.features.find(f=>(f.properties.name||"").toLowerCase().includes(q));
  if(match){
    const c = match.geometry.coordinates;
    map.setView([c[1],c[0]],17);
  }
};

</script>

</body>
</html>
