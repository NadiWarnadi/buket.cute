@extends('layouts.admin')

@section('title', 'Export Laporan Penjualan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light">
        <h6 class="mb-0">Laporan Penjualan {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>No. Pesanan</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                            <td>Rp{{ number_format($order->total_price, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $order->getStatusColor() }}">
                                    {{ $order->getStatusLabel() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada data pesanan</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">TOTAL:</th>
                        <th>Rp{{ number_format($orders->sum('total_price'), 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="p-3 text-end">
            <button class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak
            </button>
            <a href="data:text/csv;charset=utf-8,{{ urlencode($csvData ?? '') }}" download="laporan_penjualan.csv" class="btn btn-success btn-sm">
                <i class="bi bi-download"></i> Download CSV
            </a>
        </div>
    </div>
</div>
@endsection
