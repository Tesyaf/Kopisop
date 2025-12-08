<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Analisis / Dashboard — CoffeePahoman</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- leaflet.heat -->
  <script src="https://cdn.jsdelivr.net/gh/Leaflet/Leaflet.heat@gh-pages/dist/leaflet-heat.js"></script>

  <style>
    body { background:#0b0b0b; color:#fef3c7; }
    .card { background:#0f0f0f; border:1px solid rgba(120,53,15,0.25); }
    #miniMap { height:300px; border-radius:10px; }
    .chart-card { height:280px; }
  </style>
</head>
<body class="antialiased">

<header class="p-4 bg-[#0b0b0b] border-b border-amber-900/40">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <a href="landing_dark_final.html" class="text-amber-200 text-lg">← Back</a>
    <h1 class="text-amber-50 font-semibold">Analisis / Dashboard</h1>
    <a href="map_dark.html" class="text-amber-200">Map</a>
  </div>
</header>

<main class="max-w-6xl mx-auto p-6 space-y-6">

  <!-- Summary metrics -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="p-4 card rounded">
      <div class="text-sm text-amber-300">Total Coffee Shop</div>
      <div id="metric-total" class="text-2xl font-bold text-amber-50">—</div>
    </div>
    <div class="p-4 card rounded">
      <div class="text-sm text-amber-300">Average Rating</div>
      <div id="metric-avg" class="text-2xl font-bold text-amber-50">—</div>
    </div>
    <div class="p-4 card rounded">
      <div class="text-sm text-amber-300">Median Rating</div>
      <div id="metric-med" class="text-2xl font-bold text-amber-50">—</div>
    </div>
    <div class="p-4 card rounded">
      <div class="text-sm text-amber-300">Top Area</div>
      <div id="metric-top" class="text-2xl font-bold text-amber-50">—</div>
    </div>
  </section>

  <!-- Charts & Map -->
  <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="col-span-2 card p-4 rounded">
      <h3 class="text-amber-200 font-semibold mb-2">Distribusi Rating</h3>
      <canvas id="ratingChart" class="chart-card bg-[#0b0b0b] p-2 rounded"></canvas>
    </div>

    <div class="card p-4 rounded">
      <h3 class="text-amber-200 font-semibold mb-2">Top Areas (count)</h3>
      <canvas id="areaChart" class="chart-card bg-[#0b0b0b] p-2 rounded"></canvas>
    </div>
  </section>

  <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card p-4 rounded">
      <h3 class="text-amber-200 font-semibold mb-2">Mini Heatmap</h3>
      <div id="miniMap"></div>
    </div>

    <div class="card p-4 rounded">
      <h3 class="text-amber-200 font-semibold mb-2">Table Data</h3>
      <div id="tableContainer" class="text-sm text-amber-200 max-h-72 overflow-auto"></div>
    </div>
  </section>

  <section class="text-sm text-amber-300">
    <div class="card p-4 rounded">
      <strong>Notes:</strong> Charts and statistics are generated from <code>shops.geojson</code> in the same folder. Replace it with your real GeoJSON export from QGIS for full analysis.
    </div>
  </section>

</main>

<script>
// Utility functions
function mean(arr){ if(!arr.length) return 0; return arr.reduce((a,b)=>a+b,0)/arr.length; }
function median(arr){ if(!arr.length) return 0; const s = arr.slice().sort((a,b)=>a-b); const m = Math.floor(s.length/2); return s.length%2? s[m] : (s[m-1]+s[m])/2; }

// load geojson
let shops = null;
fetch('shops.geojson').then(r=>r.json()).then(data=>{
  shops = data.features.map(f=>{
    return {
      name: f.properties.name || '',
      address: f.properties.address || '',
      rating: parseFloat(f.properties.rating || 0),
      coords: f.geometry.coordinates
    };
  });

  // metrics
  document.getElementById('metric-total').innerText = shops.length;
  const ratings = shops.map(s=>s.rating).filter(r=>!isNaN(r));
  document.getElementById('metric-avg').innerText = (mean(ratings)).toFixed(2);
  document.getElementById('metric-med').innerText = (median(ratings)).toFixed(2);

  // top area estimation (simple: use first word of address / street)
  const areaCounts = {};
  shops.forEach(s=>{
    const area = (s.address || '').split(' ')[1] || (s.address||'Unknown');
    areaCounts[area] = (areaCounts[area]||0)+1;
  });
  const sortedAreas = Object.entries(areaCounts).sort((a,b)=>b[1]-a[1]);
  document.getElementById('metric-top').innerText = sortedAreas.length? `${sortedAreas[0][0]} (${sortedAreas[0][1]})` : '-';

  // rating distribution chart
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
    data: {
      labels: Object.keys(bins),
      datasets: [{ label: 'Count', data: Object.values(bins), backgroundColor: '#f59e0b' }]
    },
    options: {
      plugins:{ legend:{ display:false }, tooltip:{mode:'index'} },
      scales: {
        x: { ticks:{ color:'#fef3c7' }, grid:{ display:false } },
        y: { ticks:{ color:'#fef3c7' }, grid:{ color:'rgba(255,255,255,0.05)' } }
      }
    }
  });

  // areas chart (top 5)
  const areaLabels = sortedAreas.slice(0,5).map(a=>a[0]||'Unknown');
  const areaValues = sortedAreas.slice(0,5).map(a=>a[1]);
  const ctx2 = document.getElementById('areaChart').getContext('2d');
  new Chart(ctx2, {
    type: 'doughnut',
    data: { labels: areaLabels, datasets:[{ data: areaValues, backgroundColor:['#f59e0b','#fbbf24','#f97316','#fca5a5','#fb923c'] }] },
    options: { plugins:{ legend:{ labels:{ color:'#fef3c7' } } } }
  });

  // mini heatmap
  const map = L.map('miniMap', { zoomControl:false }).setView([data.features[0].geometry.coordinates[1], data.features[0].geometry.coordinates[0]], 15);
  L.tileLayer('https://tile.jawg.io/jawg-dark/{z}/{x}/{y}.png?access-token=ggK0D5bH2lO0tNWcQeD7JqTLqH4FZy2E5bxzhHcEQn4p9P5V8r',{maxZoom:19}).addTo(map);
  const heatPoints = data.features.map(f=>[f.geometry.coordinates[1], f.geometry.coordinates[0], 0.6]);
  L.heatLayer(heatPoints, { radius: 25, blur: 15, gradient: {0.2:'#f97316',0.4:'#f59e0b',0.6:'#fb923c',0.8:'#fca5a5'}}).addTo(map);

  // table
  const table = document.getElementById('tableContainer');
  const tbl = document.createElement('table');
  tbl.className = 'w-full text-left';
  tbl.innerHTML = `<thead><tr class="text-amber-300"><th class="p-2">Name</th><th class="p-2">Address</th><th class="p-2">Rating</th></tr></thead>`;
  const tbody = document.createElement('tbody');
  shops.forEach(s=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="p-2 text-amber-100">${s.name}</td><td class="p-2 text-amber-300">${s.address}</td><td class="p-2">${s.rating}</td>`;
    tbody.appendChild(tr);
  });
  tbl.appendChild(tbody);
  table.appendChild(tbl);
})
.catch(err=>{
  console.error(err);
  document.getElementById('metric-total').innerText = 'Error';
});
</script>

</body>
</html>
