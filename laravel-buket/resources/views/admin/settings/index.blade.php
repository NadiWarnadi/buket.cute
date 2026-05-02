@extends('layouts.admin')

@section('title', 'Pengaturan Toko')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3">⚙️ Pengaturan Toko</h1>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.settings.update') }}" method="POST" class="row g-3">
    @csrf
    @method('PUT')

    <!-- General Settings -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">📋 Pengaturan Umum</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="app_name" class="form-label">Nama Toko</label>
                        <input type="text" class="form-control @error('app_name') is-invalid @enderror" 
                               id="app_name" name="app_name" 
                               value="{{ old('app_name', $settings['general']['app_name'] ?? '') }}" required>
                        @error('app_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="app_timezone" class="form-label">Zona Waktu</label>
                        <select class="form-select" id="app_timezone" name="app_timezone">
                            <option value="Asia/Jakarta" {{ ($settings['general']['app_timezone'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar" {{ ($settings['general']['app_timezone'] ?? '') == 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura" {{ ($settings['general']['app_timezone'] ?? '') == 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="app_description" class="form-label">Deskripsi Toko</label>
                    <textarea class="form-control @error('app_description') is-invalid @enderror" 
                              id="app_description" name="app_description" rows="3">{{ old('app_description', $settings['general']['app_description'] ?? '') }}</textarea>
                    @error('app_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="app_currency" class="form-label">Mata Uang</label>
                        <input type="text" class="form-control @error('app_currency') is-invalid @enderror" 
                               id="app_currency" name="app_currency" 
                               value="{{ old('app_currency', $settings['general']['app_currency'] ?? 'IDR') }}" required>
                        @error('app_currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="order_prefix" class="form-label">Prefix No. Pesanan</label>
                        <input type="text" class="form-control @error('order_prefix') is-invalid @enderror" 
                               id="order_prefix" name="order_prefix" 
                               value="{{ old('order_prefix', $settings['general']['order_prefix'] ?? 'ORD') }}" required>
                        @error('order_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Settings -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">📦 Pengaturan Stok</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="low_stock_threshold" class="form-label">Batas Stok Rendah</label>
                        <input type="number" class="form-control @error('low_stock_threshold') is-invalid @enderror" 
                               id="low_stock_threshold" name="low_stock_threshold" 
                               value="{{ old('low_stock_threshold', $settings['stock']['low_stock_threshold'] ?? 5) }}" min="1" required>
                        <small class="text-muted">Jumlah minimal sebelum peringatan stok rendah</small>
                        @error('low_stock_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="auto_order_timeout" class="form-label">Timeout Draft Pesanan (jam)</label>
                        <input type="number" class="form-control @error('auto_order_timeout') is-invalid @enderror" 
                               id="auto_order_timeout" name="auto_order_timeout" 
                               value="{{ old('auto_order_timeout', $settings['stock']['auto_order_timeout'] ?? 24) }}" min="1" required>
                        @error('auto_order_timeout') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">📧 Pengaturan Notifikasi</h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="notification_enabled" 
                           name="notification_enabled" value="1" {{ ($settings['notification']['notification_enabled'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="notification_enabled">
                        Aktifkan notifikasi email
                    </label>
                </div>
                <div class="mb-3">
                    <label for="notification_email" class="form-label">Email Notifikasi</label>
                    <input type="email" class="form-control @error('notification_email') is-invalid @enderror" 
                           id="notification_email" name="notification_email" 
                           value="{{ old('notification_email', $settings['notification']['notification_email'] ?? '') }}">
                    @error('notification_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Settings -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">📱 Pengaturan WhatsApp Bot</h6>
                <a href="{{ route('admin.settings.whatsapp-qr') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-qr-code"></i> Manajemen QR Code
                </a>
            </div>
            <div class="card-body">
                <div id="whatsapp-status" class="mb-3"></div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="whatsapp_enabled" 
                           name="whatsapp_enabled" value="1" {{ ($settings['whatsapp']['whatsapp_enabled'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="whatsapp_enabled">
                        Aktifkan WhatsApp Bot
                    </label>
                </div>
                <div class="mb-3">
                    <label for="whatsapp_phone" class="form-label">Nomor WhatsApp Toko</label>
                    <input type="text" class="form-control @error('whatsapp_phone') is-invalid @enderror" 
                           id="whatsapp_phone" name="whatsapp_phone" placeholder="62812345678" 
                           value="{{ old('whatsapp_phone', $settings['whatsapp']['whatsapp_phone'] ?? '') }}">
                    @error('whatsapp_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="whatsapp_api_url" class="form-label">URL API WhatsApp (Node.js)</label>
                    <input type="url" class="form-control @error('whatsapp_api_url') is-invalid @enderror" 
                           id="whatsapp_api_url" name="whatsapp_api_url" 
                           value="{{ old('whatsapp_api_url', $settings['whatsapp']['whatsapp_api_url'] ?? '') }}">
                    <small class="text-muted">Contoh: http://localhost:3000</small>
                    @error('whatsapp_api_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="whatsapp_api_key" class="form-label">API Key WhatsApp</label>
                    <input type="password" class="form-control @error('whatsapp_api_key') is-invalid @enderror" 
                           id="whatsapp_api_key" name="whatsapp_api_key" 
                           placeholder="Kosongkan jika tidak ingin mengubah" value="">
                    <small class="text-muted">Isi hanya jika ingin mengganti API Key. Nilai yang tersimpan tidak ditampilkan.</small>
                    @error('whatsapp_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="col-12">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Simpan Pengaturan
            </button>
            <button type="reset" class="btn btn-secondary">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function checkWhatsAppStatus() {
        fetch('{{ route("admin.settings.whatsapp-status") }}')
            .then(res => res.json())
            .then(data => {
                const statusDiv = document.getElementById('whatsapp-status');
                if (data.connected) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success mb-0">
                            <strong>✅ Terhubung</strong>
                            <small class="d-block">Nomor: ${data.phone || '-'}</small>
                        </div>
                    `;
                } else if (data.status === 'disabled') {
                    statusDiv.innerHTML = `
                        <div class="alert alert-secondary mb-0">
                            <strong>⏸️ WhatsApp belum diaktifkan</strong>
                            <small class="d-block">Aktifkan fitur di atas untuk memulai</small>
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <strong>❌ Tidak Terhubung</strong>
                            <small class="d-block">${data.message || 'Belum terhubung, scan QR Code di halaman Manajemen QR Code'}</small>
                        </div>
                    `;
                }
            })
            .catch(err => {
                document.getElementById('whatsapp-status').innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <strong>⚠️ Gagal memeriksa status</strong>
                        <small class="d-block">${err.message}</small>
                    </div>
                `;
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkWhatsAppStatus();
        setInterval(checkWhatsAppStatus, 30000);
    });
</script>
@endpush