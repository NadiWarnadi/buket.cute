@extends('layouts.admin')

@section('title', 'Import Fuzzy Rules')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">Import Fuzzy Rules</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Upload File JSON</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.fuzzy-rules.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">File JSON <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                               accept=".json" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">Upload file JSON berisi array dari fuzzy rules</small>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Format File JSON</h6>
                        <p class="mb-0">File harus berisi array dengan struktur seperti ini:</p>
                        <pre class="mt-2"><code>[
  {
    "intent": "greeting",
    "pattern": "halo|hi|hello|assalamualaikum",
    "confidence_threshold": 0.5,
    "action": "reply",
    "response_template": "Halo! Ada yang bisa kami bantu?",
    "is_active": true
  },
  {
    "intent": "order_status",
    "pattern": "order|pesanan|status",
    "confidence_threshold": 0.6,
    "action": "order",
    "response_template": null,
    "is_active": true
  }
]</code></pre>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Import
                        </button>
                        <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-secondary">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0 small fw-bold">Contoh Format</h5>
            </div>
            <div class="card-body small">
                <p><strong>Field yang Diperlukan:</strong></p>
                <ul class="mb-3">
                    <li><code>intent</code> - Nama intent (unik)</li>
                    <li><code>pattern</code> - Pattern matching</li>
                    <li><code>confidence_threshold</code> - 0-1</li>
                    <li><code>action</code> - Jenis aksi</li>
                </ul>

                <p><strong>Field Opsional:</strong></p>
                <ul class="mb-3">
                    <li><code>response_template</code> - Pesan balasan</li>
                    <li><code>is_active</code> - Status aktif</li>
                </ul>

                <p><strong>Nilai Action:</strong></p>
                <ul class="mb-0">
                    <li>reply</li>
                    <li>escalate</li>
                    <li>manual_review</li>
                    <li>order</li>
                    <li>category</li>
                    <li>pending</li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-light">
                <h5 class="mb-0 small fw-bold">Download Template</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">Klik tombol di bawah untuk download template JSON:</p>
                <button type="button" class="btn btn-secondary btn-sm w-100" onclick="downloadTemplate()">
                    <i class="bi bi-download"></i> Download Template
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function downloadTemplate() {
    const template = [
        {
            "intent": "greeting",
            "pattern": "halo|hi|hello|assalamualaikum|/^hey/i",
            "confidence_threshold": 0.5,
            "action": "reply",
            "response_template": "Halo! Selamat datang di Buket Cute. Ada yang bisa kami bantu?",
            "is_active": true
        },
        {
            "intent": "order_status",
            "pattern": "order|pesanan|status|track",
            "confidence_threshold": 0.6,
            "action": "order",
            "response_template": null,
            "is_active": true
        },
        {
            "intent": "help",
            "pattern": "bantuan|help|pertanyaan|tanya",
            "confidence_threshold": 0.5,
            "action": "escalate",
            "response_template": "Terima kasih atas pertanyaannya. Tim kami akan segera membantu Anda.",
            "is_active": true
        }
    ];

    const dataStr = JSON.stringify(template, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'fuzzy-rules-template.json';
    link.click();
    URL.revokeObjectURL(url);
}
</script>
@endpush

@endsection
