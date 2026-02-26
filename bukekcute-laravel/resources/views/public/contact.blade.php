@extends('layouts.public')

@section('title', 'Kontak - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero mb-0">
    <div class="container">
        <h1>📞 Hubungi Kami</h1>
        <p>Kami siap menjawab setiap pertanyaan Anda</p>
    </div>
</section>

<div class="container py-60">
    <div class="row g-5">
        <!-- Contact Information -->
        <div class="col-lg-5">
            <h3 class="mb-4">📍 Informasi Kontak</h3>
            
            <!-- Address -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div style="font-size: 1.5rem; color: var(--primary-color);">📍</div>
                        <div>
                            <h6 class="mb-1">Alamat</h6>
                            <p class="text-muted small mb-0">
                                {{ env('STORE_ADDRESS', 'Jalan Sudirman No. 123, Jakarta Selatan 12345') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phone -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div style="font-size: 1.5rem; color: var(--primary-color);">📱</div>
                        <div>
                            <h6 class="mb-1">Telepon</h6>
                            <p class="text-muted small mb-0">
                                <a href="tel:{{ env('STORE_PHONE', '+62234567890') }}" class="text-decoration-none">
                                    {{ env('STORE_PHONE', '(02) 345-6789') }}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div style="font-size: 1.5rem; color: #25D366;">💬</div>
                        <div>
                            <h6 class="mb-1">WhatsApp</h6>
                            <p class="text-muted small mb-0">
                                <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="text-decoration-none">
                                    {{ env('STORE_WHATSAPP', '0812345678901') }}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div style="font-size: 1.5rem; color: var(--primary-color);">📧</div>
                        <div>
                            <h6 class="mb-1">Email</h6>
                            <p class="text-muted small mb-0">
                                <a href="mailto:{{ env('STORE_EMAIL', 'info@buketcute.com') }}" class="text-decoration-none">
                                    {{ env('STORE_EMAIL', 'info@buketcute.com') }}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Working Hours -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div style="font-size: 1.5rem; color: var(--primary-color);">⏰</div>
                        <div>
                            <h6 class="mb-2">Jam Operasional</h6>
                            <table class="small text-muted">
                                <tr>
                                    <td width="40%">Senin - Jumat</td>
                                    <td>09:00 - 18:00</td>
                                </tr>
                                <tr>
                                    <td>Sabtu</td>
                                    <td>09:00 - 17:00</td>
                                </tr>
                                <tr>
                                    <td>Minggu</td>
                                    <td>10:00 - 16:00</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <h6 class="mt-4 mb-3">🌐 Ikuti Kami</h6>
            <div class="d-flex gap-2">
                <a href="https://instagram.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-instagram"></i> Instagram
                </a>
                <a href="https://facebook.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
                <a href="https://tiktok.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-tiktok"></i> TikTok
                </a>
            </div>
        </div>

        <!-- Contact Form / Map -->
        <div class="col-lg-7">
            <!-- Quick Contact Buttons -->
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-whatsapp"></i> Chat WhatsApp
                    </a>
                </div>
                <div class="col-6">
                    <a href="tel:{{ env('STORE_PHONE', '+62234567890') }}" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-telephone"></i> Hubungi
                    </a>
                </div>
            </div>

            <!-- Map -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.9365038768893!2d106.80906631450259!3d-6.272711893645266!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f38c5c3c5c5d%3A0x5c3c5c5d5c5c5c5c!2sJakarta%2C%20Indonesia!5e0!3m2!1sid!2sid!4v1624000000000"
                            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" class="rounded-top"></iframe>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        <i class="bi bi-geo-alt"></i> Lokasi toko kami di {{ env('STORE_ADDRESS', 'Jakarta Selatan') }}. Klik tombol di atas untuk navigasi.
                    </p>
                </div>
            </div>

            <!-- Quick Contact Form -->
            <h4 class="mb-3">💬 Pesan Cepat</h4>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="quickContactForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" placeholder="Masukkan nama Anda" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor WhatsApp</label>
                            <input type="tel" class="form-control" id="phone" placeholder="08xxxxxxxxxx" required>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Pesan</label>
                            <textarea class="form-control" id="message" rows="4" placeholder="Tulis pesan Anda di sini..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100">
                            Kirim via WhatsApp
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4" role="alert">
                <i class="bi bi-info-circle"></i>
                <strong>💡 Tips:</strong> Untuk respon lebih cepat, hubungi kami melalui WhatsApp. Tim kami biasanya merespon dalam 5-10 menit.
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('quickContactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('name').value;
    const phone = document.getElementById('phone').value;
    const message = document.getElementById('message').value;

    // Clean phone number (remove non-digits)
    const cleanPhone = phone.replace(/\D/g, '');
    
    // Format for WhatsApp
    const whatsappMessage = `Nama: ${name}\nNomor: ${phone}\n\nPesan:\n${message}`;
    const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(whatsappMessage)}`;

    window.open(whatsappUrl, '_blank');
    
    // Reset form
    this.reset();
});
</script>
@endsection
