@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">WhatsApp Gateway Settings</h1>
        <div id="status-badge">
            {{-- Status awal dari server-side (Cache) --}}
            @if(isset($whatsappStatus) && $whatsappStatus['connected'])
                <span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> Connected: {{ $whatsappStatus['user'] }}
                </span>
            @else
                <span class="badge bg-secondary">Mengecek Status...</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Kolom Kiri: Form Settings -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konfigurasi API</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.whatsapp.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Bisnis WhatsApp</label>
                                <input type="text" name="business_phone" class="form-control"
                                       value="{{ old('business_phone', $settings['business_phone']) }}" placeholder="62xxxxxxxx">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Store WhatsApp Number</label>
                                <input type="text" name="store_whatsapp" class="form-control"
                                       value="{{ old('store_whatsapp', $settings['store_whatsapp']) }}" placeholder="62xxxxxxxx">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">WhatsApp Service URL</label>
                            <input type="url" name="service_url" class="form-control"
                                   value="{{ old('service_url', $settings['service_url']) }}" placeholder="http://localhost:3000">
                            <small class="text-muted">URL server Node.js kamu (misal: http://localhost:3000)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" class="form-control"
                                       value="{{ old('api_key', $settings['api_key']) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Webhook Key</label>
                                <input type="password" name="webhook_key" class="form-control"
                                       value="{{ old('webhook_key', $settings['webhook_key']) }}">
                            </div>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Simpan Konfigurasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: QR Code & Status -->
        <div class="col-lg-5">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">WhatsApp Connection</h6>
                    <button id="check-status" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-sync-alt"></i> Refresh Status
                    </button>
                </div>
                <div class="card-body text-center">
                    <div id="qr-wrapper" class="p-4 border rounded bg-light mb-3" style="min-height: 320px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <div id="qrcode"></div>
                        <div id="qr-placeholder">
                            <i class="fab fa-whatsapp fa-4x mb-3 text-muted"></i>
                        </div>
                        <p id="qr-message" class="small text-muted mt-3">Status koneksi akan muncul di sini</p>
                    </div>
                    
                    <div id="action-buttons">
                        <button id="refresh-qr" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-qrcode me-1"></i> Hubungkan WhatsApp (Scan QR)
                        </button>
                    </div>

                    <div id="last-update-info" class="text-xs text-muted mt-2">
                        @if(isset($whatsappStatus['updated_at']))
                            Terakhir diperbarui: {{ \Carbon\Carbon::parse($whatsappStatus['updated_at'])->diffForHumans() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let statusInterval = null;
    const qrEl = document.getElementById('qrcode');
    const qrPlaceholder = document.getElementById('qr-placeholder');
    const qrMessage = document.getElementById('qr-message');
    const refreshBtn = document.getElementById('refresh-qr');
    const badgeContainer = document.getElementById('status-badge');

    // Fungsi Update Tampilan UI berdasarkan status
    function updateBadge(connected, user = null, lastUpdate = null) {
        if (connected) {
            badgeContainer.innerHTML = `<span class="badge bg-success shadow-sm"><i class="fas fa-check-circle"></i> Connected: ${user}</span>`;
            qrEl.innerHTML = '<i class="fas fa-check-circle fa-5x text-success mb-3"></i>';
            qrPlaceholder.style.display = 'none';
            qrMessage.innerHTML = `<strong class="text-success">WhatsApp Terhubung!</strong><br><span class="text-muted">Sesi aktif: ${user}</span>`;
            refreshBtn.innerHTML = '<i class="fas fa-link me-1"></i> WhatsApp Sudah Aktif';
            refreshBtn.classList.replace('btn-success', 'btn-outline-success');
            refreshBtn.disabled = true;
            if(statusInterval) clearInterval(statusInterval); // Stop polling jika sudah connect
        } else {
            badgeContainer.innerHTML = `<span class="badge bg-danger shadow-sm"><i class="fas fa-times-circle"></i> Disconnected</span>`;
            refreshBtn.innerHTML = '<i class="fas fa-qrcode me-1"></i> Generate New QR';
            refreshBtn.classList.replace('btn-outline-success', 'btn-success');
            refreshBtn.disabled = false;
        }
    }

    // Cek Status ke Laravel (Bukan ke Node.js langsung)
    async function checkStatusSilently() {
        try {
            const resp = await fetch('{{ route("admin.settings.whatsapp.status") }}');
            const data = await resp.json();
            updateBadge(data.connected, data.user);
            return data.connected;
        } catch (e) {
            console.error('Gagal mengambil status');
            return false;
        }
    }

    // Ambil QR Code dari Server Node.js (via Laravel Proxy)
    async function fetchQR() {
        qrPlaceholder.style.display = 'none';
        qrEl.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        qrMessage.innerText = 'Meminta QR Code dari service...';

        try {
            const response = await fetch('{{ route("admin.settings.whatsapp.qr") }}');
            const data = await response.json();

            if (data.success && data.qrCode) {
                qrEl.innerHTML = '';
                new QRCode(qrEl, {
                    text: data.qrCode,
                    width: 220,
                    height: 220,
                    colorDark: "#075E54",
                });
                qrMessage.innerHTML = '<span class="text-primary font-weight-bold">QR Ready!</span><br>Buka WA > Perangkat Tertaut > Tautkan Perangkat.';
                
                // Mulai polling untuk mendeteksi kapan user selesai scan
                if (!statusInterval) {
                    statusInterval = setInterval(checkStatusSilently, 3000);
                }
            } else if (data.status && data.status.connected) {
                updateBadge(true, data.status.user);
            } else {
                qrEl.innerHTML = '<i class="fas fa-info-circle fa-3x text-info"></i>';
                qrMessage.innerText = data.message || 'Gagal memuat QR. Coba lagi.';
            }
        } catch (error) {
            qrEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-3x text-danger"></i>';
            qrMessage.innerText = 'Service Node.js tidak merespon.';
        }
    }

    refreshBtn.addEventListener('click', fetchQR);
    
    document.getElementById('check-status').addEventListener('click', async function() {
        this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i>';
        await checkStatusSilently();
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
        }, 500);
    });

    // Jalankan pengecekan pertama kali saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        checkStatusSilently();
    });
</script>
@endpush
@endsection