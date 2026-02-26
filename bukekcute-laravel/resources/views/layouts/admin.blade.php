<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Admin') - Bucket Cutie</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #f88ec3;
            --secondary-color: #ff059f;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            margin-left: 15px;
        }

        .navbar-toggler {
            margin-right: 15px;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: calc(100vh - 70px);
            position: fixed;
            left: -280px;
            top: 70px;
            padding: 20px 0;
            transition: left 0.3s ease;
            z-index: 999;
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-item {
            display: block;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-item:hover,
        .sidebar-item.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #fff;
        }

        .sidebar-item i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            transition: margin-left 0.3s ease;
            margin-left: 0;
            padding: 20px 15px;
            min-height: calc(100vh - 70px);
        }

        @media (min-width: 992px) {
            .main-content {
                margin-left: var(--sidebar-width);
                padding: 30px;
            }

            .sidebar {
                left: 0;
            }

            .sidebar-toggle {
                display: none;
            }
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }

        /* Alert */
        .alert {
            border: none;
            border-radius: 8px;
        }

        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Responsive */
        @media (max-width: 991px) {
            .main-content {
                padding: 15px;
            }

            .card {
                margin-bottom: 15px;
            }

            .btn {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.2rem;
                margin-left: 10px;
            }

            .main-content {
                padding: 10px;
            }

            .card-body {
                padding: 1rem;
            }

            h1, h2, h3, h4, h5 {
                font-size: 1.25rem !important;
            }

            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 998;
        }

        .sidebar-overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid ps-0 pe-0">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-box2-heart"></i> Bucket Cutie
            </a>
            <button class="navbar-toggler sidebar-toggle" type="button" data-bs-target="#sidebar" onclick="toggleSidebar()">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="dropdown-item" style="border: none; background: none; cursor: pointer;">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-item @if(Route::currentRouteName() === 'dashboard') active @endif">
                <i class="bi bi-house-fill"></i> Dashboard
            </a>
            <a href="{{ route('admin.orders.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.orders.index') active @endif">
                <i class="bi bi-cart-check"></i> Pesanan
            </a>
            <a href="{{ route('admin.customers.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.customers.index') active @endif">
                <i class="bi bi-people"></i> Pelanggan
            </a>
            <a href="{{ route('admin.categories.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.categories.index') active @endif">
                <i class="bi bi-tags"></i> Kategori
            </a>
            <a href="{{ route('admin.products.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.products.index') active @endif">
                <i class="bi bi-box"></i> Produk
            </a>
            <a href="{{ route('admin.ingredients.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.ingredients.index') active @endif">
                <i class="bi bi-leaf"></i> Bahan Baku
            </a>
            <a href="{{ route('admin.purchases.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.purchases.index') active @endif">
                <i class="bi bi-bag-check"></i> Pembelian
            </a>
            <a href="{{ route('admin.chat.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.chat.index' || Route::currentRouteName() === 'admin.chat.show') active @endif">
                <i class="bi bi-chat-dots"></i> Chat
            </a>
            <a href="#" class="sidebar-item">
                <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
            <a href="#" class="sidebar-item">
                <i class="bi bi-gear"></i> Pengaturan
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });

        // Close sidebar when resizing to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>