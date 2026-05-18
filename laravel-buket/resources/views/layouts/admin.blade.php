<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Bucket Cutie</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    @vite(['resources/css/admin.css'])
    @stack('styles')
</head>
<body class="">

    <!-- Navbar -->
    <nav class="navbar navbar-expand navbar-dark">
        <div class="container-fluid">
            <!-- Tombol Hamburger (Universal untuk Desktop & Mobile) -->
            <button class="sidebar-hamburger me-3" type="button" onclick="toggleSidebar()" title="Toggle Sidebar">
                <i class="bi bi-list" style="font-size: 1.6rem;"></i>
            </button>

            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-box2-heart"></i> Bucket Cutie
            </a>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 me-2"></i> 
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Overlay untuk Mobile (Saat sidebar muncul) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ Route::is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door"></i> Dashboard
            </a>

            <a href="{{ route('admin.orders.index') }}" class="sidebar-item {{ Route::is('admin.orders.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i> Pesanan
            </a>

            <a href="{{ route('admin.customers.index') }}" class="sidebar-item {{ Route::is('admin.customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Pelanggan
            </a>

            <a href="{{ route('admin.categories.index') }}" class="sidebar-item {{ Route::is('admin.categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Kategori
            </a>

            <a href="{{ route('admin.products.index') }}" class="sidebar-item {{ Route::is('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Produk
            </a>

            <!-- Dropdown Bahan -->
<div class="sidebar-item-group">
    <a href="#bahanDropdown" 
       class="sidebar-toggle-link {{ Route::is('admin.ingredients.*','admin.recipes.*','admin.purchases.*') ? 'active' : '' }}" 
       data-bs-toggle="collapse" 
       role="button" 
       aria-expanded="{{ Route::is('admin.ingredients.*','admin.recipes.*','admin.purchases.*') ? 'true' : 'false' }}">
        <i class="bi bi-database"></i> 
        <span>Data Bahan</span>
        <i class="bi bi-chevron-down ms-auto fs-xs"></i>
    </a>
    <div class="collapse {{ Route::is('admin.ingredients.*','admin.recipes.*','admin.purchases.*') ? 'show' : '' }}" id="bahanDropdown">
        <a href="{{ route('admin.ingredients.index') }}" class="sidebar-item-sub {{ Route::is('admin.ingredients.*') ? 'active' : '' }}">
            <i class="bi bi-flower1 me-2"></i> Bahan Baku
        </a>
        <a href="{{ route('admin.recipes.index') }}" class="sidebar-item-sub {{ Route::is('admin.recipes.*') ? 'active' : '' }}">
            <i class="bi bi-journal-check me-2"></i> Resep
        </a>
        <a href="{{ route('admin.purchases.index') }}" class="sidebar-item-sub {{ Route::is('admin.purchases.*') ? 'active' : '' }}">
            <i class="bi bi-bag-check me-2"></i> Pembelian Bahan
        </a>
    </div>
</div>

            {{-- <a href="{{ route('admin.ingredients.index') }}" class="sidebar-item {{ Route::is('admin.ingredients.*') ? 'active' : '' }}">
                <i class="bi bi-flower1"></i> Bahan Baku
            </a>

            <a href="{{ route('admin.purchases.index') }}" class="sidebar-item {{ Route::is('admin.purchases.*') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i> Pembelian Bahan
            </a> --}}

            <a href="{{ route('admin.chat.index') }}" class="sidebar-item {{ Route::is('admin.chat.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots"></i> Chat WhatsApp
            </a>

            <!-- Dropdown Menu Laporan -->
            <div class="sidebar-item-group">
                <a href="#reportDropdown" class="sidebar-toggle-link {{ Route::is('admin.reports.*') ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ Route::is('admin.reports.*') ? 'true' : 'false' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i> 
                    <span>Laporan</span>
                    <i class="bi bi-chevron-down ms-auto fs-xs"></i>
                </a>
                <div class="collapse {{ Route::is('admin.reports.*') ? 'show' : '' }}" id="reportDropdown">
                    <a href="{{ route('admin.reports.sales') }}" class="sidebar-item-sub {{ Route::is('admin.reports.sales') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow me-2"></i> Penjualan
                    </a>
                    <a href="{{ route('admin.reports.stock') }}" class="sidebar-item-sub {{ Route::is('admin.reports.stock') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down me-2"></i> Stok Bahan
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.fuzzy-rules.index') }}" class="sidebar-item {{ Route::is('admin.fuzzy-rules.*') ? 'active' : '' }}">
                <i class="bi bi-cpu"></i> Fuzzy Rules
            </a>

            <a href="{{ route('admin.complaints.index') }}" class="sidebar-item {{ Route::is('admin.complaints.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-octagon"></i> Komplain
            </a>

            <a href="{{ route('admin.settings.index') }}" class="sidebar-item {{ Route::is('admin.settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Pengaturan
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <!-- Breadcrumbs / Header Page (Optional) -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">@yield('page-title')</h4>
            </div>

            @yield('content')
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')

    <script>
        /**
         * Logika Toggle Sidebar
         * Desktop: Menambah class 'sidebar-collapsed' ke body untuk menggeser margin.
         * Mobile: Menambah class 'show' ke sidebar & overlay untuk tampilan melayang.
         */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth < 992) {
                // Perilaku Mobile
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                // Perilaku Desktop
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        /**
         * Otomatis membersihkan state mobile saat layar di-resize ke desktop
         */
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        // Menutup sidebar otomatis di mobile setelah klik link (opsional)
        document.querySelectorAll('.sidebar-item:not([data-bs-toggle])').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>