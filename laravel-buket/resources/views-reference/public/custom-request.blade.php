@extends('layouts.public')

@section('title', 'Custom Request - Buket Cute')

@section('content')
<!-- Hero Section -->
<section class="hero mb-0">
    <div class="container">
        <h1>🎨 Pesan Custom</h1>
        <p>Buat rangkaian bunga impian Anda dengan bantuan tim kami</p>
    </div>
</section>

<div class="container py-60">
    <div class="row g-5">
        <!-- Info Section -->
        <div class="col-lg-4">
            <h3 class="mb-4">💡 Mengapa Custom?</h3>
            
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎨</div>
                    <h6>Desain Unik</h6>
                    <p class="small text-muted">
                        Rangkaian bunga yang benar-benar unik sesuai visi dan preferensi Anda.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🌹</div>
                    <h6>Pilihan Bunga Bebas</h6>
                    <p class="small text-muted">
                        Pilih jenis bunga favorit Anda, warna kesukaan, dan kombinasi yang pas untuk acara Anda.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                    <h6>Fleksibel Budget</h6>
                    <p class="small text-muted">
                        Kami bisa membuat rangkaian untuk semua budget, dari yang ekonomis hingga premium.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏰</div>
                    <h6>Pengerjaan Cepat</h6>
                    <p class="small text-muted">
                        Custom order akan dikerjakan dalam 2-4 jam sesuai dengan kebutuhan Anda.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">📝 Form Custom Request</h5>
                </div>
                <div class="card-body">
                    <form id="customRequestForm">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" placeholder="Masukkan nama Anda" required>
                            <small class="text-muted">Kami akan menggunakan nama ini sebagai namun kontak</small>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor WhatsApp</label>
                            <input type="tel" class="form-control" id="phone" placeholder="08xxxxxxxxxx" required>
                            <small class="text-muted">Kami akan menghubungi Anda di nomor ini untuk konfirmasi</small>
                        </div>

                        <!-- Occasion -->
                        <div class="mb-3">
                            <label for="occasion" class="form-label">Untuk Apa / Acara?</label>
                            <select class="form-select" id="occasion" required>
                                <option selected disabled>-- Pilih Acara --</option>
                                <option value="ulang-tahun">🎂 Ulang Tahun</option>
                                <option value="pernikahan">💒 Pernikahan</option>
                                <option value="anniversary">💕 Anniversary</option>
                                <option value="ucapan-selamat">🏆 Ucapan Selamat</option>
                                <option value="duka-cita">🙏 Duka Cita</option>
                                <option value="mohon-maaf">💌 Mohon Maaf</option>
                                <option value="ucapan-terima-kasih">🙏 Terima Kasih</option>
                                <option value="lamaran">💍 Lamaran</option>
                                <option value="kelahiran">👶 Kelahiran</option>
                                <option value="wisuda">🎓 Wisuda</option>
                                <option value="lainnya">📌 Lainnya</option>
                            </select>
                        </div>

                        <!-- Date Needed -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Kapan Dibutuhkan?</label>
                            <input type="date" class="form-control" id="date" required>
                            <small class="text-muted">Tanggal pengiriman yang Anda inginkan</small>
                        </div>

                        <!-- Color Preference -->
                        <div class="mb-3">
                            <label for="colors" class="form-label">Preferensi Warna</label>
                            <select class="form-select" id="colors" required>
                                <option selected disabled>-- Pilih Warna Utama --</option>
                                <option value="merah">❤️ Merah (Romantis, Cinta)</option>
                                <option value="pink">💗 Pink (Lembut, Feminim)</option>
                                <option value="putih">🤍 Putih (Bersih, Elegan)</option>
                                <option value="kuning">💛 Kuning (Ceria, Bahagia)</option>
                                <option value="ungu">💜 Ungu (Mewah, Misterius)</option>
                                <option value="orange">🧡 Orange (Energik, Hangat)</option>
                                <option value="multi-color">🌈 Multi Color (Meriah, Beragam)</option>
                                <option value="surprise">😊 Surprise Me!  (Pilih Kami)</option>
                            </select>
                        </div>

                        <!-- Flower Type -->
                        <div class="mb-3">
                            <label class="form-label">Jenis Bunga yang Disukai</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rose" value="rose" name="flowers">
                                <label class="form-check-label" for="rose">🌹 Mawar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sunflower" value="sunflower" name="flowers">
                                <label class="form-check-label" for="sunflower">🌻 Bunga Matahari</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tulip" value="tulip" name="flowers">
                                <label class="form-check-label" for="tulip">🌷 Tulip</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="lily" value="lily" name="flowers">
                                <label class="form-check-label" for="lily">🌸 Lily</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="orchid" value="orchid" name="flowers">
                                <label class="form-check-label" for="orchid">🌺 Orchid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mixed" value="mixed" name="flowers">
                                <label class="form-check-label" for="mixed">🌼 Campuran (Pilih Kami)</label>
                            </div>
                            <small class="text-muted d-block mt-2">Jika tidak ada yg dipilih, kami akan pilihkan bunga terbaik</small>
                        </div>

                        <!-- Budget -->
                        <div class="mb-3">
                            <label for="budget" class="form-label">Budget</label>
                            <select class="form-select" id="budget" required>
                                <option selected disabled>-- Pilih Range Budget --</option>
                                <option value="100-250k">💵 Rp 100.000 - Rp 250.000</option>
                                <option value="250-500k">💵 Rp 250.000 - Rp 500.000</option>
                                <option value="500-1m">💵 Rp 500.000 - Rp 1.000.000</option>
                                <option value="1-2m">💵 Rp 1.000.000 - Rp 2.000.000</option>
                                <option value="2m-plus">💰 Rp 2.000.000+</option>
                            </select>
                        </div>

                        <!-- Additional Items -->
                        <div class="mb-3">
                            <label class="form-label">Tambahan (Opsional)</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="card" name="additions" value="kartu-ucapan">
                                <label class="form-check-label" for="card">📝 Kartu Ucapan</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chocolate" name="additions" value="cokelat">
                                <label class="form-check-label" for="chocolate">🍫 Cokelat</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bear" name="additions" value="boneka">
                                <label class="form-check-label" for="bear">🧸 Boneka</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="candle" name="additions" value="lilin">
                                <label class="form-check-label" for="candle">🕯️ Lilin</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perfume" name="additions" value="parfum">
                                <label class="form-check-label" for="perfume">💐 Parfum</label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi / Ide Lainnya</label>
                            <textarea class="form-control" id="description" rows="4" placeholder="Ceritakan ide Anda lebih detail. Contoh: warna spesifik, tema, pesan khusus, dll..."></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-custom btn-lg">
                                💬 Kirim via WhatsApp
                            </button>
                            <button type="reset" class="btn btn-light">
                                Bersihkan Form
                            </button>
                        </div>
                    </form>

                    <div class="alert alert-info mt-3" role="alert">
                        <i class="bi bi-info-circle"></i>
                        <strong>💡 Tip:</strong> Semakin detail deskripsi Anda, semakin bagus hasil yang kami berikan!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tips Section -->
