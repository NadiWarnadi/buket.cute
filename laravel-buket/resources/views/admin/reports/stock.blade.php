@extends('layouts.admin')

@section('title', 'Laporan Stok Bahan')

@section('content')
<!-- Header Section -->
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 mb-1 fw-bold text-dark">📦 Laporan Stok Bahan Baku</h1>
        <p class="text-muted small mb-0">Pantau ketersediaan stok dan riwayat pergerakan logistik secara real-time.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button onclick="window.print()" class="btn btn-outline-secondary me-2">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
        <a href="{{ route('dashboard') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Total Bahan -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm overflow-hidden border-start border-primary border-4 h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase tracking-wider">Total Bahan</small>
                        <h2 class="mb-0 fw-bold mt-1 text-dark">{{ $totalIngredients }}</h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-none d-sm-block">
                        <i class="bi bi-box-seam fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Stok Rendah -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm overflow-hidden border-start border-warning border-4 h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase tracking-wider">Stok Rendah</small>
                        <h2 class="mb-0 fw-bold mt-1 text-warning">{{ $lowStockCount }}</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 d-none d-sm-block">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Stok Habis -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm overflow-hidden border-start border-danger border-4 h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase tracking-wider">Stok Habis</small>
                        <h2 class="mb-0 fw-bold mt-1 text-danger">{{ $emptyStockCount }}</h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3 d-none d-sm-block">
                        <i class="bi bi-x-circle fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Status Normal -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm overflow-hidden border-start border-success border-4 h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold text-uppercase tracking-wider">Status Normal</small>
                        <h2 class="mb-0 fw-bold mt-1 text-success">{{ $totalIngredients - $lowStockCount - $emptyStockCount }}</h2>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 d-none d-sm-block">
                        <i class="bi bi-check2-circle fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphs Section -->
