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

<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.settings.update') }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <!-- General Settings -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Pengaturan Umum</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="app_name" class="form-label">Nama Toko</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" 
                                       value="{{ $settings['app_name'] }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="app_timezone" class="form-label">Zona Waktu</label>
                                <select class="form-select" id="app_timezone" name="app_timezone">
                                    <option value="Asia/Jakarta" {{ $settings['app_timezone'] === 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                                    <option value="Asia/Makassar" {{ $settings['app_timezone'] === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                                    <option value="Asia/Jayapura" {{ $settings['app_timezone'] === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="app_description" class="form-label">Deskripsi Toko</label>
                            <textarea class="form-control" id="app_description" name="app_description" rows="3">{{ $settings['app_description'] }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="app_currency" class="form-label">Mata Uang</label>
                                <input type="text" class="form-control" id="app_currency" name="app_currency" 
                                       value="{{ $settings['app_currency'] }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="order_prefix" class="form-label">Prefix No. Pesanan</label>
                                <input type="text" class="form-control" id="order_prefix" name="order_prefix" 
                                       value="{{ $settings['order_prefix'] }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Settings -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Pengaturan Stok</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="low_stock_threshold" class="form-label">Batas Stok Rendah</label>
                                <input type="number" class="form-control" id="low_stock_threshold" 
                                       name="low_stock_threshold" value="{{ $settings['low_stock_threshold'] }}" min="1" required>
                                <small class="text-muted">Jumlah minimal sebelum sistem menampilkan peringatan stok rendah</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="auto_order_timeout" class="form-label">Timeout Draft Pesanan (jam)</label>
                                <input type="number" class="form-control" id="auto_order_timeout" 
                                       name="auto_order_timeout" value="{{ $settings['auto_order_timeout'] }}" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Pengaturan Notifikasi</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notification_enabled" 
                                   name="notification_enabled" value="1" {{ $settings['notification_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="notification_enabled">
                                Aktifkan notifikasi email
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="notification_email" class="form-label">Email Notifikasi</label>
                            <input type="email" class="form-control" id="notification_email" 
                                   name="notification_email" value="{{ $settings['notification_email'] }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Settings -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Pengaturan WhatsApp Bot</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="whatsapp_enabled" 
                                   name="whatsapp_enabled" value="1" {{ $settings['whatsapp_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="whatsapp_enabled">
                                Aktifkan WhatsApp Bot
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_phone" class="form-label">Nomor WhatsApp Toko</label>
                            <input type="text" class="form-control" id="whatsapp_phone" 
                                   name="whatsapp_phone" placeholder="62812345678" 
                                   value="{{ $settings['whatsapp_phone'] }}">
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_api_url" class="form-label">URL API WhatsApp</label>
                            <input type="url" class="form-control" id="whatsapp_api_url" 
                                   name="whatsapp_api_url" value="{{ $settings['whatsapp_api_url'] }}">
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_api_key" class="form-label">API Key WhatsApp</label>
                            <input type="password" class="form-control" id="whatsapp_api_key" 
                                   name="whatsapp_api_key" value="{{ $settings['whatsapp_api_key'] }}">
                        </div>
                        <small class="text-muted d-block mb-3">Kosongkan untuk menggunakan nilai sebelumnya</small>
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
    </div>
</div>
@endsection

    <!-- Settings Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">📝 Informasi Toko</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Logo -->
                <div class="mb-4">
                    <label for="logo" class="form-label">📸 Logo Toko</label>
                    @if($settings['logo'])
                        <div class="mb-2">
                            <img src="{{ Storage::url($settings['logo']) }}" alt="Logo" style="max-width: 150px; max-height: 150px;">
                            <br>
                            <small class="text-muted">{{ $settings['logo'] }}</small>
                        </div>
                    @endif
                    <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                           id="logo" name="logo" accept="image/*">
                    <small class="text-muted d-block mt-1">Format: JPEG, PNG, JPG, GIF. Max: 2MB</small>
                    @error('logo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                <!-- Store Info Row 1 -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="store_name" class="form-label">Nama Toko</label>
                        <input type="text" class="form-control @error('store_name') is-invalid @enderror" 
                               id="store_name" name="store_name" 
                               value="{{ $settings['store_name'] }}" required>
                        @error('store_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="store_email" class="form-label">Email Toko</label>
                        <input type="email" class="form-control @error('store_email') is-invalid @enderror" 
                               id="store_email" name="store_email" 
                               value="{{ $settings['store_email'] }}" required>
                        @error('store_email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Store Info Row 2 -->
                <div class="mb-4">
                    <label for="store_address" class="form-label">Alamat Toko</label>
                    <textarea class="form-control @error('store_address') is-invalid @enderror" 
                              id="store_address" name="store_address" rows="3" required>{{ $settings['store_address'] }}</textarea>
                    @error('store_address')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Store Info Row 3 -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="store_phone" class="form-label">Telepon Toko</label>
                        <input type="text" class="form-control @error('store_phone') is-invalid @enderror" 
                               id="store_phone" name="store_phone" 
                               value="{{ $settings['store_phone'] }}" placeholder="02x xxxx xxxx" required>
                        @error('store_phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="store_whatsapp" class="form-label">🔗 Nomor WhatsApp</label>
                        <input type="text" class="form-control @error('store_whatsapp') is-invalid @enderror" 
                               id="store_whatsapp" name="store_whatsapp" 
                               value="{{ $settings['store_whatsapp'] }}" placeholder="62xxxxxxxxxx" required>
                        @error('store_whatsapp')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="store_description" class="form-label">Deskripsi Toko</label>
                    <textarea class="form-control @error('store_description') is-invalid @enderror" 
                              id="store_description" name="store_description" rows="3">{{ $settings['store_description'] }}</textarea>
                    @error('store_description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                <!-- Features -->
                <h6 class="mb-3">🛠️ Fitur</h6>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="auto_reply_enabled" 
                           name="auto_reply_enabled" value="1" 
                           {{ $settings['auto_reply_enabled'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_reply_enabled">
                        <strong>Aktifkan Auto Reply</strong>
                        <small class="d-block text-muted">Bot akan otomatis membalas pesan masuk</small>
                    </label>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auto_read_messages" 
                           name="auto_read_messages" value="1" 
                           {{ $settings['auto_read_messages'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_read_messages">
                        <strong>Auto Read Messages</strong>
                        <small class="d-block text-muted">RBot akan otomatis menandai pesan sebagai sudah dibaca</small>
                    </label>
                </div>

                <hr class="my-4">

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Pengaturan
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkWhatsAppStatus();
    
    document.getElementById('rescan-qr-btn').addEventListener('click', function() {
        if (confirm('Anda yakin? QR Code akan di-reset dan perlu di-scan ulang.')) {
            fetch('{{ route("admin.settings.rescan-qr") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Berhasil di-reset');
                setTimeout(checkWhatsAppStatus, 1000);
            })
            .catch(err => alert('Error: ' + err.message));
        }
    });
});

function checkWhatsAppStatus() {
    fetch('{{ route("admin.settings.whatsapp-status") }}')
        .then(res => res.json())
        .then(data => {
            const statusDiv = document.getElementById('whatsapp-status');
            if (data.connected) {
                statusDiv.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <strong>✅ Terhubung</strong>
                        <small class="d-block">${data.bot_jid || ''}</small>
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <strong>❌ Tidak Terhubung</strong>
                        <small class="d-block">${data.message || 'Bot tidak berjalan'}</small>
                    </div>
                `;
            }
        })
        .catch(err => {
            document.getElementById('whatsapp-status').innerHTML = `
                <div class="alert alert-danger mb-0">
                    <strong>Error</strong>
                    <small class="d-block">${err.message}</small>
                </div>
            `;
        });
}
</script>
@endsection
