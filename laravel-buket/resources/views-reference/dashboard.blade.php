@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Dashboard Admin</h1>
            <div>
                <span class="badge bg-success">Online</span>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Selamat Datang, {{ Auth::user()->name }}!</h5>
                <p class="card-text text-muted">Kelola produk, pesanan, bahan baku, dan chat pelanggan dari dashboard ini.</p>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Stats -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-0">Total Produk</p>
                        <h4 class="mb-0">{{ $totalProducts }}</h4>
                    </div>
                    <div class="text-primary" style="font-size: 2rem;">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-0">Kategori</p>
                        <h4 class="mb-0">{{ $totalCategories }}</h4>
                    </div>
                    <div class="text-info" style="font-size: 2rem;">
                        <i class="bi bi-tags"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-0">Produk Stok Rendah</p>
                        <h4 class="mb-0">{{ $lowStockProducts }}</h4>
                    </div>
                    <div class="text-warning" style="font-size: 2rem;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-0">Produk Aktif</p>
                        <h4 class="mb-0">{{ $activeProducts }}</h4>
                    </div>
                    <div class="text-success" style="font-size: 2rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Tambah Produk
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-outline-success w-100">
                            <i class="bi bi-plus-circle"></i> Tambah Kategori
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-info w-100">
                            <i class="bi bi-list-ul"></i> Kelola Produk
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-warning w-100">
                            <i class="bi bi-tags"></i> Kelola Kategori
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reports & Settings -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">📊 Laporan & Pengaturan</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-success w-100">
                            <i class="bi bi-graph-up"></i> Laporan Penjualan
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.reports.stock') }}" class="btn btn-outline-info w-100">
                            <i class="bi bi-box-seam"></i> Laporan Stok
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.reports.chat') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-chat-dots"></i> Laporan Chat
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-warning w-100">
                            <i class="bi bi-gear"></i> Pengaturan Toko
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Section -->
<div class="row">
    <div class="col-12 col-lg-8 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Pesanan Terbaru</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Tidak ada pesanan saat ini</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Info Sistem</h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <p class="mb-2">
                        <strong>User:</strong> {{ Auth::user()->email }}
                    </p>
                    <p class="mb-2">
                        <strong>Tanggal:</strong> {{ now()->format('d F Y') }}
                    </p>
                    <p class="mb-0">
                        <strong>Status WhatsApp:</strong> <span class="badge bg-secondary">Offline</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
