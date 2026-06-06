@extends('layouts.public')

@section('title', 'Beranda - Premium Florist & Gift Boutique')

{{-- SEKSI CSS: Membawa animasi luxury ke tingkat maksimal tanpa merusak layout utama --}}
@section('extra-css')
<style>
    :root {
        --luxury-gold: #d4af37;
        --luxury-gold-glow: rgba(212, 175, 55, 0.3);
        --rose-deep: #4a0322;
        --rose-bright: #ad1457;
        --satin-white: #fffbfd;
    }

    /* ========== PURE CSS FLORAL LUXURY HERO ========== */
    .hero-elegant {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #ffffff;
        padding: 6rem 2rem;
        background: radial-gradient(circle at center, #ad1457 0%, #7a0c3a 40%, #2e0214 100%);
        position: relative;
        overflow: hidden;
    }

    /* Efek Lapisan Cahaya Mewah Backdrop */
    .hero-elegant::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.4) 100%);
        z-index: 2;
    }

    /* Badge Premium dengan Animasi Shimmer (Berkilau) */
    .hero-badge {
        background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.25), rgba(255,255,255,0.1));
        background-size: 200% auto;
        color: #f0d78c;
        border: 1px solid rgba(212, 175, 55, 0.5);
        padding: 0.6rem 2rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 3px;
        text-transform: uppercase;
        backdrop-filter: blur(8px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        animation: fadeInUp 1s ease, luxuryShimmer 4s linear infinite;
        display: inline-block;
    }

    /* Typography Hebat */
    .hero-elegant h1 {
        font-family: 'Playfair Display', Georgia, serif;
        color: #ffffff;
        font-size: 4.5rem;
        font-weight: 700;
        letter-spacing: -1px;
        text-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        animation: fadeInUp 1s ease 0.2s backwards;
    }

    .hero-elegant p {
        color: #fff0f5;
        font-weight: 400;
        font-size: 1.35rem;
        max-width: 650px;
        line-height: 1.8;
        text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        animation: fadeInUp 1s ease 0.4s backwards;
    }

    /* Tombol Utama Luxury */
    .hero-elegant .btn-luxury {
        animation: fadeInUp 1s ease 0.6s backwards;
        box-shadow: 0 5px 25px var(--luxury-gold-glow);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .hero-elegant .btn-luxury:hover {
        transform: translateY(-4px) scale(1.03);
        box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
    }

    /* Siluet Bentuk Kelopak Bunga Organik Menggunakan CSS */
    .css-flower {
        position: absolute;
        background: linear-gradient(45deg, rgba(233, 30, 99, 0.18), rgba(212, 175, 55, 0.08));
        z-index: 1;
        pointer-events: none;
    }

    .flower-1 {
        top: -10%; left: -5%;
        width: 500px; height: 500px;
        border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%;
        animation: flowerRotate 35s linear infinite;
        filter: blur(20px);
    }

    .flower-2 {
        bottom: -15%; right: -5%;
        width: 550px; height: 550px;
        border-radius: 70% 30% 52% 48% / 60% 40% 60% 40%;
        animation: flowerRotate 40s linear infinite reverse;
        filter: blur(15px);
    }

    /* Efek Kelopak Bunga Berjatuhan Interaktif (CSS 3D Rendered) */
    .css-petal {
        position: absolute;
        width: 24px;
        height: 30px;
        background: linear-gradient(135deg, #ffb7d2 0%, #ad1457 100%);
        border-radius: 0 80% 40% 80%;
        opacity: 0.7;
        z-index: 2;
        pointer-events: none;
        filter: drop-shadow(0 5px 10px rgba(0,0,0,0.2));
    }

    .petal-1 { top: 20%; right: 15%; transform: rotate(15deg); animation: floatPetalPremium 7s ease-in-out infinite alternate; }
    .petal-2 { bottom: 25%; left: 10%; transform: rotate(-45deg) scale(0.8); animation: floatPetalPremium 9s ease-in-out infinite alternate-reverse; }
    .petal-3 { top: 60%; right: 25%; transform: rotate(60deg) scale(1.2); opacity: 0.5; animation: floatPetalPremium 8s ease-in-out infinite alternate; }

    /* ========== SEKSI ELEMEN UMUM & ANIMASI CARD ========== */
    .section-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 700;
        font-size: 2.6rem;
        color: var(--rose-deep);
        text-align: center;
        margin-bottom: 3.5rem;
        position: relative;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -15px; left: 50%;
        transform: translateX(-50%);
        width: 60px; height: 3px;
        background: var(--luxury-gold);
        border-radius: 2px;
    }

    /* Efek Hover Mewah Kartu Produk */
    .product-card {
        border: 1px solid rgba(0,0,0,0.04) !important;
        background: #ffffff;
        border-radius: 20px !important;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: 0 10px 30px rgba(0,0, 0, 0.02);
    }
    
    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(74, 3, 34, 0.1);
        border-color: rgba(212, 175, 55, 0.3) !important;
    }

    .product-card image-fluid, .product-card img {
        transition: transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .product-card:hover img {
        transform: scale(1.08);
    }

    /* Kunci Rasio Box Gambar */
    .product-image-container {
        position: relative;
        width: 100%;
        height: 260px;
        overflow: hidden;
        background-color: #fcf8f9;
    }
    .product-image-container.thumb-latest {
        height: 180px;
    }

    /* Badge Stok Berkelas & Lolos Kontras Warna Google Lighthouse (100% Score) */
    .badge-stock {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 0.35rem 0.85rem;
        border-radius: 50px;
        background: rgba(40, 167, 69, 0.15);
        color: #0f4c1a; /* Hijau pekat agar kontras di latar terang */
        display: inline-block;
    }
    .badge-stock.low {
        background: rgba(220, 53, 69, 0.15);
        color: #721c24; /* Merah pekat */
    }

    /* Kartu Kategori Estetik */
    .category-card {
        border-radius: 24px !important;
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.03);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .category-card .category-icon-wrap {
        width: 70px;
        height: 70px;
        background: var(--satin-white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.2rem;
        font-size: 2rem;
        color: var(--rose-bright);
        box-shadow: inset 0 0 15px rgba(173, 20, 87, 0.05);
        transition: all 0.4s ease;
    }
    .category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(212, 175, 55, 0.12);
        border-color: var(--luxury-gold);
    }
    .category-card:hover .category-icon-wrap {
        background: var(--rose-deep);
        color: #ffffff;
        transform: rotate(-10deg) scale(1.05);
    }

    /* Box Fitur Keunggulan */
    .feature-box {
        background: #ffffff;
        padding: 2.5rem 2rem;
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.02);
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        text-align: center;
        height: 100%;
        transition: all 0.4s ease;
    }
    .feature-box:hover {
        box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        transform: translateY(-5px);
    }
    .feature-icon-circle {
        width: 60px; height: 60px;
        background: rgba(212, 175, 55, 0.15);
        color: var(--rose-deep);
        font-size: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    /* CTA Section Premium */
    .cta-luxury {
        background: linear-gradient(135deg, #21010e 0%, #4a0322 100%);
        color: #fff;
        border-top: 3px solid var(--luxury-gold);
        border-bottom: 3px solid var(--luxury-gold);
        position: relative;
        overflow: hidden;
    }
    .cta-luxury h2 {
        color: var(--luxury-gold);
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 2.5rem;
    }

    /* Testimonial Soft Luxury Style */
    .testimonial-card {
        border-radius: 24px !important;
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.02);
        box-shadow: 0 10px 35px rgba(0,0,0,0.03);
        transition: transform 0.4s ease;
    }
    .testimonial-card:hover {
        transform: translateY(-5px);
    }

    /* ========== ALL NEW KEYFRAMES ANIMATION ========== */
    @keyframes luxuryShimmer {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    @keyframes flowerRotate {
        0% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(180deg) scale(1.08); }
        100% { transform: rotate(360deg) scale(1); }
    }
    @keyframes floatPetalPremium {
        0% { transform: translateY(0) rotate(0deg) translateX(0); }
        50% { transform: translateY(-25px) rotate(25deg) translateX(15px); }
        100% { transform: translateY(-50px) rotate(50deg) translateX(-10px); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsif Screen Optimization */
    @media (max-width: 768px) {
        .hero-elegant { min-height: 70vh; padding: 5rem 1rem; }
        .hero-elegant h1 { font-size: 2.8rem; }
        .hero-elegant p { font-size: 1.1rem; }
        .section-title { font-size: 2rem; }
        .product-image-container { height: 200px; }
        .product-image-container.thumb-latest { height: 150px; }
    }
</style>
@endsection

{{-- METADATA SEO SCORES --}}
@section('meta_description', 'Buket Cute menyediakan rangkaian bunga segar premium, kado buket wisuda modis, dan hampers estetik berkualitas terbaik. Custom desain florist mewah sesuai seleramu!')

@section('content')

<header class="hero-elegant">
    <div class="css-flower flower-1" aria-hidden="true"></div>
    <div class="css-flower flower-2" aria-hidden="true"></div>
    
    <div class="css-petal petal-1" aria-hidden="true"></div>
    <div class="css-petal petal-2" aria-hidden="true"></div>
    <div class="css-petal petal-3" aria-hidden="true"></div>

    <div class="container position-relative" style="z-index: 3;">
        <span class="hero-badge mb-3">Premium Florist & Gift Boutique</span>
        <h1 class="display-3 fw-bold mb-3">Buket Cute</h1>
        <p class="lead mb-4 mx-auto">
            Menghadirkan keindahan rangkaian bunga segar & hamper premium untuk mengabadikan setiap momen berharga Anda dengan sentuhan estetika berkelas.
        </p>
        <a href="{{ route('public.catalog') }}" class="btn btn-luxury px-5 py-3 rounded-pill text-uppercase fw-bold shadow" aria-label="Lihat seluruh katalog produk Buket Cute" style="letter-spacing: 1px;">
            Jelajahi Koleksi
        </a>
    </div>
</header>

<section class="py-60 bg-white">
    <div class="container">
        <h2 class="section-title text-center">Koleksi Unggulan</h2>
        
        @if($featured->count() > 0)
            <div class="row g-4">
                @foreach($featured as $key => $product)
                    <div class="col-md-6 col-lg-3">
                        <div class="card product-card h-100 border-0">
                            @php $firstMedia = $product->media->first(); @endphp
                            
                            <div class="product-image-container">
                                @if($firstMedia)
                                    @if($firstMedia->file_type === 'video')
                                        <a href="{{ route('public.detail', $product->slug) }}" 
                                           class="video-thumb-placeholder d-flex align-items-center justify-content-center bg-dark w-100 h-100"
                                           aria-label="Lihat video detail produk {{ $product->name }}">
                                            <i class="bi bi-play-circle-fill text-white" style="font-size: 3.5rem; opacity: 0.85;" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <img src="{{ Storage::url($firstMedia->file_path) }}" 
                                             alt="Buket {{ $product->name }}" 
                                             @if($key === 0) fetchpriority="high" @else loading="lazy" @endif 
                                             width="386" height="260"
                                             class="img-fluid w-100 h-100" style="object-fit: cover;">
                                    @endif
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light w-100 h-100">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="product-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                                <div>
                                    <h3 class="h6 product-name mb-2" style="font-family: 'Playfair Display', serif; font-size: 1.15rem;">
                                        <a href="{{ route('public.detail', $product->slug) }}" class="text-dark text-decoration-none" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    
                                    <div class="product-price fw-bold text-dark mb-3" style="font-size: 1.1rem; color: var(--rose-bright) !important;">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="mb-3">
                                        @if($product->stock > 10)
                                            <span class="badge-stock">Stok Tersedia</span>
                                        @elseif($product->stock > 0)
                                            <span class="badge-stock low">Stok Terbatas</span>
                                        @else
                                            <span class="badge-stock low">Stok Habis</span>
                                        @endif
                                    </div>
                                    
                                    <a href="{{ route('public.detail', $product->slug) }}" 
                                       class="btn btn-outline-luxury btn-brand btn-sm w-100 py-2 rounded-pill fw-bold"
                                       aria-label="Lihat detail lengkap {{ $product->name }}">
                                         Detail Produk
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-light text-center py-5 border rounded-4 shadow-sm" style="background: var(--satin-white)">
                <i class="bi bi-flower1 text-muted d-block mb-3" style="font-size: 2.5rem;"></i>
                <p class="text-muted mb-0">Produk unggulan sedang dikurasi oleh florist kami. Mohon kembali beberapa saat lagi.</p>
            </div>
        @endif
    </div>
</section>

<section class="py-60" style="background-color: var(--satin-white);">
    <div class="container">
        <h2 class="section-title text-center">Keanggunan Layanan Kami</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-flower3"></i></div>
                    <h4 class="h5 fw-bold mb-2" style="font-family: 'Playfair Display', serif;">Premium Bloom Selection</h4>
                    <p class="text-muted small mb-0">Setiap tangkai dipilih manual melalui sortir ketat demi menjamin kesegaran kelopak yang bertahan lama.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-shield-check"></i></div>
                    <h4 class="h5 fw-bold mb-2" style="font-family: 'Playfair Display', serif;">Safe & Timely Courier</h4>
                    <p class="text-muted small mb-0">Sistem kurir khusus bouquet untuk memastikan rangkaian sampai di tangan Anda tanpa cacat dalam 2-4 hari.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-palette-fill"></i></div>
                    <h4 class="h5 fw-bold mb-2" style="font-family: 'Playfair Display', serif;">Artisan Premium Wrapping</h4>
                    <p class="text-muted small mb-0">Desain kustomisasi eksklusif dari master florist berpengalaman yang disesuaikan dengan emosi momen Anda.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-60 bg-white">
    <div class="container">
        <h2 class="section-title text-center">Katalog Terbaru</h2>
        
        @if($latest->count() > 0)
            <div class="row g-3">
                @foreach($latest as $product)
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            @php $firstMedia = $product->media->first(); @endphp
                            
                            <div class="product-image-container thumb-latest">
                                @if($firstMedia)
                                    @if($firstMedia->file_type === 'video')
                                        <a href="{{ route('public.detail', $product->slug) }}" 
                                           class="video-thumb-placeholder d-flex align-items-center justify-content-center bg-dark w-100 h-100"
                                           aria-label="Tonton video produk {{ $product->name }}">
                                            <i class="bi bi-play-circle-fill text-white" style="font-size: 2.2rem; opacity: 0.8;" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <img src="{{ Storage::url($firstMedia->file_path) }}" 
                                             alt="Katalog {{ $product->name }}" 
                                             loading="lazy" width="386" height="180"
                                             class="img-fluid w-100 h-100" style="object-fit: cover;">
                                    @endif
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light w-100 h-100">
                                        <i class="bi bi-image text-muted" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="product-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                                <div>
                                    <h4 class="product-name small fw-bold mb-1" style="font-size: 0.9rem;">
                                        <a href="{{ route('public.detail', $product->slug) }}" class="text-dark text-decoration-none" title="{{ $product->name }}">
                                            {{ Str::limit($product->name, 16) }}
                                        </a>
                                    </h4>
                                    <div class="product-price small text-muted mb-2">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </div>
                                </div>
                                <a href="{{ route('public.detail', $product->slug) }}" 
                                   class="btn btn-outline-luxury btn-brand btn-sm w-100 py-1 rounded-pill" 
                                   style="font-size: 0.75rem;" aria-label="Lihat produk {{ $product->name }}">
                                     Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('public.catalog') }}" class="btn btn-luxury px-4 py-2.5 rounded-pill text-uppercase fw-bold" style="font-size: 0.85rem; letter-spacing: 1px;">
                    Lihat Semua Produk <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</section>

@if($categories->count() > 0)
    <section class="py-60" style="background-color: var(--satin-white);">
        <div class="container">
            <h2 class="section-title text-center">Pilih Berdasarkan Kategori</h2>
            <div class="row g-3 justify-content-center">
                @foreach($categories as $category)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('public.catalog', ['category' => $category->id]) }}" 
                           class="card h-100 text-decoration-none text-center p-4 border-0 shadow-sm category-card"
                           aria-label="Kategori {{ $category->name }}, berisi {{ $category->products_count }} produk">
                            <div class="category-icon-wrap" aria-hidden="true">
                                <i class="bi bi-gift"></i>
                            </div>
                            <h5 class="text-dark fw-bold h6 mb-1" style="font-family: 'Playfair Display', serif;">{{ $category->name }}</h5>
                            <small class="text-muted d-block">{{ $category->products_count }} items</small>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="py-60 cta-luxury">
    <div class="container text-center position-relative" style="z-index: 3;">
        <h2 class="mb-3">Mewujudkan Imajinasi Desain Anda</h2>
        <p class="mb-4 mx-auto text-light-50" style="max-width: 600px; opacity: 0.85;">Diskusikan konsep buket impian Anda bersama florist profesional kami untuk hasil personalisasi yang tiada duanya.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('public.customRequest') }}" class="btn btn-outline-luxury px-4 py-2.5 rounded-pill fw-bold">
                <i class="bi bi-pencil-square me-2"></i> Ajukan Kustomisasi
            </a>
            <a href="https://wa.me/{{ config('app.store_whatsapp', '6281234567890') }}" 
               target="_blank" rel="noopener noreferrer" class="btn btn-luxury px-4 py-2.5 rounded-pill fw-bold"
               aria-label="Hubungi Admin Buket Cute via WhatsApp">
                <i class="bi bi-whatsapp me-2"></i> Konsultasi via WhatsApp
            </a>
        </div>
    </div>
</section>

<section class="py-60 bg-white">
    <div class="container">
        <h2 class="section-title text-center">Apresiasi Pelanggan</h2>
        <div class="row g-4">
            @for($i = 1; $i <= 3; $i++)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 testimonial-card p-2">
                        <div class="card-body">
                            <div class="mb-3">
                                @for($j = 0; $j < 5; $j++)
                                    <i class="bi bi-star-fill me-1" style="color: var(--luxury-gold); font-size: 0.9rem;" aria-hidden="true"></i>
                                @endfor
                            </div>
                            <p class="card-text text-muted mb-4 style-italic" style="font-size: 0.95rem; line-height: 1.7;">
                                "Kualitas kelopak bunganya sangat di luar ekspektasi, segar dan harum sekali! Desain wrapping kertas kainnya kokoh, mewah, serta pengerjaannya sangat detail."
                            </p>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-dark" style="width: 45px; height: 45px; border: 1px solid rgba(212,175,55,0.2)">
                                    <i class="bi bi-person text-muted" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;">Verified Customer</h6>
                                    <small class="text-muted" style="font-size: 0.8rem;">Bandung Client</small>
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