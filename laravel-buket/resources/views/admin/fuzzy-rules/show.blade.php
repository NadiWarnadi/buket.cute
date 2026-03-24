@extends('layouts.admin')

@section('title', 'Detail Fuzzy Rule')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">Detail Fuzzy Rule</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $fuzzyRule->intent }}</h5>
                <span class="badge bg-light text-dark">
                    {{ $fuzzyRule->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="card-body">
                <!-- Intent -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Intent Name</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $fuzzyRule->intent }}</code>
                    </div>
                </div>

                <hr>

                <!-- Pattern -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Pattern</strong>
                    </div>
                    <div class="col-md-9">
                        <div class="card bg-light">
                            <div class="card-body">
                                <code class="text-break" style="white-space: pre-wrap;">{{ $fuzzyRule->pattern }}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Confidence Threshold -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Confidence Threshold</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $fuzzyRule->confidence_threshold }}</code>
                        <small class="text-muted d-block">{{ round($fuzzyRule->confidence_threshold * 100) }}%</small>
                    </div>
                </div>

                <hr>

                <!-- Action -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Aksi</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $fuzzyRule->action)) }}</span>
                    </div>
                </div>

                <hr>

                <!-- Response Template -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Response Template</strong>
                    </div>
                    <div class="col-md-9">
                        @if($fuzzyRule->response_template)
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="text-break mb-0">{{ $fuzzyRule->response_template }}</p>
                                </div>
                            </div>
                        @else
                            <em class="text-muted">Tidak ada</em>
                        @endif
                    </div>
                </div>

                <hr>

                <!-- Status -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Status</strong>
                    </div>
                    <div class="col-md-9">
                        @if($fuzzyRule->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </div>
                </div>

                <hr>

                <!-- Timestamps -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Dibuat</strong>
                    </div>
                    <div class="col-md-9">
                        <small class="text-muted">{{ $fuzzyRule->created_at->format('d M Y H:i:s') }}</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <strong>Diperbarui</strong>
                    </div>
                    <div class="col-md-9">
                        <small class="text-muted">{{ $fuzzyRule->updated_at->format('d M Y H:i:s') }}</small>
                    </div>
                </div>

                <hr>

                <!-- Actions -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('admin.fuzzy-rules.edit', $fuzzyRule) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('admin.fuzzy-rules.destroy', $fuzzyRule) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </form>
                    <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-secondary btn-sm">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pattern Tester -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Test Pattern</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pesan Test</label>
                    <input type="text" id="testMessage" class="form-control form-control-sm" 
                           placeholder="Masukkan pesan untuk di-test">
                </div>

                <button type="button" class="btn btn-info btn-sm w-100" onclick="testPattern()">
                    <i class="bi bi-play-circle"></i> Test
                </button>

                <div id="testResult" class="mt-3"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-light">
                <h5 class="mb-0 small fw-bold">Ringkasan</h5>
            </div>
            <div class="card-body small">
                <div class="mb-3">
                    <p class="text-muted mb-1">Jenis Aksi</p>
                    <p class="mb-0"><strong>{{ match($fuzzyRule->action) {
                        'reply' => 'Kirim Balasan Otomatis',
                        'escalate' => 'Eskalasi ke Admin',
                        'manual_review' => 'Memerlukan Review Manual',
                        'order' => 'Proses Pesanan',
                        'category' => 'Kategorisasi Pesan',
                        'pending' => 'Tandai Pending',
                        default => ucfirst(str_replace('_', ' ', $fuzzyRule->action))
                    } }}</strong></p>
                </div>

                <div class="mb-3">
                    <p class="text-muted mb-1">Tingkat Kepercayaan</p>
                    <p class="mb-0"><strong>{{ round($fuzzyRule->confidence_threshold * 100) }}%</strong></p>
                </div>

                @php
                    $keywords = [];
                    $regex = [];
                    $parts = explode('|', $fuzzyRule->pattern);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (!empty($part)) {
                            if (preg_match('~^/(.+)/([imsxADSUXJu]*)$~', $part)) {
                                $regex[] = $part;
                            } else {
                                $keywords[] = $part;
                            }
                        }
                    }
                @endphp

                @if(count($keywords) > 0)
                    <div class="mb-3">
                        <p class="text-muted mb-1">Keywords ({{ count($keywords) }})</p>
                        @foreach($keywords as $keyword)
                            <small class="badge bg-light text-dark">{{ $keyword }}</small>
                        @endforeach
                    </div>
                @endif

                @if(count($regex) > 0)
                    <div>
                        <p class="text-muted mb-1">Regex ({{ count($regex) }})</p>
                        @foreach($regex as $pattern)
                            <small class="badge bg-light text-dark text-break">{{ $pattern }}</small>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testPattern() {
    const pattern = '{{ $fuzzyRule->pattern }}';
    const message = document.getElementById('testMessage').value;
    const result = document.getElementById('testResult');

    if (!message) {
        result.innerHTML = '<div class="alert alert-warning">Masukkan pesan test terlebih dahulu</div>';
        return;
    }

    fetch('{{ route("admin.fuzzy-rules.test-pattern") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            pattern: pattern,
            test_message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const html = `
                <div class="alert ${data.matched ? 'alert-success' : 'alert-warning'}">
                    <strong>Hasil:</strong> ${data.matched ? '✓ COCOK' : '✗ TIDAK COCOK'}
                </div>
            `;
            result.innerHTML = html;
        } else {
            result.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        result.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
    });
}
</script>
@endpush

@endsection
