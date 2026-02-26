@extends('layouts.admin')

@section('title', 'Riwayat Pembelian')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Riwayat Pembelian Bahan</h1>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Catat Pembelian
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
        @if($purchases->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%">No. Pembelian</th>
                            <th style="width: 25%">Supplier</th>
                            <th style="width: 15%">Jumlah Item</th>
                            <th style="width: 20%">Total</th>
                            <th style="width: 15%">Tanggal</th>
                            <th style="width: 10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchases as $purchase)
                            <tr>
                                <td>
                                    <strong>#{{ $purchase->id }}</strong>
                                </td>
                                <td>{{ $purchase->supplier }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $purchase->items->count() }}</span>
                                </td>
                                <td>
                                    <strong>Rp{{ number_format($purchase->total, 0, ',', '.') }}</strong>
                                </td>
                                <td>
                                    <small>{{ $purchase->created_at->format('d M Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin? Stok akan dikembalikan.&apos;)">
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
                {{ $purchases->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada data pembelian</p>
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Catat Pembelian Baru
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
