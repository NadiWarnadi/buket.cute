<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Buket Cute') - Rangkaian Bunga & Hamper Terbaik</title>
    
    
    <!-- Bootstrap & Icons (CDN tetap boleh, atau install via NPM) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- INI BAGIAN VITE -->
    @vite(['resources/css/public-style.css', 'resources/js/public-script.js'])
    
    @yield('extra-css')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('public.home') }}">
                🌸 Buket Cute
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}">
                            Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.catalog') ? 'active' : '' }}" href="{{ route('public.catalog') }}">
                            Katalog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.about') ? 'active' : '' }}" href="{{ route('public.about') }}">
                            Tentang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.contact') ? 'active' : '' }}" href="{{ route('public.contact') }}">
                            Kontak
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.faq') ? 'active' : '' }}" href="{{ route('public.faq') }}">
                            FAQ
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary-custom btn-sm" href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}?text=Halo%20saya%20ingin%20menanyakan%20tentang%20produk%20Anda" target="_blank">
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Page Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="mt-70">
        <div class="container py-60">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5>🌸 Buket Cute</h5>
                    <p class="small">Rangkaian bunga segar dan hamper berkualitas untuk semua momen spesial Anda.</p>
                    <div class="social-links">
                        <a href="https://instagram.com" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://facebook.com" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://tiktok.com" title="TikTok"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <h5>Menu</h5>
                    <ul class="list-unstyled small">
                        <li><a href="{{ route('public.home') }}">Beranda</a></li>
                        <li><a href="{{ route('public.catalog') }}">Katalog Produk</a></li>
                        <li><a href="{{ route('public.about') }}">Tentang Kami</a></li>
                        <li><a href="{{ route('public.faq') }}">FAQ</a></li>
                    </ul>
                </div>

                <div class="col-md-3 mb-4">
                    <h5>📞 Informasi</h5>
                    <ul class="list-unstyled small">
                        <li>📍 {{ env('STORE_ADDRESS', 'Jakarta, Indonesia') }}</li>
                        <li>📱 {{ env('STORE_WHATSAPP', '0812345678901') }}</li>
                        <li>📧 {{ env('STORE_EMAIL', 'info@buketcute.com') }}</li>
                    </ul>
                </div>

                <div class="col-md-3 mb-4">
                    <h5>⏰ Jam Operasional</h5>
                    <ul class="list-unstyled small">
                        <li>Senin - Jumat: 09:00 - 18:00</li>
                        <li>Sabtu: 09:00 - 17:00</li>
                        <li>Minggu: 10:00 - 16:00</li>
                    </ul>
                </div>
            </div>

            <hr class="bg-secondary">

            <div class="row">
                <div class="col-md-6 mb-2 small">
                    <p>&copy; 2026 Buket Cute. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end small">
                    <a href="#" class="text-decoration-none me-3">Kebijakan Privasi</a>
                    <a href="#" class="text-decoration-none">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('extra-js')
</body>
</html>
