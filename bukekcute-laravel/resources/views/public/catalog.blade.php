@extends('layouts.public')

@section('title', 'Katalog Produk - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero mb-4">
    <div class="container">
        <h1>📚 Katalog Produk</h1>
        <p>Jelajahi koleksi lengkap bunga dan hamper kami</p>
    </div>
</section>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar (Filters) -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Produk</h5>
                    
                    <form method="GET" class="mb-3">
                        <!-- Kategori -->
                        <div class="mb-3">
                            <label class="form-label fw-600">Kategori</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Semua Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->products_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pencarian -->
                        <div class="mb-3">
                            <label class="form-label fw-600">Cari Produk</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Ketik nama produk..." 
                                   value="{{ request('search') }}">
                        </div>

                        <!-- Sorting -->
                        <div class="mb-3">
                            <label class="form-label fw-600">Urutkan</label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>
                                    Terbaru
                                </option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>
                                    Nama (A-Z)
                                </option>
                                <option value="price-low" {{ request('sort') == 'price-low' ? 'selected' : '' }}>
                                    Harga Terendah
                                </option>
                                <option value="price-high" {{ request('sort') == 'price-high' ? 'selected' : '' }}>
                                    Harga Tertinggi
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100">
                            🔍 Cari
                        </button>

                        @if(request('search') || request('category') || request('sort'))
                            <a href="{{ route('public.catalog') }}" class="btn btn-light w-100 mt-2">
                                Reset Filter
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="card border-0 shadow-sm mt-3 text-center">
                <div class="card-body">
                    <p class="mb-0">
                        <strong style="color: var(--primary-color);">{{ $products->total() }}</strong>
                        produk ditemukan
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            @if($products->count() > 0)
                <!-- Products Grid -->
                <div class="row g-4">
                    @foreach($products as $product)
                        <div class="col-md-6 col-lg-4">
                            <div class="card product-card h-100">
                                <!-- Product Image -->
                                @if($product->media->first())
                                    <img src="{{ Storage::url($product->media->first()->path) }}" 
                                         alt="{{ $product->name }}" class="product-image">
                                @else
                                    <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @endif
                                
                                <!-- Product Info -->
                                <div class="product-body">
                                    <h6 class="product-name">
                                        <a href="{{ route('public.detail', $product->slug) }}" 
                                           class="text-decoration-none text-dark">
                                            {{ $product->name }}
                                        </a>
                                    </h6>
                                    
                                    @if($product->category)
                                        <small class="text-muted">
                                            📂 {{ $product->category->name }}
                                        </small><br>
                                    @endif
                                    
                                    <div class="product-price mt-2">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </div>
                                    
                                    <div class="product-stock small mb-2">
                                        @if($product->stock > 10)
                                            <span class="badge-stock">✓ Stok Tersedia ({{ $product->stock }})</span>
                                        @elseif($product->stock > 0)
                                            <span class="badge-stock low">⚠️ Stok Terbatas ({{ $product->stock }})</span>
                                        @else
                                            <span class="badge-stock low">❌ Stok Habis</span>
                                        @endif
                                    </div>

                                    @if($product->description)
                                        <p class="small text-muted mb-2">
                                            {{ Str::limit($product->description, 60) }}
                                        </p>
                                    @endif
                                    
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('public.detail', $product->slug) }}" 
                                           class="btn btn-primary-custom btn-sm">
                                            👁️ Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <nav class="mt-5">
                    {{ $products->links('pagination::bootstrap-5') }}
                </nav>
            @else
                <!-- No Products Found -->
                <div class="alert alert-warning text-center py-5">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <h5>Produk Tidak Ditemukan</h5>
                    <p class="text-muted mb-3">
                        Maaf, tidak ada produk yang sesuai dengan filter Anda. 
                        Silakan coba filter lain atau hubungi kami di WhatsApp.
                    </p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="{{ route('public.catalog') }}" class="btn btn-outline-primary-custom">
                            Lihat Semua Produk
                        </a>
                        <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" 
                           class="btn btn-primary-custom">
                            💬 Hubungi Kami
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Call to Action Section -->
<section class="py-60 mt-5" style="background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); color: white;">
    <div class="container text-center">
        <h3 class="mb-3">💕 Tidak Menemukan Produk Impian?</h3>
        <p class="mb-4">Kami siap membuat rangkaian custom sesuai keinginan Anda!</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('public.customRequest') }}" class="btn btn-light">
                📝 Order Custom
            </a>
            <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="btn btn-light">
                💬 Chat WhatsApp
            </a>
        </div>
    </div>
</section>

<style>
    .fw-600 {
        font-weight: 600;
    }

    .form-select, .form-control {
        border-radius: 8px;
        border-color: #ddd;
    }

    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
    }
</style>
@endsection
