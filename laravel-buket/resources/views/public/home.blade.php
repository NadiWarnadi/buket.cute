@extends('layouts.public')

@section('title', 'Beranda - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero-elegant">
    <div class="container">
        <h1>🌸 Buket Cute</h1>
        <p>Rangkaian bunga segar & elegan untuk momen istimewa Anda</p>
        <a href="{{ route('public.catalog') }}" class="btn btn-luxury">
            Lihat Katalog Produk
        </a>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-60">
    <div class="container">
        <h2 class="section-title">Produk Unggulan</h2>
        
        @if($featured->count() > 0)
            <div class="row g-4">
                @foreach($featured as $product)
                    <div class="col-md-6 col-lg-3">
                        <div class="card product-card">
                            {{-- Gambar produk --}}
                            @if($product->media->first())
                                <div class="product-image-container">
                                    <img src="{{ $product->media->first()->getUrl() }}" alt="{{ $product->name }}">
                                </div>
                            @else
                                <div class="product-image-container d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                </div>
                            @endif
                            
                            <div class="product-body">
                                <h6 class="product-name">
                                    <a href="{{ route('public.detail', $product->slug) }}">
                                        {{ $product->name }}
                                    </a>
                                </h6>
                                
                                <div class="product-price">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </div>
                                
                                <div class="mb-2">
                                    @if($product->stock > 10)
                                        <span class="badge-stock">Stok Tersedia</span>
                                    @elseif($product->stock > 0)
                                        <span class="badge-stock low">Stok Terbatas</span>
                                    @else
                                        <span class="badge-stock low">Stok Habis</span>
                                    @endif
                                </div>
                                
                                <a href="{{ route('public.detail', $product->slug) }}" class="btn btn-outline-luxury btn-sm w-100">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center">
                Produk unggulan sedang dipersiapkan. Silakan kembali lagi nanti.
            </div>
        @endif
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-60 bg-light">
    <div class="container">
        <h2 class="section-title">Mengapa Memilih Kami?</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">🌹</div>
                    <h5>Bunga Segar</h5>
                    <p class="text-muted">Semua bunga kami dipilih langsung dari taman terbaik dan dijamin segar.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">⚡</div>
                    <h5>Pengiriman Cepat</h5>
                    <p class="text-muted">Pengiriman gratis untuk area tertentu dalam 2-4 Hari setelah pemesanan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">🎨</div>
                    <h5>Desain Menarik</h5>
                    <p class="text-muted">Desain custom sesuai preferensi Anda dengan sentuhan personal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products Section -->
<section class="py-60">
    <div class="container">
        <h2 class="section-title">Produk Terbaru</h2>
        
        @if($latest->count() > 0)
            <div class="row g-4">
                @foreach($latest as $product)
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card product-card">
                            @if($product->media->first())
                                <div class="product-image-container">
                                    <img src="{{ $product->media->first()->getUrl() }}" alt="{{ $product->name }}">
                                </div>
                            @else
                                <div class="product-image-container d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            @endif
                            
                            <div class="product-body p-2">
                                <h6 class="product-name small">
                                    <a href="{{ route('public.detail', $product->slug) }}">
                                        {{ Str::limit($product->name, 15) }}
                                    </a>
                                </h6>
                                <div class="product-price small">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </div>
                                <a href="{{ route('public.detail', $product->slug) }}" class="btn btn-outline-luxury btn-sm w-100 py-1" style="font-size: 0.75rem;">
                                    Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('public.catalog') }}" class="btn btn-luxury">
                    Lihat Semua Produk →
                </a>
            </div>
        @endif
    </div>
</section>

<!-- Categories Preview Section -->
@if($categories->count() > 0)
    <section class="py-60 bg-light">
        <div class="container">
            <h2 class="section-title">Kategori Produk</h2>
            <div class="row g-3">
                @foreach($categories as $category)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('public.catalog', ['category' => $category->id]) }}" 
                           class="card h-100 text-decoration-none text-center p-4 border-0 shadow-sm category-card">
                            <div style="font-size: 2.5rem; margin-bottom: 1rem;">📦</div>
                            <h6 class="text-dark">{{ $category->name }}</h6>
                            <small class="text-muted">{{ $category->products_count }} produk</small>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- CTA Section -->
<section class="py-60 cta-luxury">
    <div class="container text-center">
        <h2 class="mb-3">Tidak Menemukan Produk yang Anda Cari?</h2>
        <p class="mb-4">Kami juga menerima pesanan custom untuk kebutuhan spesial Anda.</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('public.customRequest') }}" class="btn btn-outline-luxury">
                📝 Order Custom
            </a>
            <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="btn btn-luxury">
                💬 Chat WhatsApp
            </a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-60">
    <div class="container">
        <h2 class="section-title">Testimoni Pelanggan</h2>
        <div class="row g-4">
            @for($i = 1; $i <= 3; $i++)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 testimonial-card">
                        <div class="card-body">
                            <div class="mb-2">
                                @for($j = 0; $j < 5; $j++)
                                    <i class="bi bi-star-fill" style="color: var(--gold);"></i>
                                @endfor
                            </div>
                            <p class="card-text mb-3">
                                "Bunga sangat segar dan indah! Desainnya sesuai dengan yang saya minta. Sangat puas dengan pelayanannya! 🌹"
                            </p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-secondary" style="width: 40px; height: 40px;"></div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Pelanggan Buket Cute</h6>
                                    <small class="text-muted">Pelanggan Setia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</section>
@endsection

@section('extra-css')
<style>
    /* Tambahan styling untuk halaman depan */
    .category-card {
        border-radius: 20px;
        transition: all 0.4s ease;
    }
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        border: 1px solid var(--gold);
    }
    .testimonial-card {
        border-radius: 20px;
        transition: all 0.4s ease;
    }
    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    }
    /* CTA Luxury background */
    .cta-luxury {
        background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
        color: #fff;
        border-top: 2px solid var(--gold);
        border-bottom: 2px solid var(--gold);
    }
    .cta-luxury h2 {
        color: var(--gold);
        font-family: 'Playfair Display', serif;
    }
</style>
@endsection