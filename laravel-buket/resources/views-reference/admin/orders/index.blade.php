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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($orders->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 12%">No. Pesanan</th>
                            <th style="width: 20%">Pelanggan</th>
                            <th style="width: 10%">Item</th>
                            <th style="width: 18%">Total</th>
                            <th style="width: 15%">Status</th>
                            <th style="width: 15%">Tanggal</th>
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
                                    <span class="badge bg-{{ $order->getStatusColor() }}">
                                        {{ $order->getStatusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $order->created_at->format('d M Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($order->canBeUpdated())
                                            <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
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
