@extends('layouts.app')

@section('title', 'Listing - CoffeePahoman')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .card { background: linear-gradient(145deg, #0f0f0f, #0c0c0c); border: 1px solid rgba(120,53,15,0.25); box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
    .map-icon { color:#d97706; font-size:20px; text-shadow:0 1px 2px rgba(0,0,0,0.4); }
    .filter-label { color:#fbbf24; font-weight:600; letter-spacing:0.02em; }
    .pill { background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.25); }
    .fade-in { animation: fadeIn .35s ease; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }
</style>
@endpush

@section('content')
<header class="mb-8" data-aos="fade-down">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <p class="text-amber-400 uppercase text-xs tracking-[0.2em]">Directory</p>
      <h1 class="text-3xl font-bold text-amber-50 mt-1">Coffee Shop Listing</h1>
      <p class="text-amber-300 text-sm">Filter, sortir, dan lihat lokasi cepat.</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ route('map') }}" class="px-4 py-2 rounded-md border border-amber-700 text-amber-100 hover:bg-amber-800/30">Lihat Peta</a>
      <button id="resetBtn" class="px-4 py-2 rounded-md bg-amber-600 text-black btn-amber">Reset Filter</button>
    </div>
  </div>
</header>

<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
  <aside class="lg:col-span-1 bg-[#0f0f0f] p-5 rounded card" data-aos="fade-right">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-amber-50">Filter & Sort</h3>
      <span class="pill px-2 py-1 rounded text-xs text-amber-200">Live</span>
    </div>

    <label class="text-xs filter-label">Search</label>
    <div class="mt-1 flex items-center gap-2 rounded bg-[#0b0b0b] border border-amber-800 px-2">
      <i class="fa-solid fa-magnifying-glass text-amber-500 text-sm"></i>
      <input id="q" class="w-full p-2 rounded bg-transparent text-amber-100 placeholder:text-amber-600 outline-none" placeholder="Cari nama...">
    </div>

    <div class="mt-4">
      <label class="text-xs filter-label block">Min Rating</label>
      <select id="minRating" class="w-full mt-1 p-2 rounded bg-[#0b0b0b] border border-amber-800 text-amber-100">
        <option value="0">Any</option><option value="3">3+</option><option value="4">4+</option><option value="4.5">4.5+</option>
      </select>
    </div>

    <div class="mt-4">
      <label class="text-xs filter-label block">Sort by</label>
      <select id="sortBy" class="w-full mt-1 p-2 rounded bg-[#0b0b0b] border border-amber-800 text-amber-100">
        <option value="name">Name (A-Z)</option>
        <option value="rating_desc">Rating (High → Low)</option>
        <option value="rating_asc">Rating (Low → High)</option>
      </select>
    </div>

    <div class="mt-4 text-sm text-amber-300 flex items-center justify-between">
      <span>Showing</span>
      <span class="text-amber-100 font-semibold" id="count">0</span>
    </div>
  </aside>

  <section class="lg:col-span-3" data-aos="fade-left" data-aos-delay="100">
    <div class="flex flex-wrap gap-3 items-center mb-5">
      <div class="text-sm text-amber-300">Per page:</div>
      <select id="perPage" class="bg-[#0f0f0f] border border-amber-800 text-amber-100 p-2 rounded">
        <option>5</option><option>10</option><option>20</option>
      </select>
      <span class="text-xs text-amber-500">Tap kartu untuk detail atau zoom.</span>
    </div>

    <div id="cards" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

    <div class="mt-6 flex justify-center items-center gap-2" id="pagination"></div>
  </section>
</section>

@endsection

@push('scripts')
<script>
const shopsUrl = "{{ route('api.geojson', ['type' => 'shops'], false) }}";
let shops = [];
let filtered = [];
let currentPage = 1;
let perPage = 5;

fetch(shopsUrl).then(r=>r.json()).then(data=>{
  shops = data.features.map(f=>({
    name: f.properties.NAMA || 'Tanpa nama',
    rating: parseFloat(f.properties.RATING || 0),
    buka: f.properties.WAKTU_BUKA || '-',
    tutup: f.properties.WKT_TUTUP || '-',
    harga: f.properties.HARGA || 0,
    coords: f.geometry.coordinates,
    address: 'Pahoman, Bandar Lampung'
  }));
  applyFilters();
});

function renderCards(){
  const container = document.getElementById('cards');
  container.innerHTML='';
  const start=(currentPage-1)*perPage;
  const slice = filtered.slice(start, start+perPage);
  slice.forEach((s, idx)=>{
    const card = document.createElement('div');
    card.className='p-5 rounded card cursor-pointer group transition-all fade-in';
    card.innerHTML = `
      <div class="flex justify-between items-start gap-3">
        <div class="flex items-center gap-3">
          <span class="w-10 h-10 rounded-full bg-amber-900/40 border border-amber-700 flex items-center justify-center text-amber-200 text-lg">
            <i class="fa-solid fa-mug-saucer"></i>
          </span>
          <div>
            <div class="font-semibold text-amber-50 text-lg">${s.name}</div>
            <div class="text-xs text-amber-300">${s.address}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-xs text-amber-400 uppercase tracking-wide">Rating</div>
          <div class="font-bold text-white text-xl">${s.rating}</div>
        </div>
      </div>
      <div class="mt-3 text-sm text-amber-200">Buka: ${s.buka} - ${s.tutup}</div>
      <div class="text-sm text-amber-300">Harga rata-rata: Rp ${s.harga}</div>
      <div class="mt-4 flex gap-2">
        <button class="openBtn px-3 py-2 bg-amber-600 text-black rounded btn-amber text-sm" data-idx="${idx+start}">Detail</button>
        <button class="zoomBtn px-3 py-2 border border-amber-800 text-amber-200 rounded text-sm" data-coords="${s.coords}">Zoom</button>
      </div>
    `;
    container.appendChild(card);
  });

  document.querySelectorAll('.openBtn').forEach(b=>b.addEventListener('click', (e)=>{
    const i = parseInt(e.target.dataset.idx);
    const name = encodeURIComponent(filtered[i].name);
    window.open(`{{ route('detail') }}#${name}`, '_blank');
  }));
  document.querySelectorAll('.zoomBtn').forEach(b=>b.addEventListener('click', (e)=>{
    const coords = e.target.dataset.coords.split(',').map(Number);
    window.open(`{{ route('map') }}#${coords[1]},${coords[0]}`, '_blank');
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

document.getElementById('resetBtn').addEventListener('click', ()=>{
  document.getElementById('q').value=''; document.getElementById('minRating').value='0'; document.getElementById('sortBy').value='name';
  applyFilters();
});
document.getElementById('minRating').addEventListener('change', applyFilters);
document.getElementById('sortBy').addEventListener('change', applyFilters);
document.getElementById('perPage').addEventListener('change', ()=>{ 
  perPage = parseInt(document.getElementById('perPage').value); 
  currentPage = 1;
  applyFilters();
});
document.getElementById('q').addEventListener('input', ()=>{ applyFilters(); });
</script>
@endpush
