@extends('layouts.admin')

@section('title', 'Detail Bahan Baku')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.ingredients.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detail Bahan</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted mb-1">Nama Bahan</h6>
                    <p class="mb-0"><strong>{{ $ingredient->name }}</strong></p>
                </div>

                <div class="mb-4">
                    <h6 class="text-muted mb-1">Satuan</h6>
                    <p class="mb-0"><strong>{{ $ingredient->unit }}</strong></p>
                </div>

                @if($ingredient->description)
                    <div class="mb-4">
                        <h6 class="text-muted mb-1">Deskripsi</h6>
                        <p>{{ $ingredient->description }}</p>
                    </div>
                @endif

                <div class="mb-2">
                    <a href="{{ route('admin.ingredients.edit', $ingredient) }}" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-box"></i> Manajemen Stok</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-6 col-md-4">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Stok Saat Ini</h6>
                            <h3 class="mb-0">{{ $ingredient->stock }}</h3>
                            <small>{{ $ingredient->unit }}</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Min Stok</h6>
                            <h3 class="mb-0">{{ $ingredient->min_stock ?? '-' }}</h3>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Status</h6>
                            @if($ingredient->isLowStock())
                                <span class="badge bg-danger" style="font-size: 1rem;">Stok Rendah</span>
                            @elseif($ingredient->stock == 0)
                                <span class="badge bg-secondary" style="font-size: 1rem;">Habis</span>
                            @else
                                <span class="badge bg-success" style="font-size: 1rem;">Normal</span>
                            @endif
                        </div>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <h6 class="mb-3">Update Stok Manual</h6>
                    <form action="{{ route('admin.ingredients.updateStock', $ingredient) }}" method="POST" class="row g-2 g-md-3">
                        @csrf
                        @method('PATCH')

                        <div class="col-12 col-md-6">
                            <input type="number" name="stock" class="form-control" placeholder="Stok baru" value="{{ $ingredient->stock }}" min="0" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Movements History -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Riwayat Pergerakan Stok</h5>
            </div>
            <div class="card-body p-0">
                @if($stockMovements->count())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockMovements as $movement)
                                    <tr>
                                        <td>
                                            <small>{{ $movement->created_at->format('d M Y H:i') }}</small>
                                        </td>
                                        <td>
                                            @if($movement->type === 'in')
                                                <span class="badge bg-success">Masuk</span>
                                            @else
                                                <span class="badge bg-danger">Keluar</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $movement->quantity }}</strong></td>
                                        <td><small>{{ $movement->description }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center p-3">
                        {{ $stockMovements->links() }}
                    </div>
                @else
                    <div class="p-5 text-center">
                        <p class="text-muted">Belum ada riwayat pergerakan stok</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
