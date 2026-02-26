@extends('layouts.public')

@section('title', 'Tentang Kami - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero mb-0">
    <div class="container">
        <h1>🌸 Tentang Kami</h1>
        <p>Mengenal lebih jauh tentang Buket Cute</p>
    </div>
</section>

<!-- Story Section -->
<section class="py-60">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div style="background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); height: 400px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <div class="text-center">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">🌹</div>
                        <p style="font-size: 1.2rem; font-weight: 600;">Buket Cute</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="mb-3">🎀 Cerita Kami</h2>
                <p class="text-muted mb-3">
                    Buket Cute didirikan pada tahun 2023 dengan misi sederhana: memberikan kebahagiaan melalui keindahan bunga segar berkualitas tinggi. Kami percaya bahwa setiap momen spesial layak untuk dirayakan dengan sesuatu yang istimewa.
                </p>
                <p class="text-muted mb-3">
                    Dimulai dari sebuah passion kecil, kini kami telah melayani ribuan pelanggan yang puas. Setiap rangkaian yang kami buat adalah karya seni yang dibuat dengan cinta dan perhatian terhadap detail.
                </p>
                <p class="text-muted">
                    Tim kami terdiri dari florist profesional yang berpengalaman dan berdedikasi untuk menciptakan arrangemnet bunga yang tidak hanya indah, tetapi juga bermakna dalam setiap kesempatan.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-60 bg-light">
    <div class="container">
        <h2 class="section-title">💎 Nilai-Nilai Kami</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🌹</div>
                        <h5>Kualitas Premium</h5>
                        <p class="text-muted small">
                            Kami hanya menggunakan bunga segar pilihan langsung dari taman terbaik untuk menjamin kualitas terbaik.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                        <h5>Pelayanan Terbaik</h5>
                        <p class="text-muted small">
                            Kepuasan pelanggan adalah prioritas utama kami. Tim kami siap membantu Anda 24/7 melalui WhatsApp.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body p-4">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div>
                        <h5>Kreativitas Tak Terbatas</h5>
                        <p class="text-muted small">
                            Setiap desain unik dan dibuat sesuai preferensi Anda dengan sentuhan kreatif tim kami.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-60">
    <div class="container">
        <h2 class="section-title">👥 Tim Kami</h2>
        
        <div class="row g-4">
            @for($i = 1; $i <= 4; $i++)
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <div class="card-body">
                            <div class="rounded-circle bg-secondary mx-auto mb-3" style="width: 100px; height: 100px;"></div>
                            <h6>Anggota Tim {{ $i }}</h6>
                            <small class="text-muted d-block mb-2">Florist Professional</small>
                            <p class="small text-muted mb-0">
                                Berpengalaman lebih dari 5 tahun dalam industri floral design.
                            </p>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-60 bg-light">
    <div class="container">
        <h2 class="section-title">✨ Mengapa Memilih Buket Cute?</h2>
        
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Bunga Segar Setiap Hari</h6>
                        <p class="text-muted small mb-0">Kami selalu menyediakan bunga segar yang ditarik langsung dari supplier terpercaya.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Pengiriman Cepat & Tepat Waktu</h6>
                        <p class="text-muted small mb-0">Pengiriman gratis untuk area tertentu dalam 2-4 jam setelah pemesanan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Desain Custom Unlimited</h6>
                        <p class="text-muted small mb-0">Kami menerima permintaan custom untuk setiap kebutuhan dan budget Anda.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Harga Kompetitif</h6>
                        <p class="text-muted small mb-0">Harga yang transparan dan kompetitif tanpa biaya tersembunyi.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Garansi Kepuasan 100%</h6>
                        <p class="text-muted small mb-0">Jika bunganya layu setelah 1 hari, kami akan menggantinya dengan gratis.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3 mb-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">✓</div>
                    <div>
                        <h6>Layanan 24/7</h6>
                        <p class="text-muted small mb-0">Tim kami siap melayani pertanyaan Anda kapan saja melalui WhatsApp.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-60" style="background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); color: white;">
    <div class="container text-center">
        <h2 class="mb-3">💌 Hubungi Kami</h2>
        <p class="mb-4">Siap membantu untuk pesanan Anda hari ini!</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('public.catalog') }}" class="btn btn-light">
                📚 Lihat Katalog
            </a>
            <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="btn btn-light">
                💬 Chat WhatsApp
            </a>
        </div>
    </div>
</section>
@endsection
