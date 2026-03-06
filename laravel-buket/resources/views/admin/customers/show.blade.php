@extends('layouts.admin')

@section('title', 'Detail Pelanggan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person"></i> Informasi Pelanggan</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Nama</h6>
                    <p class="mb-0"><strong>{{ $customer->name ?? 'Belum diatur' }}</strong></p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Nomor Telepon/WhatsApp</h6>
                    <p class="mb-0">
                        <a href="https://wa.me/{{ str_replace(['+', '-', ' '], '', $customer->phone) }}" target="_blank" class="text-decoration-none">
                            <i class="bi bi-whatsapp"></i> {{ $customer->phone }}
                        </a>
                    </p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Alamat</h6>
                    <p class="mb-0"><small>{{ $customer->address ?? '-' }}</small></p>
                </div>

                <hr>

                <div class="mb-2">
                    <h6 class="text-muted mb-2">Statistik</h6>
                    <div class="d-flex justify-content-between">
                        <span>Total Pesanan:</span>
                        <strong>{{ $customer->orders->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total Percakapan:</span>
                        <strong>{{ $customer->conversations->count() }}</strong>
                    </div>
                </div>

                <hr>

                <div class="d-grid gap-2">
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Riwayat Pesanan</h5>
            </div>
            <div class="card-body p-0">
                @if($customer->orders->count())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->orders as $order)
                                    <tr>
                                        <td><strong>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                                        <td>Rp{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $order->getStatusColor() }}">
                                                {{ $order->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td><small>{{ $order->created_at->format('d M Y') }}</small></td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center">
                        <p class="text-muted">Belum ada pesanan</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
