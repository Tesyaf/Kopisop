@extends('layouts.app')

@section('title', 'Tentang - CoffeePahoman')

@section('content')
<header class="mb-6 flex items-center justify-between">
  <h1 class="text-2xl font-semibold text-amber-50">Tentang - CoffeePahoman</h1>
  <a href="{{ route('map') }}" class="text-amber-200">Map</a>
</header>

<main class="space-y-6">
  <section class="card p-6 rounded">
    <h2 class="text-amber-50 text-2xl font-bold">Gambaran Proyek</h2>
    <p class="mt-2 text-amber-200">
      <strong>CoffeePahoman</strong> adalah prototype WebGIS dengan Leaflet.js yang menampilkan visualisasi dan analisis lokasi coffee shop di Pahoman, Kota Bandar Lampung.
      Backend Laravel menyajikan GeoJSON melalui endpoint sehingga front-end dapat memuat marker/polygon secara dinamis.
    </p>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Tujuan Proyek</h3>
    <ul class="list-disc ml-5 mt-2 text-amber-200">
      <li>Menganalisis persebaran coffee shop di daerah Pahoman.</li>
      <li>Menyediakan direktori coffee shop interaktif.</li>
      <li>Menjadi contoh implementasi WebGIS berbasis PHP/Laravel + Leaflet.</li>
    </ul>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Metodologi</h3>
    <ol class="list-decimal ml-5 mt-2 text-amber-200">
      <li>Data titik coffee shop diekspor ke <code>public/data/coffeeshops.geojson</code> (atau tabel spasial MySQL).</li>
      <li>Backend menampilkan GeoJSON via endpoint <code>/api/geojson/shops</code> dan <code>/api/geojson/boundary</code>.</li>
      <li>Leaflet menampilkan marker/polygon + popup, listing, dashboard, heatmap.</li>
    </ol>
    <p class="mt-3 text-xs text-amber-300">Catatan: File dapat diganti dengan hasil analisis spasial terbaru, atau digenerate dari query spasial MySQL.</p>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Sumber Data</h3>
    <ul class="list-disc ml-5 mt-2 text-amber-200">
      <li>File GeoJSON titik coffee shop (<code>public/data/coffeeshops.geojson</code>).</li>
      <li>File GeoJSON batas wilayah (<code>public/data/batas-wilayah.geojson</code>).</li>
      <li>Opsional: tabel spasial MySQL untuk CRUD & analisis lanjut.</li>
    </ul>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Kontak</h3>
    <p class="text-amber-200 mt-2">Jika ingin bertanya atau bekerja sama:</p>
    <p class="mt-2 text-amber-200">
      <strong>Email:</strong> example@coffee.id<br>
      <strong>Telepon:</strong> +62 812 3456 7890
    </p>
  </section>
</main>
@endsection
