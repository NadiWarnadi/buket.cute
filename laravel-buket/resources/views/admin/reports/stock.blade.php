@extends('layouts.admin')

@section('title', 'Laporan Stok Bahan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3">📦 Laporan Stok Bahan Baku</h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Bahan</small>
                <h3 class="mb-0">{{ $totalIngredients }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Stok Rendah</small>
                <h3 class="mb-0 text-warning">{{ $lowStockCount }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Stok Habis</small>
                <h3 class="mb-0 text-danger">{{ $emptyStockCount }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Status Normal</small>
                <h3 class="mb-0 text-success">{{ $totalIngredients - $lowStockCount - $emptyStockCount }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Cari Bahan</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nama bahan" value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status Stok</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Stok Rendah</option>
                    <option value="empty" {{ request('status') === 'empty' ? 'selected' : '' }}>Stok Habis</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('admin.reports.stock') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ingredients Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0">Daftar Stok Bahan</h6>
    </div>
    <div class="card-body p-0">
        @if($ingredients->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Min Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $ingredient)
                            <tr>
                                <td><strong>{{ $ingredient->name }}</strong></td>
                                <td>{{ $ingredient->unit }}</td>
                                <td>{{ $ingredient->stock }}</td>
                                <td>{{ $ingredient->min_stock ?? '-' }}</td>
                                <td>
                                    @if($ingredient->stock == 0)
                                        <span class="badge bg-danger">Habis</span>
                                    @elseif($ingredient->isLowStock())
                                        <span class="badge bg-warning">Rendah</span>
                                    @else
                                        <span class="badge bg-success">Normal</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.ingredients.show', $ingredient) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center p-3">
                {{ $ingredients->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <p class="text-muted">Tidak ada data bahan baku</p>
            </div>
        @endif
    </div>
</div>

<!-- Stock Movement History -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">📝 Riwayat Pergerakan Stok Bahan Baku</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Bahan Baku</th>
                        <th>Tipe</th>
                        <th class="text-end">Perubahan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyMovements as $movement)
                        <tr>
                            <td>
                                <small>{{ $movement->created_at?->format('d/m/Y H:i') ?? '-' }}</small>
                            </td>
                            <td>
                                @if($movement->ingredient)
                                    <strong>{{ $movement->ingredient->name }}</strong>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                @if($movement->type === 'in')
                                    <span class="badge bg-success">IN</span>
                                @elseif($movement->type === 'out')
                                    <span class="badge bg-danger">OUT</span>
                                @else
                                    <span class="badge bg-secondary">{{ $movement->type }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($movement->type === 'in')
                                    <span class="text-success">+{{ $movement->quantity }}</span>
                                @else
                                    <span class="text-danger">-{{ $movement->quantity }}</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $movement->description ?? '-' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Tidak ada riwayat pergerakan stok bulan ini</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($monthlyMovements->hasPages())
            <div class="d-flex justify-content-center p-3">
                {{ $monthlyMovements->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Back Button -->
<div class="mt-4">
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        ← Kembali
    </a>
</div>
@endsection