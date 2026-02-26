@extends('layouts.admin')

@section('title', 'Detail Produk')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detail Produk</h5>
            </div>
            <div class="card-body">
                @if($product->getFeaturedImage())
                    <div class="mb-4">
                        <img src="{{ $product->getFeaturedImage()->getUrl() }}" class="img-fluid rounded" style="max-width: 300px;">
                    </div>
                @endif

                <div class="row mb-4">
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Nama Produk</h6>
                        <p class="mb-0"><strong>{{ $product->name }}</strong></p>
                    </div>
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Status</h6>
                        <p class="mb-0">
                            @if($product->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Kategori</h6>
                        <p class="mb-0"><strong>{{ $product->category->name }}</strong></p>
                    </div>
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Slug</h6>
                        <p class="mb-0"><code>{{ $product->slug }}</code></p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Harga</h6>
                        <p class="mb-0"><strong class="h5">Rp{{ number_format($product->price, 0, ',', '.') }}</strong></p>
                    </div>
                    <div class="col-12 col-sm-6">
                        <h6 class="text-muted mb-1">Stok</h6>
                        <p class="mb-0"><strong><span class="badge bg-info" style="font-size: 1rem;">{{ $product->stock }}</span></strong></p>
                    </div>
                </div>

                @if($product->description)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Deskripsi</h6>
                        <p>{{ nl2br($product->description) }}</p>
                    </div>
                @endif

                <div class="mb-4">
                    <h6 class="text-muted mb-2">Tanggal</h6>
                    <p class="mb-1"><small>Dibuat: {{ $product->created_at->format('d M Y H:i') }}</small></p>
                    <p class="mb-0"><small>Diperbarui: {{ $product->updated_at->format('d M Y H:i') }}</small></p>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-end">
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
