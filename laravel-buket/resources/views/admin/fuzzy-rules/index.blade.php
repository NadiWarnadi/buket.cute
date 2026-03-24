@extends('layouts.admin')

@section('title', 'Kelola Fuzzy Rules')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0">Fuzzy Rules Chatbot</h1>
                <p class="text-muted small mt-1">Kelola aturan otomatis untuk merespons pesan</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.fuzzy-rules.import-form') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-upload"></i> Import
                </a>
                <a href="{{ route('admin.fuzzy-rules.export') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-download"></i> Export
                </a>
                <a href="{{ route('admin.fuzzy-rules.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Rule
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.fuzzy-rules.index') }}" class="row g-2 g-md-3">
            <div class="col-12 col-md-6 col-lg-4">
                <input type="text" name="search" class="form-control" placeholder="Cari intent, pattern, atau aksi..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') == 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') == 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($rules->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%">Status</th>
                            <th style="width: 15%">Intent</th>
                            <th style="width: 25%">Pattern</th>
                            <th style="width: 15%">Aksi</th>
                            <th style="width: 10%">Threshold</th>
                            <th style="width: 15%">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                            <tr>
                                <td>
                                    @if($rule->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $rule->intent }}</strong>
                                </td>
                                <td>
                                    <code class="text-nowrap d-block text-truncate small">{{ substr($rule->pattern, 0, 50) }}{{ strlen($rule->pattern) > 50 ? '...' : '' }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $rule->action)) }}</span>
                                </td>
                                <td>
                                    <code class="small">{{ $rule->confidence_threshold }}</code>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.fuzzy-rules.show', $rule) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.fuzzy-rules.edit', $rule) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Toggle Status" onclick="toggleRule({{ $rule->id }})">
                                            <i class="bi bi-toggle-{{ $rule->is_active ? 'on' : 'off' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteRule({{ $rule->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top small">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        Menampilkan {{ $rules->firstItem() }} hingga {{ $rules->lastItem() }} dari {{ $rules->total() }} data
                    </div>
                    <div>
                        {{ $rules->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                <p class="text-muted mt-3">Belum ada Fuzzy Rule</p>
                <a href="{{ route('admin.fuzzy-rules.create') }}" class="btn btn-primary btn-sm mt-2">
                    <i class="bi bi-plus-circle"></i> Buat Rule Pertama
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleRule(ruleId) {
    if (confirm('Yakin ingin mengubah status rule ini?')) {
        fetch(`/admin/fuzzy-rules/${ruleId}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => location.reload())
        .catch(error => alert('Error: ' + error.message));
    }
}

function deleteRule(ruleId) {
    if (confirm('Yakin ingin menghapus rule ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/fuzzy-rules/${ruleId}`;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="_method" value="DELETE">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush

@endsection