<div class="row g-4 mb-4">
    <!-- Chart Status Stok -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold text-dark mb-0">Proporsi Status Stok</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center px-4 pb-4">
                <div style="position: relative; height:220px; width:100%">
                    <canvas id="stockStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart Pergerakan Logistik -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold text-dark mb-0">Aktivitas Stok Masuk vs Keluar</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div style="position: relative; height:220px; width:100%">
                    <canvas id="stockMovementChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label fw-semibold text-muted small">CARI BAHAN</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="search" name="search" 
                           placeholder="Ketik nama bahan baku..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold text-muted small">STATUS STOK</label>
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Stok Rendah</option>
                    <option value="empty" {{ request('status') === 'empty' ? 'selected' : '' }}>Stok Habis</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 shadow-sm">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('admin.reports.stock') }}" class="btn btn-light border flex-grow-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ingredients Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold text-dark">Daftar Stok Bahan</h5>
        <span class="badge bg-light text-dark border">{{ $ingredients->count() }} Data ditampilkan</span>
    </div>
    <div class="card-body p-0">
        @if($ingredients->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase fs-7 tracking-wider">
                        <tr>
                            <th class="ps-4">Nama Bahan</th>
                            <th>Satuan</th>
                            <th>Stok Saat Ini</th>
                            <th>Min. Stok</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $ingredient)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">{{ $ingredient->name }}</span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ $ingredient->unit }}</span></td>
                                <td>
                                    <span class="fw-bold {{ $ingredient->stock == 0 ? 'text-danger' : ($ingredient->isLowStock() ? 'text-warning' : 'text-dark') }}">
                                        {{ $ingredient->stock }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $ingredient->min_stock ?? '-' }}</td>
                                <td>
                                    @if($ingredient->stock == 0)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">🔴 Habis</span>
                                    @elseif($ingredient->isLowStock())
                                        <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">⚠️ Rendah</span>
                                    @else
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">🟢 Normal</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.ingredients.show', $ingredient) }}" class="btn btn-sm btn-icon btn-light border hover-shadow" title="Detail">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center p-4 border-top">
                {{ $ingredients->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <img src="https://illustrations.popsy.co/flat/empty-box.svg" alt="Empty" style="height: 120px;" class="mb-3">
                <p class="text-muted mb-0">Tidak ada data bahan baku yang ditemukan</p>
            </div>
        @endif
    </div>
</div>

<!-- Stock Movement History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h5 class="mb-0 fw-bold text-dark">📝 Riwayat Pergerakan Stok Bulan Ini</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase fs-7 tracking-wider">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Bahan Baku</th>
                        <th>Tipe</th>
                        <th class="text-end">Perubahan</th>
                        <th class="pe-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyMovements as $movement)
                        <tr>
                            <td class="ps-4">
                                <small class="text-muted fw-semibold">{{ $movement->created_at?->format('d M Y, H:i') ?? '-' }}</small>
                            </td>
                            <td>
                                @if($movement->ingredient)
                                    <span class="fw-bold text-dark">{{ $movement->ingredient->name }}</span>
                                @else
                                    <small class="text-muted italic">- Bahan dihapus -</small>
                                @endif
                            </td>
                            <td>
                                @if($movement->type === 'in')
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-bold">MASUK</span>
                                @elseif($movement->type === 'out')
                                    <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2.5 py-1.5 fw-bold">KELUAR</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 fw-bold">{{ strtoupper($movement->type) }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                @if($movement->type === 'in')
                                    <span class="text-success">+{{ $movement->quantity }}</span>
                                @else
                                    <span class="text-danger">-{{ $movement->quantity }}</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                <span class="text-muted small">{{ $movement->description ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-folder-x fs-2 d-block mb-2 text-black-50"></i>
                                Tidak ada riwayat pergerakan stok pada bulan ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($monthlyMovements->hasPages())
            <div class="d-flex justify-content-center p-4 border-top">
                {{ $monthlyMovements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom utility styles untuk mempercantik UI */
    .fs-7 { font-size: 0.75rem; }
    .tracking-wider { letter-spacing: 0.05em; }
    
    /* Subtly colored badges */
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.12) !important; }
    .bg-warning-subtle { background-color: rgba(255, 193, 7, 0.15) !important; }
    .bg-danger-subtle { background-color: rgba(220, 53, 69, 0.12) !important; }
    .bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.12) !important; }
    
    .table > :not(caption) > * > * {
        padding: 0.85rem 0.5rem;
    }
    .hover-shadow:hover {
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
</style>
@endpush

@push('scripts')
<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. DONUT CHART (Proporsi Status Stok) ---
        const ctxStatus = document.getElementById('stockStatusChart').getContext('2d');
        
        // Kalkulasi data dari server side variable laravel
        const lowStock = {{ $lowStockCount }};
        const emptyStock = {{ $emptyStockCount }};
        const normalStock = {{ $totalIngredients }} - lowStock - emptyStock;

        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Rendah', 'Habis'],
                datasets: [{
                    data: [normalStock, lowStock, emptyStock],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 15, font: { family: 'inherit', size: 12 } }
                    }
                },
                cutout: '70%'
            }
        });

        // --- 2. BAR CHART (Aktivitas Pergerakan Stok) ---
        // Kita kalkulasi data ringkasan pergerakan IN & OUT dari data server
        @php
            $inCount = $monthlyMovements->where('type', 'in')->sum('quantity');
            $outCount = $monthlyMovements->where('type', 'out')->sum('quantity');
        @endphp

        const ctxMovement = document.getElementById('stockMovementChart').getContext('2d');
        new Chart(ctxMovement, {
            type: 'bar',
            data: {
                labels: ['Stok Masuk (IN)', 'Stok Keluar (OUT)'],
                datasets: [{
                    label: 'Volume Kuantitas Bulan Ini',
                    data: [{{ $inCount }}, {{ $outCount }}],
                    backgroundColor: ['rgba(25, 135, 84, 0.85)', 'rgba(220, 53, 69, 0.85)'],
                    borderColor: ['#198754', '#dc3545'],
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: '#f1f1f1' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endpush