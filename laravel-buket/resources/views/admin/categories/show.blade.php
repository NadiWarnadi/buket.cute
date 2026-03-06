@extends('layouts.admin')

@section('title', 'Detail Kategori')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detail Kategori</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Nama Kategori</h6>
                        <p class="mb-0"><strong>{{ $category->name }}</strong></p>
                    </div>
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Total Produk</h6>
                        <p class="mb-0"><strong><span class="badge bg-primary">{{ $category->products()->count() }}</span></strong></p>
                    </div>
                </div>

                @if($category->description)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Deskripsi</h6>
                        <p>{{ $category->description }}</p>
                    </div>
                @endif

                <div class="mb-4">
                    <h6 class="text-muted mb-3">Tanggal</h6>
                    <p class="mb-1"><small>Dibuat: {{ $category->created_at->format('d M Y H:i') }}</small></p>
                    <p class="mb-0"><small>Diperbarui: {{ $category->updated_at->format('d M Y H:i') }}</small></p>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-end">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($category->products()->count())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">Produk di Kategori Ini</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td><span class="badge bg-info">{{ $product->stock }}</span></td>
                                        <td>Rp{{ number_format($product->price, 0, ',', '.') }}</td>
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
