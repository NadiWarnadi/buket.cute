@extends('layouts.admin')

@section('title', 'Daftar Pesanan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Daftar Pesanan</h1>
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Pesanan Baru
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Search & Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="search" class="form-label">Cari Pesanan</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="No/Pelanggan/HP..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-2">
                <label for="status" class="form-label">Status Pesanan</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="all" @selected(!request('status') || request('status') == 'all')>Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" @selected(request('status') == $st)>{{ $st == 'pending' ? 'Pending' : ($st == 'processed' ? 'Diproses' : ($st == 'completed' ? 'Selesai' : 'Dibatalkan')) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="payment_status" class="form-label">Status Bayar</label>
                <select class="form-select" id="payment_status" name="payment_status" onchange="this.form.submit()">
                    <option value="all" @selected(!request('payment_status') || request('payment_status') == 'all')>Semua</option>
                    <option value="pending" @selected(request('payment_status') == 'pending')>Menunggu Bayar</option>
                    <option value="paid" @selected(request('payment_status') == 'paid')>Sudah Dibayar</option>
                    <option value="failed" @selected(request('payment_status') == 'failed')>Gagal</option>
                    <option value="refunded" @selected(request('payment_status') == 'refunded')>Refund</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="sort" class="form-label">Urutkan</label>
                <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                    <option value="latest" @selected(request('sort') == 'latest' || !request('sort'))>Terbaru</option>
                    <option value="oldest" @selected(request('sort') == 'oldest')>Terlama</option>
                    <option value="total-high" @selected(request('sort') == 'total-high')>Total Tinggi</option>
                    <option value="total-low" @selected(request('sort') == 'total-low')>Total Rendah</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($orders->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%">No. Pesanan</th>
                            <th style="width: 20%">Pelanggan</th>
                            <th style="width: 8%">Item</th>
                            <th style="width: 14%">Total</th>
                            <th style="width: 12%">Metode Bayar</th>
                            <th style="width: 12%">Status Bayar</th>
                            <th style="width: 12%">Status</th>
                            <th style="width: 12%">Tanggal</th>
                            <th style="width: 10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <strong>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td>
                                    {{ $order->customer->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $order->customer->phone }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $order->items->count() }}</span>
                                </td>
                                <td>
                                    <strong>Rp{{ number_format($order->total_price, 0, ',', '.') }}</strong>
                                </td>
                                <td>
                                    @php
                                        $methodLabels = [
                                            'cod' => 'COD',
                                            'bank_transfer' => 'Transfer',
                                            'qris' => 'QRIS',
                                        ];
                                    @endphp
                                    <span class="badge bg-primary">{{ $methodLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</span>
                                </td>
                                <td>
                                    @php
                                        $paymentColor = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                            'refunded' => 'info',
                                        ][$order->payment_status] ?? 'secondary';
                                        $paymentLabel = [
                                            'pending' => 'Menunggu',
                                            'paid' => 'Lunas',
                                            'failed' => 'Gagal',
                                            'refunded' => 'Refund',
                                        ][$order->payment_status] ?? $order->payment_status;
                                    @endphp
                                    <span class="badge bg-{{ $paymentColor }}">{{ $paymentLabel }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $order->getStatusColor() }}">{{ $order->getStatusLabel() }}</span>
                                </td>
                                <td>
                                    <small>{{ $order->created_at->format('d M Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus pesanan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center p-3">
                {{ $orders->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada pesanan</p>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Buat Pesanan
                </a>
            </div>
        @endif
    </div>
</div>
@endsection