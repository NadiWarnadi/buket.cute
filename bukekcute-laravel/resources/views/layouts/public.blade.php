<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Buket Cute') - Rangkaian Bunga & Hamper Terbaik</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #e91e63;
            --secondary-color: #f06292;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            color: #333;
            background-color: #fff;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #fff 0%, #fef5f7 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .navbar-nav .nav-link {
            margin: 0 0.5rem;
            font-weight: 500;
            color: #555 !important;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
            border-bottom: 2px solid var(--primary-color);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e91e63 0%, #f06292 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        /* Buttons */
        .btn-primary-custom {
            background-color: var(--primary-color);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.7rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background-color: #c2185b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
        }

        .btn-outline-primary-custom {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-outline-primary-custom:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Product Card */
        .product-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #f5f5f5;
        }

        .product-body {
            padding: 1rem;
        }

        .product-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            min-height: 40px;
        }

        .product-price {
            font-size: 1.3rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-stock {
            font-size: 0.85rem;
            color: #999;
        }

        .badge-stock {
            background-color: #c8e6c9;
            color: #2e7d32;
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
        }

        .badge-stock.low {
            background-color: #ffccbc;
            color: #d84315;
        }

        /* Footer */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 3rem;
        }

        footer h5 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
        }

        footer a {
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        footer a:hover {
            color: var(--secondary-color);
        }

        footer .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        footer .social-links a:hover {
            background-color: var(--primary-color);
        }

        /* Section Title */
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 3rem;
            text-align: center;
        }

        .section-title::before {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            margin: 0 auto 1rem;
        }

        /* Forms */
        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
        }

        /* Spacing */
        .py-60 {
            padding: 60px 0;
        }

        .mt-70 {
            margin-top: 70px;
        }

        /* Alert */
        .alert {
            border: none;
            border-radius: 10px;
        }

        .alert-success {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .alert-warning {
            background-color: #ffe0b2;
            color: #e65100;
        }

        .alert-danger {
            background-color: #ffccbc;
            color: #d84315;
        }

        /* Pagination */
        .pagination {
            margin-top: 2rem;
        }

        .page-link {
            color: var(--primary-color);
            border-color: #ddd;
        }

        .page-link:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>

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
