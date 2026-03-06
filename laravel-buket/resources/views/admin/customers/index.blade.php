@extends('layouts.admin')

@section('title', 'Daftar Pelanggan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Daftar Pelanggan</h1>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Pelanggan Baru
            </a>
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

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label">Cari Pelanggan</label>
                <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama, telepon, atau alamat..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Urutkan</label>
                <select name="sort" class="form-select">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Tertua</option>
                    <option value="name-asc" {{ request('sort') == 'name-asc' ? 'selected' : '' }}>Nama (A-Z)</option>
                    <option value="name-desc" {{ request('sort') == 'name-desc' ? 'selected' : '' }}>Nama (Z-A)</option>
                    <option value="phone-asc" {{ request('sort') == 'phone-asc' ? 'selected' : '' }}>Telepon (A-Z)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
                @if(request('search') || request('sort'))
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($customers->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Nama</th>
                            <th style="width: 20%">Telepon</th>
                            <th style="width: 30%">Alamat</th>
                            <th style="width: 10%">Pesanan</th>
                            <th style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <strong>{{ $customer->name ?? '-' }}</strong>
                                </td>
                                <td>
                                    <a href="https://wa.me/{{ str_replace(['+', '-', ' '], '', $customer->phone) }}" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-whatsapp"></i> {{ $customer->phone }}
                                    </a>
                                </td>
                                <td>
                                    <small>{{ $customer->address ? Str::limit($customer->address, 30) : '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $customer->orders->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center p-3">
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada pelanggan</p>
                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Pelanggan
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
