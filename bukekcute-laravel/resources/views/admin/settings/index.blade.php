@extends('layouts.app')

@section('title', 'Pengaturan Toko')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">⚙️ Pengaturan Toko</h1>
            <p class="text-muted small mt-1">Kelola konfigurasi toko Buket Cute</p>
        </div>
    </div>

    <!-- Status Section -->
    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Status WhatsApp Bot</h6>
                    <div id="whatsapp-status" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted small mt-2">Mengecek status...</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-3" id="rescan-qr-btn">
                        🔄 Rescan QR Code
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">Informasi Sistem</h6>
                    <small class="d-block text-muted mb-2">
                        <strong>Laravel:</strong> {{ \Illuminate\Foundation\Application::VERSION }} | 
                        <strong>PHP:</strong> {{ phpversion() }}
                    </small>
                    <small class="d-block text-muted mb-2">
                        <strong>Database:</strong> {{ config('database.default') }}
                    </small>
                    <small class="d-block text-muted">
                        <strong>Queue:</strong> {{ config('queue.default') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

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
