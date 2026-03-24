@extends('layouts.admin')

@section('title', 'Edit Fuzzy Rule')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">Edit Fuzzy Rule</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Edit Rule: {{ $fuzzyRule->intent }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.fuzzy-rules.update', $fuzzyRule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Intent -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Intent Name <span class="text-danger">*</span></label>
                        <input type="text" name="intent" class="form-control @error('intent') is-invalid @enderror" 
                               value="{{ old('intent', $fuzzyRule->intent) }}">
                        @error('intent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Pattern -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pattern <span class="text-danger">*</span></label>
                        <textarea name="pattern" class="form-control @error('pattern') is-invalid @enderror" 
                                  rows="4">{{ old('pattern', $fuzzyRule->pattern) }}</textarea>
                        @error('pattern')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Pisahkan keyword dengan <code>|</code> atau gunakan regex: <code>/pattern/flags</code>
                        </small>
                    </div>

                    <!-- Confidence Threshold -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confidence Threshold <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="confidence_threshold" step="0.01" min="0" max="1" 
                                   class="form-control @error('confidence_threshold') is-invalid @enderror" 
                                   value="{{ old('confidence_threshold', $fuzzyRule->confidence_threshold) }}">
                            <span class="input-group-text">%</span>
                        </div>
                        @error('confidence_threshold')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Aksi <span class="text-danger">*</span></label>
                        <select name="action" class="form-select @error('action') is-invalid @enderror">
                            <option value="">-- Pilih Aksi --</option>
                            @foreach($actions as $key => $label)
                                <option value="{{ $key }}" @selected(old('action', $fuzzyRule->action) == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('action')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Response Template -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Response Template</label>
                        <textarea name="response_template" class="form-control @error('response_template') is-invalid @enderror" 
                                  rows="4">{{ old('response_template', $fuzzyRule->response_template) }}</textarea>
                        @error('response_template')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" 
                                   value="1" @checked(old('is_active', $fuzzyRule->is_active))>
                            <label class="form-check-label" for="isActive">
                                Aktifkan rule ini
                            </label>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Rule
                        </button>
                        <a href="{{ route('admin.fuzzy-rules.index') }}" class="btn btn-secondary">
                            Batal
                        </a>
                    </div>
                </form>
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
                <p class="small text-muted">Test pattern sebelum menyimpan</p>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pattern Tester</label>
                    <textarea id="testPattern" class="form-control form-control-sm" rows="3">{{ $fuzzyRule->pattern }}</textarea>
                </div>

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
                <h5 class="mb-0 small fw-bold">Info Rule</h5>
            </div>
            <div class="card-body small">
                <p class="mb-2">
                    <strong>Dibuat:</strong><br>
                    {{ $fuzzyRule->created_at->format('d M Y H:i') }}
                </p>
                <p class="mb-0">
                    <strong>Diperbarui:</strong><br>
                    {{ $fuzzyRule->updated_at->format('d M Y H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testPattern() {
    const pattern = document.getElementById('testPattern').value;
    const message = document.getElementById('testMessage').value;
    const result = document.getElementById('testResult');

    if (!pattern || !message) {
        result.innerHTML = '<div class="alert alert-warning">Masukkan pattern dan pesan test terlebih dahulu</div>';
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
                <p class="small mb-2"><strong>Keywords:</strong></p>
                <code class="small">${data.keywords.join(', ') || 'Tidak ada'}</code>
                <p class="small mt-2 mb-2"><strong>Regex:</strong></p>
                <code class="small">${data.regex.join('<br>') || 'Tidak ada'}</code>
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
