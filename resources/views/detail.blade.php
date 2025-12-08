<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Detail Coffee Shop — CoffeePahoman</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <style>
    body{background:#0b0b0b;color:#fef3c7}
    .card{background:#0f0f0f;border:1px solid rgba(120,53,15,0.25)}
    #detailMap{height:300px;border-radius:10px}
    .gallery img{height:120px;width:auto;border-radius:8px}
  </style>
</head>
<body class="antialiased">

<header class="p-4 bg-[#0b0b0b] border-b border-amber-900/40">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <a href="listing.html" class="text-amber-200 text-lg">← Back</a>
    <h1 class="text-amber-50 font-semibold">Detail Coffee Shop</h1>
    <a href="map_dark.html" class="text-amber-200">Map</a>
  </div>
</header>

<main class="max-w-6xl mx-auto p-6">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Details -->
    <section class="lg:col-span-2 card p-6 rounded">
      <div id="title" class="flex items-start justify-between">
        <div>
          <h2 id="shopName" class="text-2xl font-bold text-amber-50">—</h2>
          <div id="shopAddr" class="text-amber-300 text-sm mt-1">—</div>
        </div>
        <div class="text-right">
          <div class="text-sm text-amber-300">Rating</div>
          <div id="shopRating" class="text-2xl font-bold text-white">—</div>
        </div>
      </div>

      <div class="mt-6 gallery flex gap-3 overflow-auto">
        <!-- placeholder images -->
        <img src="https://picsum.photos/seed/1/400/300" alt="img1">
        <img src="https://picsum.photos/seed/2/400/300" alt="img2">
        <img src="https://picsum.photos/seed/3/400/300" alt="img3">
      </div>

      <div class="mt-6">
        <h4 class="text-amber-200 font-semibold">Description</h4>
        <p id="shopDesc" class="text-amber-200 text-sm mt-2">—</p>
      </div>

      <div class="mt-6">
        <h4 class="text-amber-200 font-semibold">Open Hours</h4>
        <div id="shopHours" class="text-amber-200 text-sm mt-2">Mon–Sun: 08:00–22:00</div>
      </div>

      <div class="mt-6">
        <h4 class="text-amber-200 font-semibold">Contact</h4>
        <div id="shopContact" class="text-amber-200 text-sm mt-2">Phone: — <br/>Instagram: —</div>
      </div>

      <div class="mt-6">
        <h4 class="text-amber-200 font-semibold">Reviews</h4>
        <div id="reviews" class="mt-2 space-y-3 text-sm">
          <div class="p-3 bg-[#0b0b0b] border border-amber-800 rounded">No reviews yet.</div>
        </div>
      </div>
    </section>

    <!-- Right: Map & actions -->
    <aside class="card p-4 rounded">
      <div id="detailMap"></div>
      <div class="mt-4 flex flex-col gap-3">
        <a id="directions" class="px-3 py-2 bg-amber-600 text-black rounded text-center" href="#" target="_blank">Buka di Google Maps</a>
        <button id="shareBtn" class="px-3 py-2 border border-amber-800 rounded text-amber-200">Share Link</button>
        <a id="reportBtn" class="px-3 py-2 border border-amber-800 rounded text-amber-200" href="#">Laporkan kesalahan</a>
      </div>
    </aside>
  </div>
</main>

<script>
// Read shop identifier from URL hash or query param (hash preferred: #Kopi%20Pahoman)
function getShopId(){
  if(location.hash && location.hash.length>1) return decodeURIComponent(location.hash.slice(1));
  const params = new URLSearchParams(location.search);
  return params.get('name') ? params.get('name') : null;
}

let shopsData = null;
fetch('shops.geojson').then(r=>r.json()).then(gj=>{
  shopsData = gj.features.map(f=>({name:f.properties.name, address:f.properties.address, rating:f.properties.rating, coords:f.geometry.coordinates, props:f.properties}));
  const id = getShopId();
  let shop = null;
  if(id){
    shop = shopsData.find(s=> s.name.toLowerCase() === id.toLowerCase()) || shopsData.find(s=> s.name.toLowerCase().includes(id.toLowerCase()));
  }
  if(!shop) shop = shopsData[0]; // fallback to first
  renderShop(shop);
}).catch(e=>{
  console.error(e);
  document.getElementById('shopName').innerText = 'Data tidak ditemukan';
});

function renderShop(s){
  document.getElementById('shopName').innerText = s.name || '-';
  document.getElementById('shopAddr').innerText = s.address || '-';
  document.getElementById('shopRating').innerText = s.rating || '-';
  document.getElementById('shopDesc').innerText = s.props.description || 'No description available.';
  document.getElementById('shopContact').innerHTML = `Phone: ${s.props.phone || '-'}<br>Instagram: ${s.props.instagram || '-'}`;

  // reviews (mock)
  const reviews = s.props.reviews || [
    {user:'Andi', text:'Kopi enak, suasana nyaman.', rating:4.5},
    {user:'Sari', text:'Tempatnya cozy, harga sedang.', rating:4.0}
  ];
  const rDiv = document.getElementById('reviews');
  rDiv.innerHTML='';
  reviews.forEach(rv=>{
    const el = document.createElement('div');
    el.className='p-3 bg-[#0b0b0b] border border-amber-800 rounded text-amber-200';
    el.innerHTML = `<div class="font-medium">${rv.user} <span class="text-xs text-amber-300">— ${rv.rating}</span></div><div class="text-sm mt-1">${rv.text}</div>`;
    rDiv.appendChild(el);
  });

  // map
  const coords = s.coords;
  const map = L.map('detailMap', {zoomControl:false}).setView([coords[1], coords[0]], 17);
  L.tileLayer('https://tile.jawg.io/jawg-dark/{z}/{x}/{y}.png?access-token=ggK0D5bH2lO0tNWcQeD7JqTLqH4FZy2E5bxzhHcEQn4p9P5V8r',{maxZoom:19}).addTo(map);
  L.marker([coords[1], coords[0]]).addTo(map).bindPopup(s.name).openPopup();

  // directions link (Google Maps)
  document.getElementById('directions').href = `https://www.google.com/maps/search/?api=1&query=${coords[1]},${coords[0]}`;

  // share link
  document.getElementById('shareBtn').addEventListener('click', ()=>{
    const url = location.origin + location.pathname + '#' + encodeURIComponent(s.name);
    navigator.clipboard.writeText(url).then(()=> alert('Link copied to clipboard'));
  });
}

</script>
</body>
</html>
