<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Listing — CoffeePahoman</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <style>
    body{background:#0b0b0b;color:#fef3c7}
    .card { background: #111111; border: 1px solid rgba(120,53,15,0.3); }
    .modal-backdrop { background: rgba(3,3,3,0.6); }
    #miniMap { height:200px; }
  </style>
</head>
<body class="antialiased">

<header class="p-4 bg-[#0b0b0b] border-b border-amber-900/40">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <a href="landing_dark_final.html" class="text-amber-200 text-lg">← Back</a>
    <h1 class="text-amber-50 font-semibold">Listing / Directory Coffee Shop</h1>
    <a href="map_dark.html" class="text-amber-200">Map</a>
  </div>
</header>

<main class="max-w-6xl mx-auto p-6">
  <section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Filters -->
    <aside class="lg:col-span-1 bg-[#111111] p-4 rounded card">
      <h3 class="font-semibold text-amber-100 mb-3">Filters</h3>

      <label class="text-sm text-amber-300">Search name</label>
      <input id="q" class="w-full mt-1 p-2 rounded bg-[#0f0f0f] border border-amber-800 text-amber-100" placeholder="Cari nama..." />

      <label class="text-sm text-amber-300 mt-3 block">Min Rating</label>
      <select id="minRating" class="w-full mt-1 p-2 rounded bg-[#0f0f0f] border border-amber-800 text-amber-100">
        <option value="0">Any</option><option value="3">3+</option><option value="4">4+</option><option value="4.5">4.5+</option>
      </select>

      <label class="text-sm text-amber-300 mt-3 block">Sort by</label>
      <select id="sortBy" class="w-full mt-1 p-2 rounded bg-[#0f0f0f] border border-amber-800 text-amber-100">
        <option value="name">Name (A–Z)</option>
        <option value="rating_desc">Rating (High → Low)</option>
        <option value="rating_asc">Rating (Low → High)</option>
      </select>

      <div class="mt-4 flex gap-2">
        <button id="applyBtn" class="px-3 py-2 bg-amber-600 text-black rounded">Apply</button>
        <button id="resetBtn" class="px-3 py-2 border border-amber-800 rounded text-amber-200">Reset</button>
      </div>

      <div class="mt-6 text-sm text-amber-300">Showing <span id="count">0</span> results</div>
    </aside>

    <!-- Listing -->
    <section class="lg:col-span-3">
      <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-amber-300">Sort & Filters applied live</div>
        <div class="text-sm text-amber-300">Per page:
          <select id="perPage" class="bg-[#0f0f0f] border border-amber-800 text-amber-100 p-1 rounded ml-2">
            <option>5</option><option>10</option><option>20</option>
          </select>
        </div>
      </div>

      <div id="cards" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

      <!-- Pagination -->
      <div class="mt-4 flex justify-center items-center gap-2" id="pagination"></div>
    </section>
  </section>
</main>

<!-- Modal detail -->
<div id="modal" class="fixed inset-0 hidden items-center justify-center modal-backdrop z-50">
  <div class="bg-[#0f0f0f] w-11/12 md:w-3/4 rounded-lg p-4 border border-amber-800">
    <div class="flex justify-between items-center">
      <h4 id="mTitle" class="text-amber-50 font-semibold"></h4>
      <button id="closeModal" class="text-amber-300">Close</button>
    </div>
    <div class="mt-3 md:flex gap-4">
      <div class="md:w-1/2">
        <div id="miniMap" class="rounded border border-amber-800"></div>
      </div>
      <div class="md:w-1/2">
        <div id="mContent" class="text-amber-200 text-sm"></div>
      </div>
    </div>
  </div>
</div>

<script>
let shops = null;
let filtered = [];
let currentPage = 1;
let perPage = 5;
let miniMap, miniMarker;

// load data
fetch('shops.geojson').then(r=>r.json()).then(data=>{
  shops = data.features.map(f=>({ ...f.properties, coords: f.geometry.coordinates }));
  applyFilters();
});

// helpers
function renderCards(){
  const container = document.getElementById('cards');
  container.innerHTML='';
  const start=(currentPage-1)*perPage;
  const slice = filtered.slice(start, start+perPage);
  slice.forEach((s, idx)=>{
    const card = document.createElement('div');
    card.className='p-4 rounded card cursor-pointer';
    card.innerHTML = `
      <div class="flex justify-between items-start">
        <div>
          <div class="font-semibold text-amber-50">${s.name}</div>
          <div class="text-xs text-amber-300">${s.address}</div>
        </div>
        <div class="text-right">
          <div class="text-sm text-amber-300">Rating</div>
          <div class="font-bold text-white">${s.rating}</div>
        </div>
      </div>
      <div class="mt-3 flex gap-2">
        <button class="openBtn px-3 py-1 bg-amber-600 text-black rounded" data-idx="${idx+start}">Detail</button>
        <button class="zoomBtn px-3 py-1 border border-amber-800 text-amber-200 rounded" data-coords="${s.coords}">Zoom</button>
      </div>
    `;
    container.appendChild(card);
  });

  // attach events
  document.querySelectorAll('.openBtn').forEach(b=>b.addEventListener('click', (e)=>{
    const i = parseInt(e.target.dataset.idx);
    openModal(filtered[i]);
  }));
  document.querySelectorAll('.zoomBtn').forEach(b=>b.addEventListener('click', (e)=>{
    const coords = e.target.dataset.coords.split(',').map(Number);
    // open map in new tab centered (use map_dark.html with hash params)
    window.open(`map_dark.html#${coords[1]},${coords[0]}`, '_blank');
  }));

  renderPagination();
  document.getElementById('count').innerText = filtered.length;
}

function renderPagination(){
  const total = Math.ceil(filtered.length / perPage);
  const pag = document.getElementById('pagination');
  pag.innerHTML='';
  if(total<=1) return;
  for(let i=1;i<=total;i++){
    const b = document.createElement('button');
    b.innerText = i;
    b.className = 'px-3 py-1 rounded ' + (i===currentPage ? 'bg-amber-600 text-black' : 'border border-amber-800 text-amber-200');
    b.addEventListener('click', ()=>{ currentPage=i; renderCards(); });
    pag.appendChild(b);
  }
}

function applyFilters(){
  const q = (document.getElementById('q').value || '').toLowerCase();
  const minR = parseFloat(document.getElementById('minRating').value || 0);
  const sortBy = document.getElementById('sortBy').value;
  perPage = parseInt(document.getElementById('perPage').value || 5);
  filtered = shops.filter(s=> s.name.toLowerCase().includes(q) && (parseFloat(s.rating||0) >= minR));
  if(sortBy==='name') filtered.sort((a,b)=>a.name.localeCompare(b.name));
  if(sortBy==='rating_desc') filtered.sort((a,b)=> (b.rating||0)-(a.rating||0));
  if(sortBy==='rating_asc') filtered.sort((a,b)=> (a.rating||0)-(b.rating||0));
  currentPage = 1;
  renderCards();
}

document.getElementById('applyBtn').addEventListener('click', applyFilters);
document.getElementById('resetBtn').addEventListener('click', ()=>{
  document.getElementById('q').value=''; document.getElementById('minRating').value='0'; document.getElementById('sortBy').value='name';
  applyFilters();
});
document.getElementById('perPage').addEventListener('change', ()=>{ perPage = parseInt(document.getElementById('perPage').value); currentPage=1; renderCards(); });

// modal functions
function openModal(item){
  document.getElementById('mTitle').innerText = item.name;
  document.getElementById('mContent').innerHTML = `
    <div class="text-amber-200"><strong>Address:</strong> ${item.address}</div>
    <div class="text-amber-200 mt-2"><strong>Rating:</strong> ${item.rating}</div>
  `;
  document.getElementById('modal').classList.remove('hidden');
  // init mini map
  setTimeout(()=>{
    if(miniMap) miniMap.remove();
    miniMap = L.map('miniMap', {attributionControl:false, zoomControl:false}).setView([item.coords[1], item.coords[0]], 17);
    L.tileLayer('https://tile.jawg.io/jawg-dark/{z}/{x}/{y}.png?access-token=ggK0D5bH2lO0tNWcQeD7JqTLqH4FZy2E5bxzhHcEQn4p9P5V8r',{maxZoom:19}).addTo(miniMap);
    if(miniMarker) miniMarker.remove();
    miniMarker = L.marker([item.coords[1], item.coords[0]]).addTo(miniMap);
  }, 300);
}

document.getElementById('closeModal').addEventListener('click', ()=>{
  document.getElementById('modal').classList.add('hidden');
  if(miniMap) { miniMap.remove(); miniMap = null; }
});

// live search on typing
document.getElementById('q').addEventListener('input', ()=>{ applyFilters(); });

</script>

</body>
</html>