<section class="py-60 bg-light">
    <div class="container">
        <h3 class="mb-4 text-center">💡 Tips Untuk Custom Order Terbaik</h3>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="d-flex gap-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">1️⃣</div>
                    <div>
                        <h6>Berikan Deskripsi Detail</h6>
                        <p class="small text-muted">Semakin detail Anda menjelaskan, semakin mudah kami memahami visi Anda.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">2️⃣</div>
                    <div>
                        <h6>Bagikan Inspirasi</h6>
                        <p class="small text-muted">Jika punya foto referensi, bagikan via WhatsApp. Kami bisa menyesuaikan!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">3️⃣</div>
                    <div>
                        <h6>Tentukan Budget</h6>
                        <p class="small text-muted">Budget membantu kami memilih jenis dan jumlah bunga yang sesuai.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-3">
                    <div style="color: var(--primary-color); font-size: 1.5rem;">4️⃣</div>
                    <div>
                        <h6>Jangan Ragu Berkomunikasi</h6>
                        <p class="small text-muted">Chat kami untuk tanya jawab atau perubahan saat proses pembuatan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('customRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Get form values
    const name = document.getElementById('name').value;
    const phone = document.getElementById('phone').value;
    const occasion = document.getElementById('occasion').value;
    const date = document.getElementById('date').value;
    const colors = document.getElementById('colors').value;
    const budget = document.getElementById('budget').value;
    const description = document.getElementById('description').value;

    // Get selected flowers
    const flowers = Array.from(document.querySelectorAll('input[name="flowers"]:checked'))
        .map(cb => cb.value)
        .join(', ') || 'Pilihan diserahkan ke tim';

    // Get selected additions
    const additions = Array.from(document.querySelectorAll('input[name="additions"]:checked'))
        .map(cb => cb.value)
        .join(', ') || 'Tidak ada';

    // Format WhatsApp message
    let message = `📝 *CUSTOM REQUEST BUKET CUTE*\n\n`;
    message += `👤 *Nama:* ${name}\n`;
    message += `📱 *Nomor:* ${phone}\n`;
    message += `🎂 *Acara:* ${occasion}\n`;
    message += `📅 *Tanggal Dibutuhkan:* ${new Date(date).toLocaleDateString('id-ID')}\n`;
    message += `🎨 *Warna Utama:* ${colors}\n`;
    message += `🌹 *Jenis Bunga:* ${flowers}\n`;
    message += `💰 *Budget:* ${budget}\n`;
    message += `🎁 *Tambahan:* ${additions}\n`;
    if (description) {
        message += `💬 *Keterangan Tambahan:*\n${description}\n`;
    }
    message += `\n---\nSilakan hubungi saya untuk konfirmasi lebih lanjut. Terima kasih!`;

    // Clean phone number
    const cleanPhone = phone.replace(/\D/g, '');

    // Open WhatsApp
    const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
});
</script>

<style>
    .form-check-label {
        margin-bottom: 0;
        cursor: pointer;
    }

    .form-check {
        margin-bottom: 0.5rem;
    }

    .form-select, .form-control {
        border-radius: 8px;
    }

    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
    }
</style>
@endsection
