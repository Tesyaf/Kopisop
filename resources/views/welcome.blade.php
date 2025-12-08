@extends('layouts.app')

@section('title', 'Analisis Coffee Shop - Pahoman')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .map-dots {
        background: radial-gradient(circle at 20% 20%, rgba(16,185,129,0.6) 0.6px, transparent 0.6px),
                    radial-gradient(circle at 50% 40%, rgba(16,185,129,0.5) 0.6px, transparent 0.6px),
                    radial-gradient(circle at 80% 30%, rgba(16,185,129,0.4) 0.6px, transparent 0.6px),
                    radial-gradient(circle at 30% 70%, rgba(16,185,129,0.3) 0.6px, transparent 0.6px),
                    linear-gradient(180deg, #0d0d0d, #1a1a1a);
        background-size: 100% 100%;
        box-shadow: inset 0 0 80px rgba(0,0,0,0.8);
    }
    #previewMap { height: 100%; width: 100%; }
</style>
@endpush

@section('content')
  <section id="home" class="py-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
      <div data-aos="fade-right">
        <p class="inline-flex items-center gap-2 text-xs font-medium uppercase text-amber-300">
          <span class="px-2 py-1 rounded bg-amber-800 text-amber-100">Analisis</span>
          <span>Pahoman, Bandar Lampung</span>
        </p>
        <h1 class="mt-6 text-4xl md:text-5xl font-extrabold leading-tight text-amber-50">
          Map Design & Analisis <br class="hidden md:block" />Coffee Shop di Pahoman
        </h1>
        <p class="mt-4 text-amber-200/80 max-w-xl">
          Visualisasi dan analisis spasial coffee shop di wilayah Pahoman untuk membantu identifikasi kepadatan, rating, dan peluang lokasi.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <a href="{{ route('map') }}" class="px-5 py-3 rounded-md bg-amber-600 text-black font-semibold shadow hover:bg-amber-500">Get Started</a>
          <a href="{{ route('analisis') }}" class="px-5 py-3 rounded-md border border-amber-800 text-amber-200 hover:bg-amber-900/40">Lihat Analisis</a>
        </div>
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-md">
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg" data-aos="fade-up" data-aos-delay="100">
            <div class="text-sm text-amber-300">Coffee Shop</div>
            <div id="metric-total" class="mt-1 text-2xl font-bold text-white">-</div>
          </div>
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg" data-aos="fade-up" data-aos-delay="200">
            <div class="text-sm text-amber-300">Avg Rating</div>
            <div id="metric-avg" class="mt-1 text-2xl font-bold text-white">-</div>
          </div>
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg" data-aos="fade-up" data-aos-delay="300">
            <div class="text-sm text-amber-300">Top Rating</div>
            <div id="metric-top" class="mt-1 text-2xl font-bold text-white">-</div>
          </div>
        </div>
      </div>
      <aside data-aos="fade-left">
        <div class="relative rounded-2xl overflow-hidden map-dots h-80 md:h-96">
          <div id="previewMap" class="absolute inset-0"></div>
          <div class="absolute top-6 left-6 bg-black/40 backdrop-blur-sm p-3 rounded-lg border border-amber-900/50">
            <div class="text-xs text-amber-300">Zoom: 12</div>
            <div class="font-semibold text-white">Pahoman</div>
          </div>
          <div class="absolute bottom-6 right-6 bg-black/30 p-3 rounded-lg border border-amber-900/40">
            <div class="text-xs text-amber-300">Legend</div>
            <div class="flex gap-2 items-center mt-2">
              <span class="w-3 h-3 rounded-full bg-green-400"></span>
              <span class="text-xs text-amber-200">Coffee Shop</span>
            </div>
            <div class="flex gap-2 items-center mt-1">
              <span class="w-3 h-3 rounded-full bg-amber-400"></span>
              <span class="text-xs text-amber-200">Boundary</span>
            </div>
          </div>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="text-amber-50/60 text-sm md:text-base text-center max-w-xs">[Map preview]</div>
          </div>
        </div>
      </aside>
    </div>
  </section>
@endsection

@push('scripts')
<script>
const shopsUrl = "{{ route('api.geojson', ['type' => 'shops'], false) }}";
const boundaryUrl = "{{ route('api.geojson', ['type' => 'boundary'], false) }}";

function mean(arr){ if(!arr.length) return 0; return arr.reduce((a,b)=>a+b,0)/arr.length; }

fetch(shopsUrl).then(r=>r.json()).then(data=>{
  const shops = data.features;
  const ratings = shops.map(s=> parseFloat(s.properties.RATING || 0)).filter(r=>!isNaN(r));
  document.getElementById('metric-total').innerText = shops.length;
  document.getElementById('metric-avg').innerText = ratings.length ? mean(ratings).toFixed(2) : '-';
  const top = shops.slice().sort((a,b)=> (parseFloat(b.properties.RATING||0)) - (parseFloat(a.properties.RATING||0)))[0];
  document.getElementById('metric-top').innerText = top ? top.properties.NAMA : '-';

  const map = L.map('previewMap', { zoomControl:false, attributionControl:false }).setView([-5.4295, 105.2625], 14);
  L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", { maxZoom:19 }).addTo(map);
  const icon = L.divIcon({ className:"", html:'<i class="fa-solid fa-mug-saucer" style="color:#b45309;font-size:18px;text-shadow:0 1px 2px rgba(0,0,0,0.4);"></i>', iconSize:[18,18], iconAnchor:[9,18] });
  L.geoJSON(data, { pointToLayer:(f,latlng)=>L.marker(latlng,{icon}) }).addTo(map);

  fetch(boundaryUrl)
    .then(r=>r.ok ? r.json() : null)
    .then(bound=>{
      if(bound) L.geoJSON(bound, { style:{ color:'#22c55e', weight:2, fillOpacity:0.02 } }).addTo(map);
    })
    .catch(()=>{});
});
</script>
@endpush
