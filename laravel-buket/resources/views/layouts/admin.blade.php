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
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @yield('extra-css')
    @stack('styles')
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
            <a href="{{ route('admin.fuzzy-rules.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.fuzzy-rules.index' || Route::currentRouteName() === 'admin.fuzzy-rules.create' || Route::currentRouteName() === 'admin.fuzzy-rules.edit' || Route::currentRouteName() === 'admin.fuzzy-rules.show') active @endif">
                <i class="bi bi-robot"></i> Fuzzy Rules
            </a>
            <a href="{{ route('admin.complaints.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.complaints.index' || Route::currentRouteName() === 'admin.complaints.show') active @endif">
                <i class="bi bi-exclamation-triangle"></i> Komplain
            </a>
            <div class="sidebar-item-group">
                <a href="#reportDropdown" class="sidebar-item" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="bi bi-file-earmark-text"></i> Laporan
                </a>
                <div class="collapse" id="reportDropdown">
                    <a href="{{ route('admin.reports.sales') }}" class="sidebar-item-sub @if(Route::currentRouteName() === 'admin.reports.sales') active @endif">
                        <i class="bi bi-graph-up"></i> Penjualan
                    </a>
                    <a href="{{ route('admin.reports.stock') }}" class="sidebar-item-sub @if(Route::currentRouteName() === 'admin.reports.stock') active @endif">
                        <i class="bi bi-box-seam"></i> Stok
                    </a>
                    <a href="{{ route('admin.reports.chat') }}" class="sidebar-item-sub @if(Route::currentRouteName() === 'admin.reports.chat') active @endif">
                        <i class="bi bi-chat-dots"></i> Chat
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.settings.index') }}" class="sidebar-item @if(Route::currentRouteName() === 'admin.settings.index') active @endif">
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