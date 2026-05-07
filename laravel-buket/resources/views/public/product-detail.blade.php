@extends('layouts.public')

@section('title', $product->name . ' - Buket Cute')

@section('content')
<!-- Breadcrumb -->
<div class="container mt-3 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('public.home') }}" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('public.catalog') }}" class="text-decoration-none">Katalog</a></li>
            @if($product->category)
                <li class="breadcrumb-item">
                    <a href="{{ route('public.catalog', ['category' => $product->category_id]) }}" class="text-decoration-none">
                        {{ $product->category->name }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

@php
    $waNumber = '083824665074';
    $waNumberClean = preg_replace('/[^0-9]/', '', $waNumber);
    if (substr($waNumberClean, 0, 1) === '0') {
        $waNumberClean = '62' . substr($waNumberClean, 1);
    }
@endphp

<div class="container py-4">
    <div class="row g-4">
        <!-- Product Media Section -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                @if($product->media->count() > 0)
                    {{-- Carousel Bootstrap --}}
                    <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                        @if($product->media->count() > 1)
                            <div class="carousel-indicators">
                                @foreach($product->media as $index => $media)
                                    <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="{{ $index }}" @if($index === 0) class="active" @endif></button>
                                @endforeach
                            </div>
                        @endif

                        <div class="carousel-inner">
                            @foreach($product->media as $index => $media)
                                @php
                                    $isVideo = $media->file_type === 'video';
                                    $isActive = $index === 0 ? 'active' : '';
                                @endphp
                                <div class="carousel-item {{ $isActive }}">
                                    @if($isVideo)
                                        <video class="d-block w-100" style="height: 400px; object-fit: cover;" autoplay muted loop playsinline>
                                            <source src="{{ Storage::url($media->file_path) }}" type="{{ $media->mime_type }}">
                                            Browser Anda tidak mendukung video.
                                        </video>
                                    @else
                                        <img src="{{ Storage::url($media->file_path) }}" 
                                             class="d-block w-100" 
                                             alt="{{ $product->name }}" 
                                             loading="lazy"
                                             style="height: 400px; object-fit: cover;">
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($product->media->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>

                    {{-- Thumbnail navigator (jika >1 media) --}}
                    @if($product->media->count() > 1)
                        <div class="row g-2 mt-2 px-2 pb-2">
                            @foreach($product->media as $index => $media)
                                <div class="col-3">
                                    <div class="img-thumbnail position-relative" 
                                         style="cursor: pointer; height: 80px; overflow: hidden;"
                                         data-bs-target="#productCarousel" data-bs-slide-to="{{ $index }}">
                                        @if($media->file_type === 'video')
                                            <video class="w-100 h-100" style="object-fit: cover;" muted>
                                                <source src="{{ Storage::url($media->file_path) }}">
                                            </video>
                                            <span class="position-absolute top-50 start-50 translate-middle text-white">
                                                <i class="bi bi-play-circle-fill" style="font-size: 1.5rem;"></i>
                                            </span>
                                        @else
                                            <img src="{{ Storage::url($media->file_path) }}" 
                                                 alt="thumbnail" 
                                                 class="w-100 h-100" 
                                                 style="object-fit: cover;" 
                                                 loading="lazy">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="card-body d-flex align-items-center justify-content-center" style="height: 400px; background-color: #f5f5f5;">
                        <div class="text-center text-muted">
                            <i class="bi bi-image" style="font-size: 4rem;"></i>
                            <p class="mt-2">Foto tidak tersedia</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-lg-7">
            <!-- Category -->
            @if($product->category)
                <span class="badge bg-light text-dark mb-2">
                    📂 {{ $product->category->name }}
                </span>
            @endif

            <!-- Name -->
            <h1 class="mb-2" style="font-size: 2rem; font-weight: 700;">{{ $product->name }}</h1>

            <!-- Rating -->
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2">
                    @for($i = 0; $i < 5; $i++)
                        <i class="bi bi-star-fill" style="color: #ffc107;"></i>
                    @endfor
                    <span class="text-muted small">(24 ulasan)</span>
                </div>
            </div>

            <!-- Price -->
            <div class="mb-3 p-3 bg-light rounded">
                <small class="text-muted d-block mb-1">Harga</small>
                <h2 style="color: var(--primary-color); font-weight: 700; margin: 0;">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </h2>
            </div>

            <!-- Stock Status -->
            <div class="mb-3">
                @if($product->stock > 10)
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <div>
                            <strong>Stok Tersedia</strong>
                            <small class="d-block">{{ $product->stock }} item siap dikirim</small>
                        </div>
                    </div>
                @elseif($product->stock > 0)
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Stok Terbatas</strong>
                            <small class="d-block">Hanya {{ $product->stock }} item tersisa</small>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-x-circle me-2"></i>
                        <div>
                            <strong>Stok Habis</strong>
                            <small class="d-block">Hubungi kami untuk pre-order</small>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quantity Selection & Order Button -->
            <div class="mb-4">
                <label for="quantity" class="form-label fw-600">Jumlah Pesanan</label>
                <div class="input-group mb-3">
                    <button class="btn btn-outline-secondary" type="button" id="decreaseQty">-</button>
                    <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="{{ $product->stock }}" readonly style="max-width: 100px;">
                    <button class="btn btn-outline-secondary" type="button" id="increaseQty">+</button>
                </div>
            </div>

            <!-- Total Price -->
            <div class="mb-4 p-3 bg-light rounded">
                <small class="text-muted d-block mb-1">Total Harga</small>
                <h3 id="totalPrice" style="color: var(--primary-color); font-weight: 700;">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </h3>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 mb-4">
                @if($product->stock > 0)
                    <button class="btn btn-primary-custom btn-lg" id="orderBtn">
                        💬 Pesan via WhatsApp
                    </button>
                    <a href="{{ route('public.customRequest') }}" class="btn btn-outline-primary-custom btn-lg">
                        ✏️ Minta Custom
                    </a>
                @else
                    <a href="https://wa.me/{{ $waNumberClean }}?text={{ urlencode('Halo, saya ingin menanyakan ketersediaan produk: ' . $product->name) }}" 
                       target="_blank" class="btn btn-primary-custom btn-lg">
                        💬 Tanyakan Ketersediaan
                    </a>
                @endif
            </div>

            <!-- Description -->
            @if($product->description)
                <div class="card border-0 bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title">📝 Deskripsi Produk</h6>
                        <p class="card-text" style="white-space: pre-wrap;">{{ $product->description }}</p>
                    </div>
                </div>
            @endif

            <!-- Product Details -->
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="card-title">ℹ️ Informasi Produk</h6>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="40%"><strong>SKU</strong></td>
                            <td>{{ $product->sku ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Kategori</strong></td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge bg-success">✓ Aktif</span>
                                @else
                                    <span class="badge bg-secondary">❌ Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if($related->count() > 0)
        <hr class="my-5">

        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="bi bi-stars"></i> Produk Serupa
                </h3>
            </div>
            @foreach($related as $relatedProduct)
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card product-card h-100">
                        @php
                            $relatedMedia = $relatedProduct->media->first();
                        @endphp
                        @if($relatedMedia)
                            <img src="{{ Storage::url($relatedMedia->file_path) }}" 
                                 alt="{{ $relatedProduct->name }}" 
                                 class="product-image" 
                                 loading="lazy">
                        @else
                            <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                            </div>
                        @endif
                        
                        <div class="product-body">
                            <h6 class="product-name">
                                <a href="{{ route('public.detail', $relatedProduct->slug) }}" class="text-decoration-none text-dark">
                                    {{ $relatedProduct->name }}
                                </a>
                            </h6>
                            
                            <div class="product-price">
                                Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                            </div>
                            
                            <a href="{{ route('public.detail', $relatedProduct->slug) }}" class="btn btn-sm btn-outline-primary-custom w-100">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- FAQ Section -->
<section class="py-60 mt-5 bg-light">
    <div class="container">
        <h3 class="mb-4 text-center">❓ Pertanyaan Umum</h3>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 shadow-sm mb-2">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Berapa lama proses pengerjaan?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Proses pengerjaan biasa memerlukan waktu 1-2 jam. Untuk pesanan custom, biasanya memerlukan waktu 2-4 jam tergantung kompleksitas desain.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-2">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Apakah ada biaya pengiriman?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Pengiriman GRATIS untuk area tertentu dalam radius 5km. Untuk di luar area, tersedia opsi pengiriman dengan biaya tambahan.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-2">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Bagaimana cara pembayaran?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Pembayaran dapat dilakukan melalui transfer bank, e-wallet (GCash, Dana, OVO), atau cash on delivery. Hubungi kami via WhatsApp untuk detail pembayaran.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productId = {{ $product->id }};
    const productPrice = {{ $product->price }};
    const productName = "{{ $product->name }}";
    const maxStock = {{ $product->stock }};
    const waNumber = "{{ $waNumberClean }}";

    const quantityInput = document.getElementById('quantity');
    const totalPriceEl = document.getElementById('totalPrice');
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');
    const orderBtn = document.getElementById('orderBtn');

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    function updateTotalPrice() {
        const qty = parseInt(quantityInput.value);
        const total = productPrice * qty;
        totalPriceEl.textContent = 'Rp ' + formatRupiah(total);
    }

    decreaseBtn.addEventListener('click', () => {
        const currentQty = parseInt(quantityInput.value);
        if (currentQty > 1) {
            quantityInput.value = currentQty - 1;
            updateTotalPrice();
        }
    });

    increaseBtn.addEventListener('click', () => {
        const currentQty = parseInt(quantityInput.value);
        if (currentQty < maxStock) {
            quantityInput.value = currentQty + 1;
            updateTotalPrice();
        }
    });

    // Tombol WhatsApp langsung tanpa AJAX
    orderBtn.addEventListener('click', () => {
        const qty = parseInt(quantityInput.value);
        const total = productPrice * qty;
        
        const message = `Halo, saya ingin memesan:
*Produk:* ${productName}
*Jumlah:* ${qty}
*Harga satuan:* Rp ${formatRupiah(productPrice)}
*Total:* Rp ${formatRupiah(total)}

Apakah masih tersedia?`;
        
        const waUrl = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank');
    });
});
</script>

<style>
    .fw-600 {
        font-weight: 600;
    }

    .accordion-button:not(.collapsed) {
        background-color: rgba(233, 30, 99, 0.1);
        color: var(--primary-color);
    }

    .accordion-button:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(233, 30, 99, 0.25);
    }

    .carousel-item img, .carousel-item video {
        border-radius: 15px 15px 0 0;
    }

    .img-thumbnail {
        border-radius: 8px;
    }
</style>
@endsection