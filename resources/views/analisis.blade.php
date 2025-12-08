@extends('layouts.app')

@section('title', 'Analisis / Dashboard - CoffeePahoman')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/gh/Leaflet/Leaflet.heat@gh-pages/dist/leaflet-heat.js"></script>
<style>
    .card { background:#0f0f0f; border:1px solid rgba(120,53,15,0.25); }
    #miniMap { height:300px; border-radius:10px; }
    .chart-card { height:280px; }
</style>
@endpush

@section('content')
<header class="mb-6" data-aos="fade-down">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-amber-50">Analisis / Dashboard</h1>
    <a href="{{ route('map') }}" class="text-amber-200">Ke Map</a>
  </div>
</header>

<section class="grid grid-cols-1 md:grid-cols-4 gap-4">
  <div class="p-4 card rounded" data-aos="fade-up" data-aos-delay="50">
    <div class="text-sm text-amber-300">Total Coffee Shop</div>
    <div id="metric-total" class="text-2xl font-bold text-amber-50">-</div>
  </div>
  <div class="p-4 card rounded" data-aos="fade-up" data-aos-delay="100">
    <div class="text-sm text-amber-300">Average Rating</div>
    <div id="metric-avg" class="text-2xl font-bold text-amber-50">-</div>
  </div>
  <div class="p-4 card rounded" data-aos="fade-up" data-aos-delay="150">
    <div class="text-sm text-amber-300">Median Rating</div>
    <div id="metric-med" class="text-2xl font-bold text-amber-50">-</div>
  </div>
  <div class="p-4 card rounded" data-aos="fade-up" data-aos-delay="200">
    <div class="text-sm text-amber-300">Top Harga</div>
    <div id="metric-top" class="text-2xl font-bold text-amber-50">-</div>
  </div>
</section>

<section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
  <div class="col-span-2 card p-4 rounded" data-aos="fade-right">
    <h3 class="text-amber-200 font-semibold mb-2">Distribusi Rating</h3>
    <canvas id="ratingChart" class="chart-card bg-[#0b0b0b] p-2 rounded"></canvas>
  </div>

  <div class="card p-4 rounded" data-aos="fade-left" data-aos-delay="100">
    <h3 class="text-amber-200 font-semibold mb-2">Rata-rata Harga (Top 5)</h3>
    <canvas id="priceChart" class="chart-card bg-[#0b0b0b] p-2 rounded"></canvas>
  </div>
</section>

<section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
  <div class="card p-4 rounded" data-aos="fade-right">
    <h3 class="text-amber-200 font-semibold mb-2">Mini Heatmap</h3>
    <div id="miniMap"></div>
  </div>

  <div class="card p-4 rounded" data-aos="fade-left" data-aos-delay="100">
    <h3 class="text-amber-200 font-semibold mb-2">Table Data</h3>
    <div id="tableContainer" class="text-sm text-amber-200 max-h-72 overflow-auto"></div>
  </div>
</section>

<section class="mt-6 text-sm text-amber-300">
  <div class="card p-4 rounded">
    <strong>Notes:</strong> Charts dan statistik berasal dari endpoint GeoJSON backend (<code>/api/geojson/shops</code>). Ganti data dengan export terbaru untuk mencerminkan hasil analisis spasial.
  </div>
</section>
@endsection

@push('scripts')
<script>
const shopsUrl = "{{ route('api.geojson', ['type' => 'shops']) }}";
function mean(arr){ if(!arr.length) return 0; return arr.reduce((a,b)=>a+b,0)/arr.length; }
function median(arr){ if(!arr.length) return 0; const s = arr.slice().sort((a,b)=>a-b); const m = Math.floor(s.length/2); return s.length%2? s[m] : (s[m-1]+s[m])/2; }

let shops = null;
fetch(shopsUrl).then(r=>r.json()).then(data=>{
  shops = data.features.map(f=>{
    return {
      name: f.properties.NAMA || '',
      rating: parseFloat(f.properties.RATING || 0),
      harga: parseFloat(f.properties.HARGA || 0),
      coords: f.geometry.coordinates
    };
  });

  document.getElementById('metric-total').innerText = shops.length;
  const ratings = shops.map(s=>s.rating).filter(r=>!isNaN(r));
  document.getElementById('metric-avg').innerText = (mean(ratings)).toFixed(2);
  document.getElementById('metric-med').innerText = (median(ratings)).toFixed(2);

  const sortedHarga = shops.slice().sort((a,b)=>(b.harga||0)-(a.harga||0));
  document.getElementById('metric-top').innerText = sortedHarga.length ? `${sortedHarga[0].name} (Rp ${sortedHarga[0].harga})` : '-';

  const bins = { '0-2':0, '2-3':0, '3-4':0, '4-4.5':0, '4.5-5':0 };
  ratings.forEach(r=>{
    if(r<2) bins['0-2']++;
    else if(r<3) bins['2-3']++;
    else if(r<4) bins['3-4']++;
    else if(r<4.5) bins['4-4.5']++;
    else bins['4.5-5']++;
  });

  const ctx = document.getElementById('ratingChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: { labels: Object.keys(bins), datasets: [{ label: 'Count', data: Object.values(bins), backgroundColor: '#f59e0b' }] },
    options: {
      plugins:{ legend:{ display:false } },
      scales: {
        x: { ticks:{ color:'#fef3c7' }, grid:{ display:false } },
        y: { ticks:{ color:'#fef3c7' }, grid:{ color:'rgba(255,255,255,0.05)' } }
      }
    }
  });

  const priceTop = sortedHarga.slice(0,5);
  const ctx2 = document.getElementById('priceChart').getContext('2d');
  new Chart(ctx2, {
    type: 'doughnut',
    data: { labels: priceTop.map(p=>p.name || 'Unknown'), datasets:[{ data: priceTop.map(p=>p.harga||0), backgroundColor:['#f59e0b','#fbbf24','#f97316','#fca5a5','#fb923c'] }] },
    options: { plugins:{ legend:{ labels:{ color:'#fef3c7' } } } }
  });

  const map = L.map('miniMap', { zoomControl:false }).setView([data.features[0].geometry.coordinates[1], data.features[0].geometry.coordinates[0]], 15);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{maxZoom:19, attribution:'©OpenStreetMap, ©CARTO'}).addTo(map);
  const heatPoints = data.features.map(f=>[f.geometry.coordinates[1], f.geometry.coordinates[0], 0.6]);
  L.heatLayer(heatPoints, { radius: 25, blur: 15, gradient: {0.2:'#f97316',0.4:'#f59e0b',0.6:'#fb923c',0.8:'#fca5a5'}}).addTo(map);

  const table = document.getElementById('tableContainer');
  const tbl = document.createElement('table');
  tbl.className = 'w-full text-left';
  tbl.innerHTML = `<thead><tr class="text-amber-300"><th class="p-2">Name</th><th class="p-2">Harga</th><th class="p-2">Rating</th></tr></thead>`;
  const tbody = document.createElement('tbody');
  shops.forEach(s=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="p-2 text-amber-100">${s.name}</td><td class="p-2 text-amber-300">Rp ${s.harga}</td><td class="p-2">${s.rating}</td>`;
    tbody.appendChild(tr);
  });
  tbl.appendChild(tbody);
  table.appendChild(tbl);
});
</script>
@endpush
