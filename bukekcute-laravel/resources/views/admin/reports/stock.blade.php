@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📦 Laporan Stok</h1>
            <p class="text-muted small mt-1">Pantau stok produk dan bahan baku</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="type" class="form-label">📋 Jenis Stok</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">-- Semua --</option>
                        <option value="products" {{ request('type') === 'products' ? 'selected' : '' }}>Produk</option>
                        <option value="ingredients" {{ request('type') === 'ingredients' ? 'selected' : '' }}>Bahan Baku</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">⚠️ Status Stok</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">-- Semua --</option>
                        <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Stok Rendah</option>
                        <option value="normal" {{ request('status') === 'normal' ? 'selected' : '' }}>Normal</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        🔍 Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block">Total Produk</small>
                    <h4 class="mb-0">{{ $totalProducts ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block">Produk Stok Rendah</small>
                    <h4 class="mb-0 text-warning">{{ $lowStockProducts ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block">Total Bahan Baku</small>
                    <h4 class="mb-0">{{ $totalIngredients ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block">Bahan Baku Stok Rendah</small>
                    <h4 class="mb-0 text-warning">{{ $lowStockIngredients ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Products Stock -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🌸 Stok Produk</h5>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-primary">
                        Kelola →
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Stok</th>
                                    <th class="text-end">Min</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    @php
                                        $isLow = $product->stock <= ($product->minimum_stock ?? 5);
                                    @endphp
                                    <tr class="{{ $isLow ? 'table-warning' : '' }}">
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            <small class="text-muted">SKU: {{ $product->sku ?? '-' }}</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ $product->stock }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <small>{{ $product->minimum_stock ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @if($isLow)
                                                <span class="badge bg-warning">⚠️ Rendah</span>
                                            @else
                                                <span class="badge bg-success">✓ Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Tidak ada data produk</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ingredients Stock -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🥀 Stok Bahan Baku</h5>
                    <a href="{{ route('admin.ingredients.index') }}" class="btn btn-sm btn-outline-primary">
                        Kelola →
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bahan</th>
                                    <th class="text-end">Stok</th>
                                    <th>Satuan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ingredients as $ingredient)
                                    @php
                                        $isLow = $ingredient->stock <= ($ingredient->minimum_stock ?? 10);
                                    @endphp
                                    <tr class="{{ $isLow ? 'table-warning' : '' }}">
                                        <td>
                                            <strong>{{ $ingredient->name }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ $ingredient->stock }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $ingredient->unit }}</small>
                                        </td>
                                        <td>
                                            @if($isLow)
                                                <span class="badge bg-warning">⚠️ Rendah</span>
                                            @else
                                                <span class="badge bg-success">✓ Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Tidak ada data bahan baku</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement History -->
    <div class="card border-0 shadow-sm mt-4">
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
                        @forelse($movements as $movement)
                            <tr>
                                <td>
                                    <small>{{ $movement->created_at->format('d/m/Y H:i') }}</small>
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
                                    <small>{{ $movement->description ?? $movement->notes ?? '-' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Tidak ada riwayat pergerakan stok</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($movements && $movements->hasPages())
                <div class="p-3 border-top">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>
</div>
@endsection
