@extends('layouts.admin')

@section('title', 'Manajemen QR Code WhatsApp')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">📱 Scan QR Code WhatsApp</h6>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body text-center">
                <div id="qr-container" style="min-height: 300px;">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-2"></div>
                        <p>Memuat QR Code...</p>
                    </div>
                </div>
                <div class="alert alert-info mt-3 small">
                    <i class="bi bi-info-circle"></i> Petunjuk: pastikan server Node.js berjalan, URL API dan API Key benar.
                </div>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button id="refreshBtn" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Refresh</button>
                    <button id="resetBtn" class=" btn btn-danger"><i class="bi bi-trash"></i> Reset Sesi</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
    const API_QR = '{{ url("api/settings/whatsapp/qr-code") }}';
    const API_RESET = '{{ route("admin.settings.rescan-qr") }}';
    
    let currentQR = null;

    async function loadQR() {
        const container = document.getElementById('qr-container');
        container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary mb-2"></div><p>Memuat QR Code...</p></div>';
        try {
            const res = await fetch(API_QR, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success && data.qr_code && data.qr_code !== null) {
                // Hapus container lalu buat QR
                container.innerHTML = '';
                // Gunakan QRCode library
                new QRCode(container, {
                    text: data.qr_code,
                    width: 256,
                    height: 256,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                // Tambahkan teks
                let p = document.createElement('p');
                p.className = 'mt-2 text-muted';
                p.innerText = 'Scan dengan WhatsApp di ponsel Anda';
                container.appendChild(p);
            } else {
                container.innerHTML = `<div class="alert alert-warning">QR Code tidak tersedia. Periksa koneksi ke server WhatsApp.</div>`;
            }
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
        }
    }

    function resetSession() {
        if (!confirm('Reset sesi? Scan ulang QR nanti.')) return;
        const btn = document.getElementById('resetBtn');
        btn.disabled = true;
        btn.innerText = 'Resetting...';
        fetch(API_RESET, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            loadQR();
        }).catch(() => alert('Gagal reset')).finally(() => {
            btn.disabled = false;
            btn.innerText = 'Reset Sesi';
        });
    }

    document.getElementById('refreshBtn').onclick = loadQR;
    document.getElementById('resetBtn').onclick = resetSession;
    loadQR();
    setInterval(loadQR, 30000);
</script>
@endpush