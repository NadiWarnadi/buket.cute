@extends('layouts.admin')

@section('title', 'Detail Pembelian')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Pembelian</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">No. Pembelian</h6>
                    <p class="mb-0"><strong>#{{ $purchase->id }}</strong></p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Supplier</h6>
                    <p class="mb-0"><strong>{{ $purchase->supplier }}</strong></p>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted mb-1">Tanggal</h6>
                    <p class="mb-0"><small>{{ $purchase->created_at->format('d M Y H:i') }}</small></p>
                </div>

                <hr>

                <div class="mb-2">
                    <h6 class="text-muted mb-2">Total Pembelian</h6>
                    <h4>Rp{{ number_format($purchase->total, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Detail Item Pembelian</h5>
            </div>
            <div class="card-body p-0">
                @if($purchase->items->count())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bahan Baku</th>
                                    <th style="width: 12%">Qty</th>
                                    <th style="width: 18%">Harga Satuan</th>
                                    <th style="width: 18%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->ingredient->name }}</strong>
                                            <br><small class="text-muted">{{ $item->ingredient->unit }}</small>
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td><strong>Rp{{ number_format($item->total_price, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endforeach
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

<!-- Stock Movements Related to This Purchase -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Dampak pada Stok Bahan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bahan Baku</th>
                                <th style="width: 15%">Stok Sebelum</th>
                                <th style="width: 15%">Penambahan</th>
                                <th style="width: 15%">Stok Sekarang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                <tr>
                                    <td>{{ $item->ingredient->name }}</td>
                                    <td>{{ $item->ingredient->stock - $item->quantity }}</td>
                                    <td><span class="badge bg-success">+ {{ $item->quantity }}</span></td>
                                    <td><strong>{{ $item->ingredient->stock }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex gap-2 justify-content-end">
            <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus? Stok bahan akan dikembalikan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Hapus Pembelian
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
