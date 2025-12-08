<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Tentang — CoffeePahoman</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body{background:#0b0b0b;color:#fef3c7}
    .card{background:#0f0f0f;border:1px solid rgba(120,53,15,0.25)}
    a { color: #fbbf24 }
  </style>
</head>
<body class="antialiased">

<header class="p-4 bg-[#0b0b0b] border-b border-amber-900/40">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <a href="landing_dark_final.html" class="text-amber-200 text-lg">← Kembali</a>
    <h1 class="text-amber-50 font-semibold">Tentang — CoffeePahoman</h1>
    <a href="map_dark.html" class="text-amber-200">Map</a>
  </div>
</header>

<main class="max-w-6xl mx-auto p-6 space-y-6">

  <section class="card p-6 rounded">
    <h2 class="text-amber-50 text-2xl font-bold">Gambaran Proyek</h2>
    <p class="mt-2 text-amber-200">
      <strong>CoffeePahoman</strong> adalah prototype aplikasi WebGIS yang menampilkan visualisasi dan analisis lokasi coffee shop di wilayah Pahoman, Kota Bandar Lampung.
      Proyek ini menunjukkan alur kerja dasar WebGIS seperti pengolahan data (GeoJSON), peta interaktif (Leaflet), listing, dashboard analisis, hingga halaman detail tiap coffee shop.
    </p>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Tujuan Proyek</h3>
    <ul class="list-disc ml-5 mt-2 text-amber-200">
      <li>Menganalisis persebaran coffee shop di daerah Pahoman.</li>
      <li>Menyediakan direktori coffee shop untuk warga maupun pengunjung.</li>
      <li>Menjadi contoh / portofolio untuk implementasi WebGIS berbasis HTML, Leaflet, dan Laravel/Next.js.</li>
    </ul>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Metodologi</h3>
    <ol class="list-decimal ml-5 mt-2 text-amber-200">
      <li>Mengumpulkan data lokasi coffee shop (survey lapangan / sumber publik).</li>
      <li>Melakukan digitasi dan pembersihan data di QGIS lalu mengekspor ke <code>shops.geojson</code>.</li>
      <li>Menampilkan titik lokasi pada peta interaktif (Leaflet), dilengkapi popup dan identifikasi sederhana.</li>
      <li>Menghasilkan analisis berupa distribusi rating, top area, heatmap, dan tabel data.</li>
    </ol>
    <p class="mt-3 text-xs text-amber-300">Catatan: Prototype ini menggunakan data contoh. Ganti <code>shops.geojson</code> dengan data asli untuk hasil yang lebih akurat.</p>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Sumber Data</h3>
    <ul class="list-disc ml-5 mt-2 text-amber-200">
      <li>Survey lapangan langsung.</li>
      <li>Referensi dari Google Maps (perhatikan kebijakan penggunaan data).</li>
      <li>Hasil digitasi QGIS dalam format GeoJSON.</li>
    </ul>
  </section>

  <section class="card p-6 rounded">
    <h3 class="text-amber-50 font-semibold">Tim & Kredit</h3>
    <p class="text-amber-200 mt-2">Proyek ini dikembangkan oleh <strong>Tim</strong> sebagai prototype untuk tugas kuliah dan portofolio.</p>
    <ul class="list-disc ml-5 mt-2 text-amber-200">
      <li>UI/Styling: Tailwind CSS</li>
      <li>Pemetaan: Leaflet</li>
      <li>Grafik: Chart.js</li>
      <li>Data: File <code>shops.geojson</code> di folder proyek</li>
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

<footer class="p-6 text-center text-xs text-amber-300">
  © 2025 CoffeePahoman — Prototype
</footer>

</body>
</html>
