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
        {{-- Kartu Detail Produk --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detail Produk</h5>
            </div>
            <div class="card-body">
                {{-- === MEDIA FEATURED UTAMA === --}}
                @php
                    $featuredMedia = $product->media->where('is_featured', true)->first();
                @endphp
                @if($featuredMedia)
                    <div class="mb-4 text-center">
                        @php $isVideo = Str::contains($featuredMedia->mime_type, 'video'); @endphp
                        @if($isVideo)
                            <video controls muted autoplay loop style="max-width: 100%; max-height: 300px;" class="rounded">
                                <source src="{{ Storage::url($featuredMedia->file_path) }}" type="{{ $featuredMedia->mime_type }}">
                                Browser Anda tidak mendukung video.
                            </video>
                        @else
                            <img src="{{ Storage::url($featuredMedia->file_path) }}" alt="{{ $featuredMedia->file_name }}" class="img-fluid rounded" style="max-height: 300px;">
                        @endif
                        <div class="mt-1 text-muted small">
                            <i class="bi bi-star-fill text-warning"></i> Media Utama
                        </div>
                    </div>
                @endif

                {{-- Info Produk --}}
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
                        <p>{!! nl2br(e($product->description)) !!}</p>
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

        {{-- === MEDIA GALLERY (SEMUA MEDIA) === --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-images"></i> Semua Media ({{ $product->media->count() }})</h5>
            </div>
            <div class="card-body">
                @if($product->media->count())
                    <div class="row g-3">
                        @foreach($product->media as $media)
                            @php $isVideo = Str::contains($media->mime_type, 'video'); @endphp
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card h-100 border {{ $media->is_featured ? 'border-warning' : '' }}">
                                    <div class="position-relative">
                                        @if($isVideo)
                                            <video controls muted preload="metadata" class="card-img-top" style="object-fit: cover; height: 140px;">
                                                <source src="{{ Storage::url($media->file_path) }}" type="{{ $media->mime_type }}">
                                            </video>
                                        @else
                                            <img src="{{ Storage::url($media->file_path) }}" alt="{{ $media->file_name }}" class="card-img-top" style="object-fit: cover; height: 140px;">
                                        @endif
                                        @if($media->is_featured)
                                            <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark">
                                                <i class="bi bi-star-fill"></i> Featured
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body p-2">
                                        <small class="text-muted text-truncate d-block" title="{{ $media->file_name }}">
                                            {{ $media->file_name }}
                                        </small>
                                        <small class="badge bg-{{ $isVideo ? 'info' : 'primary' }}">
                                            {{ $isVideo ? 'Video' : 'Gambar' }}
                                        </small>
                                        @if($media->size)
                                            <small class="text-muted ms-1">{{ number_format($media->size / 1024, 1) }} KB</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted text-center py-4">
                        <i class="bi bi-camera display-4 d-block mb-2"></i>
                        <p>Belum ada media.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection