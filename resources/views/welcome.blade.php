<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Analisis Coffee Shop — Pahoman (Dark Mode)</title>

  <!-- Tailwind -->
  <script>
    tailwindConfig = {
      darkMode: 'class',
      theme: { extend: {} }
    }
  </script>
  <script src="https://cdn.tailwindcss.com"></script>

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
  </style>
</head>

<body class="antialiased bg-[#0b0b0b] text-amber-50 transition">

  <!-- NAV -->
  <header class="sticky top-0 z-50 bg-[#0b0b0b]/80 backdrop-blur-sm border-b border-amber-900/40">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="#" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-amber-50/10 flex items-center justify-center border border-amber-900/60">
          <svg class="w-6 h-6 text-amber-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h3l3 2 3-2h3a2 2 0 002-2V7"></path>
          </svg>
        </div>
        <span class="font-semibold text-lg text-amber-50">CoffeePahoman</span>
      </a>

      <nav class="hidden md:flex gap-6 items-center text-sm text-amber-200">
        <a href="#home" class="hover:text-white">Home</a>
        <a href="#map" class="hover:text-white">Map / WebGIS</a>
        <a href="#listing" class="hover:text-white">Listing</a>
        <a href="#analysis" class="hover:text-white">Analisis</a>
        <a href="#detail" class="hover:text-white">Detail</a>
        <a href="#about" class="hover:text-white">About</a>
      </nav>

      <div class="flex items-center gap-3">
        <a href="#map" class="hidden md:inline-block px-4 py-2 rounded-md bg-amber-600 text-black text-sm font-medium hover:bg-amber-500">Lihat Peta</a>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <main id="home" class="max-w-6xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

      <!-- Left -->
      <section>
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
          <a href="#map" class="px-5 py-3 rounded-md bg-amber-600 text-black font-semibold shadow hover:bg-amber-500">Get Started</a>
          <a href="#analysis" class="px-5 py-3 rounded-md border border-amber-800 text-amber-200 hover:bg-amber-900/40">Lihat Analisis</a>
        </div>

        <!-- Metrics -->
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-md">
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg">
            <div class="text-sm text-amber-300">Coffee Shop</div>
            <div class="mt-1 text-2xl font-bold text-white">42</div>
          </div>
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg">
            <div class="text-sm text-amber-300">Avg Rating</div>
            <div class="mt-1 text-2xl font-bold text-white">4.2</div>
          </div>
          <div class="p-4 bg-[#1b1b1b] border border-amber-900/60 rounded-lg">
            <div class="text-sm text-amber-300">Top Area</div>
            <div class="mt-1 text-2xl font-bold text-white">Jalan Ahmad</div>
          </div>
        </div>
      </section>

      <!-- Right / Map Visual -->
      <aside>
        <div class="relative rounded-2xl overflow-hidden map-dots h-80 md:h-96">

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
              <span class="text-xs text-amber-200">Office</span>
            </div>
          </div>

          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="text-amber-50/60 text-sm md:text-base text-center max-w-xs">
              [Map preview — Leaflet / Mapbox]
            </div>
          </div>

        </div>
      </aside>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="bg-[#0b0b0b] border-t border-amber-900/60">
    <div class="max-w-6xl mx-auto px-6 py-10 text-amber-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <h4 class="font-semibold text-white">CoffeePahoman</h4>
          <p class="mt-2 text-sm text-amber-300">Analisis Coffee Shop di Pahoman — Dark Mode.</p>
        </div>

        <div>
          <h5 class="font-medium text-amber-200">Quick links</h5>
          <ul class="mt-3 text-sm space-y-2 text-amber-300">
            <li><a href="#map" class="hover:text-white">Map / WebGIS</a></li>
            <li><a href="#listing" class="hover:text-white">Listing</a></li>
            <li><a href="#analysis" class="hover:text-white">Analisis</a></li>
          </ul>
        </div>

        <div>
          <h5 class="font-medium text-amber-200">Kontak</h5>
          <p class="mt-3 text-sm text-amber-300">
            email: example@coffee.id<br>
            phone: +62 812 3456 7890
          </p>
        </div>
      </div>

      <div class="mt-8 text-center text-xs text-amber-700">
        © 2025 CoffeePahoman — Dark Mode
      </div>
    </div>
  </footer>

</body>
</html>
