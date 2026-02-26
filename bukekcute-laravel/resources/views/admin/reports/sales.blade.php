@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📊 Laporan Penjualan</h1>
            <p class="text-muted small mt-1">Ringkasan penjualan dan pendapatan toko</p>
        </div>
        <a href="{{ route('admin.reports.export-sales') }}" class="btn btn-outline-success btn-sm">
            📥 Export CSV
        </a>
    </div>

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">📅 Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">📅 Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        🔍 Filter
                    </button>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-secondary w-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Total Penjualan</small>
                    <h4 class="mb-0 text-success">{{ count($orders) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Total Pendapatan</small>
                    <h4 class="mb-0 text-primary">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Rata-rata Pesanan</small>
                    <h4 class="mb-0 text-info">
                        Rp {{ count($orders) > 0 ? number_format($totalRevenue / count($orders), 0, ',', '.') : '0' }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Status Sukses</small>
                    <h4 class="mb-0 text-warning">
                        {{ count($orders->where('status', 'completed')) }}/{{ count($orders) }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">🌸 Produk Terlaris</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productSales as $product)
                                    <tr>
                                        <td>
                                            <strong>{{ $product['name'] }}</strong>
                                        </td>
                                        <td class="text-end">{{ $product['quantity'] }}</td>
                                        <td class="text-end text-success">
                                            <strong>Rp {{ number_format($product['revenue'], 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">📈 Status Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center g-2">
                        @php
                            $statuses = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];
                        @endphp
                        @foreach($statuses as $status)
                            @php
                                $count = $orders->where('status', $status)->count();
                                $colors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'processing' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                ];
                            @endphp
                            <div class="col">
                                <div class="p-3 bg-{{ $colors[$status] ?? 'secondary' }}-light rounded">
                                    <strong class="d-block text-{{ $colors[$status] ?? 'secondary' }}">{{ $count }}</strong>
                                    <small class="text-muted text-capitalize">{{ $status }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Detail Table -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">📋 Detail Pesanan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <small>{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $order->customer->name ?? 'Guest' }}</strong><br>
                                    <small class="text-muted">{{ $order->customer->phone }}</small>
                                </td>
                                <td>
                                    @foreach($order->items as $item)
                                        <small class="d-block">{{ $item->product->name }}</small>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    <strong>{{ $order->items->sum('quantity') }}</strong>
                                </td>
                                <td class="text-end text-success">
                                    <strong>Rp {{ number_format($order->items->sum(DB::raw('quantity * price')), 0, ',', '.') }}</strong>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'processing' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                        ];
                                        $color = $statusColors[$order->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }} text-capitalize">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                        👁️
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada data pesanan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $orders->links() }}
            </div>
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
