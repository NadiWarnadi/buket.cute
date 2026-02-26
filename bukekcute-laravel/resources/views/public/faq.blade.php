@extends('layouts.public')

@section('title', 'FAQ - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero mb-0">
    <div class="container">
        <h1>❓ FAQ & Cara Pemesanan</h1>
        <p>Jawaban atas pertanyaan yang sering diajukan</p>
    </div>
</section>

<div class="container py-60">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Cara Pemesanan Section -->
            <h3 class="mb-4">📋 Cara Pemesanan</h3>
            
            <div class="step mb-4" style="display: flex; gap: 1rem;">
                <div style="min-width: 50px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                        1
                    </div>
                </div>
                <div>
                    <h5>Pilih Produk Dari Katalog</h5>
                    <p class="text-muted">Jelajahi katalog kami dan pilih rangkaian bunga yang Anda sukai. Anda juga bisa melihat foto produk dan deskripsi detail.</p>
                </div>
            </div>

            <div class="step mb-4" style="display: flex; gap: 1rem;">
                <div style="min-width: 50px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                        2
                    </div>
                </div>
                <div>
                    <h5>Tentukan Jumlah & Klik "Pesan via WhatsApp"</h5>
                    <p class="text-muted">Pilih jumlah yang Anda inginkan, kemudian klik tombol "Pesan via WhatsApp" untuk melanjutkan.</p>
                </div>
            </div>

            <div class="step mb-4" style="display: flex; gap: 1rem;">
                <div style="min-width: 50px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                        3
                    </div>
                </div>
                <div>
                    <h5>Konfirmasi Pesanan via WhatsApp</h5>
                    <p class="text-muted">Chat akan terbuka ke WhatsApp kami. Konfirmasi detail pesanan Anda, beritahu preferensi warna, dan tanggal pengiriman yang diinginkan.</p>
                </div>
            </div>

            <div class="step mb-4" style="display: flex; gap: 1rem;">
                <div style="min-width: 50px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                        4
                    </div>
                </div>
                <div>
                    <h5>Lakukan Pembayaran</h5>
                    <p class="text-muted">Kami akan memberikan nomor rekening atau metode pembayaran lainnya (GCash, Dana, OVO). Pembayaran dapat dilakukan sebelum atau saat pengiriman.</p>
                </div>
            </div>

            <div class="step mb-4" style="display: flex; gap: 1rem;">
                <div style="min-width: 50px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e91e63 0%, #f06292 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                        5
                    </div>
                </div>
                <div>
                    <h5>Terima Pesanan Anda</h5>
                    <p class="text-muted">Pesanan akan dikerjakan dan dikirim tepat pada waktu yang dijanjikan. Driver kami akan menghubungi Anda sebelum tiba.</p>
                </div>
            </div>

            <hr class="my-5">

            <!-- FAQ Section -->
            <h3 class="mb-4">❓ Pertanyaan yang Sering Diajukan</h3>

            <div class="accordion" id="faqAccordion">
                <!-- Q1 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Berapa lama proses pemesanan hingga pengiriman?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Proses pengiriman tergantung pada waktu pemesanan:</p>
                            <ul>
                                <li><strong>Pesanan pagi (sebelum 12:00):</strong> Pengiriman sore hari (13:00-17:00)</li>
                                <li><strong>Pesanan sore (12:00-17:00):</strong> Pengiriman besok pagi (09:00-12:00)</li>
                                <li><strong>Pesanan malam (setelah 17:00):</strong> Pengiriman besok sore (13:00-17:00)</li>
                            </ul>
                            <p class="mt-2">Untuk custom order, estimasi waktu adalah 2-4 jam tambahan.</p>
                        </div>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Apakah ada biaya pengiriman?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>GRATIS pengiriman untuk:</strong></p>
                            <ul>
                                <li>Area indramayu kota radius 5 km</li>
                                <li>Pemesanan di atas Rp 500.000</li>
                            </ul>
                            <p class="mt-2"><strong>Biaya pengiriman tambahan:</strong></p>
                            <ul>
                                <li>jemput sendiri grais</li>
                                <li>Area indramayu kota (5-10 km): Rp 25.000</li>
                                <li>Area indramayu lainnya: Rp 50.000</li>
                                <li>Area sekitarnya: Hubungi kami untuk harga</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Bagaimana cara pembayaran?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>Kami menerima pembayaran melalui:</strong></p>
                            <ul>
                                <li>💳 Transfer Bank (BCA, Mandiri, BNI)</li>
                                <li>📱 E-wallet (GCash, Dana, OVO, Spay)</li>
                                <li>💵 Cash on Delivery (untuk area tertentu)</li>
                            </ul>
                            <p class="mt-2">Pembayaran dapat dilakukan sebelum atau saat pengiriman. Akan ada invoice yang dikirim via WhatsApp.</p>
                        </div>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Dapatkah saya melakukan perubahan pesanan setelah memesan?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Bergantung pada waktu:</p>
                            <ul>
                                <li><strong>Dalam 30 menit setelah memesan:</strong> Perubahan dapat dilakukan tanpa biaya tambahan</li>
                                <li><strong>30 menit - 1 jam:</strong> Perubahan terbatas (color change saja)</li>
                                <li><strong>Lebih dari 1 jam:</strong> Pekerjaan sudah dimulai, perubahan tidak dapat dilakukan</li>
                            </ul>
                            <p class="mt-2">Hubungi kami segera via WhatsApp jika ingin membuat perubahan.</p>
                        </div>
                    </div>
                </div>

                <!-- Q5 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Bagaimana jika bunga layu setelah terima?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Kami memberikan <strong>Garansi Kesegaran 24 jam</strong>. Jika bunga layu dalam 24 jam setelah diterima, kami akan menggantinya dengan gratis (untuk area pengiriman gratis).</p>
                            <p>Cara merawat agar bunga tahan lama:</p>
                            <ul>
                                <li>Letakkan di tempat sejuk, jauh dari sinar matahari langsung</li>
                                <li>Jauh dari AC dan kipas angin</li>
                                <li>Ganti air vas setiap hari</li>
                                <li>Potong tangkai bunga dengan diagonal setiap hari</li>
                                <li>Gunakan flower food jika disediakan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Q6 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            Apakah Anda menerima custom request?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>Tentu saja! Kami sangat senang dengan custom request.</strong></p>
                            <p>Anda bisa request:</p>
                            <ul>
                                <li>🎨 Design khusus sesuai selera Anda</li>
                                <li>🌹 Jenis bunga spesifik</li>
                                <li>🎁 Tambahan hadiah (cokelat, boneka, dll)</li>
                                <li>📝 Kartu ucapan personal</li>
                                <li>💰 Sesuai budget Anda</li>
                            </ul>
                            <p>Klik tombol "Order Custom" untuk memulai!</p>
                        </div>
                    </div>
                </div>

                <!-- Q7 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            Apakah ada minimum order?
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Tidak ada minimum order! Anda bisa memesan 1 rangkaian sekalipun. Harga sudah termasuk semua biaya, tidak ada biaya tersembunyi.</p>
                            <p>Untuk pemesanan dalam jumlah banyak (10+ rangkaian), hubungi kami untuk mendapatkan harga khusus! 🎉</p>
                        </div>
                    </div>
                </div>

                <!-- Q8 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                            Bagaimana jika saya ingin melihat produk lebih dulu sebelum memesan?
                        </button>
                    </h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Kami memahami Anda ingin melihat produk langsung. Silakan:</p>
                            <ul>
                                <li>☎️ Hubungi kami di WhatsApp untuk video call preview produk</li>
                                <li>📍 Datang langsung ke toko kami (jam operasional: Senin-Minggu 09:00-18:00)</li>
                                <li>📸 Minta foto/video produk spesifik yang tertarik</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Q9 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                            Berapa lama usia bunga dalam vas?
                        </button>
                    </h2>
                    <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>Rata-rata usia bunga segar kami adalah 7-10 hari.</strong></p>
                            <p>Dengan perawatan yang tepat, beberapa rangkaian bisa bertahan hingga 2 minggu!</p>
                            <p>Faktor yang mempengaruhi:</p>
                            <ul>
                                <li>Jenis bunga (beberapa jenis lebih tahan)</li>
                                <li>Suhu ruangan</li>
                                <li>Perawatan yang Anda berikan</li>
                                <li>Kualitas air vas</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Q10 -->
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                            Apakah bunga benar-benar segar?
                        </button>
                    </h2>
                    <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>Ya, semua bunga kami 100% segar!</strong></p>
                            <p>Kami berkomitmen pada kualitas:</p>
                            <ul>
                                <li>✓ Bunga dipilih langsung dari taman/supplier terbaik setiap hari</li>
                                <li>✓ Tidak menggunakan bunga bekas atau yang sudah lama</li>
                                <li>✓ Setiap rangkaian dibuat fresh on-order</li>
                                <li>✓ Jaminan kesegaran 24 jam atau ganti gratis</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- CTA -->
            <div class="text-center p-4 bg-light rounded">
                <h5 class="mb-3">Masih ada pertanyaan?</h5>
                <p class="text-muted mb-3">
                    Tim kami siap membantu Anda! Chat kami melalui WhatsApp untuk jawaban yang lebih personal.
                </p>
                <a href="https://wa.me/{{ env('STORE_WHATSAPP', '6281234567890') }}" target="_blank" class="btn btn-primary-custom">
                    💬 Hubungi Kami via WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .accordion-button:not(.collapsed) {
        background-color: rgba(233, 30, 99, 0.1);
        color: var(--primary-color);
    }

    .accordion-button:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(233, 30, 99, 0.25);
    }

    .step {
        transition: all 0.3s ease;
    }

    .step:hover {
        transform: translateX(5px);
    }
</style>
@endsection
