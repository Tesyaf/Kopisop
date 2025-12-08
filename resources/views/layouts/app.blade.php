<!doctype html>
<html lang="id" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'CoffeePahoman')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <script>
        tailwindConfig = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: #0b0b0b;
            color: #fef3c7;
        }

        .card {
            background: #0f0f0f;
            border: 1px solid rgba(120, 53, 15, 0.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .nav-link {
            color: #fcd34d;
            transition: color .15s ease;
        }

        .nav-link:hover {
            color: #fff;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 2px;
            background: #fbbf24;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .2s ease;
        }

        .nav-link:hover::after {
            transform: scaleX(1);
        }

        .nav-link.active {
            color: #fff;
        }

        .nav-link.active::after {
            transform: scaleX(1);
        }

        .btn-amber {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .btn-amber:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(251, 191, 36, 0.25);
        }
    </style>
    @stack('head')
</head>

<body class="antialiased">
    <header class="sticky top-0 z-50 bg-[#0b0b0b]/85 backdrop-blur-sm border-b border-amber-900/40">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-amber-50/10 flex items-center justify-center border border-amber-900/60 overflow-hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain">
                </div>
                <span class="font-semibold text-lg text-amber-50">CoffeePahoman</span>
            </a>

            <nav class="hidden md:flex gap-6 items-center text-sm">
                @php $current = url()->current(); @endphp
                <a class="nav-link relative pb-1 {{ str_starts_with($current, route('map')) ? 'active' : '' }}" href="{{ route('map') }}">Map / WebGIS</a>
                <a class="nav-link relative pb-1 {{ str_starts_with($current, route('listing')) ? 'active' : '' }}" href="{{ route('listing') }}">Listing</a>
                <a class="nav-link relative pb-1 {{ str_starts_with($current, route('analisis')) ? 'active' : '' }}" href="{{ route('analisis') }}">Analisis</a>
                <a class="nav-link relative pb-1 {{ str_starts_with($current, route('about')) ? 'active' : '' }}" href="{{ route('about') }}">About</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('map') }}" class="hidden md:inline-block px-4 py-2 rounded-md bg-amber-600 text-black text-sm font-medium hover:bg-amber-500">Lihat Peta</a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">
        @yield('content')
    </main>

    <footer class="bg-[#0b0b0b] border-t border-amber-900/60 mt-10">
        <div class="max-w-6xl mx-auto px-6 py-10 text-amber-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-semibold text-white">CoffeePahoman</h4>
                    <p class="mt-2 text-sm text-amber-300">Analisis Coffee Shop di Pahoman - Dark Mode.</p>
                </div>
                <div>
                    <h5 class="font-medium text-amber-200">Quick links</h5>
                    <ul class="mt-3 text-sm space-y-2 text-amber-300">
                        <li><a href="{{ route('map') }}" class="hover:text-white">Map / WebGIS</a></li>
                        <li><a href="{{ route('listing') }}" class="hover:text-white">Listing</a></li>
                        <li><a href="{{ route('analisis') }}" class="hover:text-white">Analisis</a></li>
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
                2025 CoffeePahoman - Tugas SIG Praktikum
            </div>
        </div>
    </footer>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.AOS) {
                AOS.init({
                    once: true,
                    duration: 800,
                    easing: 'ease-out-quart'
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>