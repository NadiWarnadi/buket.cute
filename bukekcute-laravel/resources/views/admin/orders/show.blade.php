@extends('layouts.admin')

@section('title', 'Detail Pesanan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
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

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Pesanan</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">No. Pesanan</h6>
                    <p class="mb-0"><strong>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong></p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Status</h6>
                    <p class="mb-0">
                        <span class="badge bg-{{ $order->getStatusColor() }} fs-6">
                            {{ $order->getStatusLabel() }}
                        </span>
                    </p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Pelanggan</h6>
                    <p class="mb-0"><strong>{{ $order->customer->name }}</strong></p>
                    <p class="mb-0"><small>{{ $order->customer->phone }}</small></p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Tanggal</h6>
                    <p class="mb-0"><small>{{ $order->created_at->format('d M Y H:i') }}</small></p>
                </div>

                <hr>

                <div class="mb-2">
                    <h6 class="text-muted mb-2">Total</h6>
                    <h4>Rp{{ number_format($order->total_price, 0, ',', '.') }}</h4>
                </div>

                @if($order->canBeUpdated())
                    <form action="{{ route('admin.orders.edit', $order) }}" method="GET" class="d-grid">
                        <button type="submit" class="btn btn-warning btn-sm mt-2">
                            <i class="bi bi-pencil"></i> Edit Pesanan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($order->notes)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0">Catatan</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Item Pesanan</h5>
            </div>
            <div class="card-body p-0">
                @if($order->items->count())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th style="width: 12%">Qty</th>
                                    <th style="width: 18%">Harga</th>
                                    <th style="width: 18%">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            @if($item->product_id)
                                                <strong>{{ $item->product->name }}</strong>
                                            @else
                                                <strong>{{ $item->custom_description }}</strong><br>
                                                <small class="text-muted">(Custom)</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td><strong>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>Rp{{ number_format($order->total_price, 0, ',', '.') }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 text-center">
                        <p class="text-muted">Tidak ada item</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Messages/Chat related to this order -->
@if($order->messages->count())
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Pesan Terkait</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tipe</th>
                                <th>Pesan</th>
                                <th>Dari</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->messages as $msg)
                                <tr>
                                    <td><small>{{ ucfirst($msg->type) }}</small></td>
                                    <td><small>{{ Str::limit($msg->body, 50) }}</small></td>
                                    <td><small>{{ $msg->is_incoming ? 'Pelanggan' : 'Bot' }}</small></td>
                                    <td><small>{{ $msg->created_at->format('d M H:i') }}</small></td>
                                    <td>
                                        <small>
                                            @if($msg->status === 'read')
                                                <span class="badge bg-success">Dibaca</span>
                                            @elseif($msg->status === 'delivered')
                                                <span class="badge bg-info">Terkirim</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $msg->status }}</span>
                                            @endif
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
