<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Buket Cute - Rangkaian Bunga & Hamper Terbaik')</title>
    
    <meta name="description" content="@yield('meta_description', 'Toko buket bunga wisuda, hampers estetik, dan rangkaian bunga segar terbaik di Buket Cute. Proses cepat dan bisa custom.')">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    @vite(['resources/css/public-style.css'])
    @vite(['resources/js/public-script.js'])
    
    @yield('extra-css')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light sticky-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="{{ route('public.home') }}">
                <img src="{{ asset('images/logo-60px.png') }}" 
                     alt="Logo Buket Cute" 
                     width="60" 
                     height="60" 
                     style="width: 35px; height: 35px; object-fit: contain;">
                Buket Cute
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Navigasi Menu Utama">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.catalog') ? 'active' : '' }}" href="{{ route('public.catalog') }}">Katalog</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.about') ? 'active' : '' }}" href="{{ route('public.about') }}">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.contact') ? 'active' : '' }}" href="{{ route('public.contact') }}">Kontak</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('public.faq') ? 'active' : '' }}" href="{{ route('public.faq') }}">FAQ</a></li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-luxury btn-sm" href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}?text=Halo%20saya%20ingin%20menanyakan%20tentang%20produk%20Anda" target="_blank" rel="noopener noreferrer" aria-label="Hubungi kami melalui WhatsApp">
                            💬 WhatsApp
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="mt-70">
        <div class="container py-60">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5>🌸 Buket Cute</h5>
                    <p class="small">Rangkaian bunga segar dan hamper berkualitas untuk semua momen spesial Anda.</p>
                    <div class="social-links">
                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Kunjungi Instagram Buket Cute"><i class="bi bi-instagram"></i></a>
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Kunjungi Facebook Buket Cute"><i class="bi bi-facebook"></i></a>
                        <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" aria-label="Kunjungi TikTok Buket Cute"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('scroll', function() {
                const mainNav = document.getElementById('mainNav');
                if(mainNav) {
                    mainNav.classList.toggle('scrolled', window.scrollY > 50);
                }
            }, { passive: true });
        });
    </script>

    @yield('extra-js')
</body>
</html>