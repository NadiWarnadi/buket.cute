@extends('layouts.admin')

@section('title', 'Kelola Bahan Baku')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Daftar Bahan Baku</h1>
            <a href="{{ route('admin.ingredients.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Bahan
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

@if($lowStockCount > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <strong>{{ $lowStockCount }} bahan</strong> memiliki stok menipis (≤ batas minimum)
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Search & Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <label for="search" class="form-label">Cari Bahan</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Nama atau deskripsi..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-4">
                <label for="sort" class="form-label">Urutkan</label>
                <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                    <option value="latest" @selected(request('sort') == 'latest' || !request('sort'))>Terbaru</option>
                    <option value="oldest" @selected(request('sort') == 'oldest')>Terlama</option>
                    <option value="name-asc" @selected(request('sort') == 'name-asc')>Nama (A-Z)</option>
                    <option value="name-desc" @selected(request('sort') == 'name-desc')>Nama (Z-A)</option>
                    <option value="stock-high" @selected(request('sort') == 'stock-high')>Stok Tinggi</option>
                    <option value="stock-low" @selected(request('sort') == 'stock-low')>Stok Rendah</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($ingredients->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Nama Bahan</th>
                            <th style="width: 12%">Satuan</th>
                            <th style="width: 15%">Stok Saat Ini</th>
                            <th style="width: 15%">Min Stok</th>
                            <th style="width: 18%">Status</th>
                            <th style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $ingredient)
                            <tr>
                                <td>
                                    <strong>{{ $ingredient->name }}</strong>
                                    <br><small class="text-muted">{{ Str::limit($ingredient->description, 40) }}</small>
                                </td>
                                <td><small>{{ $ingredient->unit }}</small></td>
                                <td>
                                    <span class="badge bg-info">{{ $ingredient->stock }}</span>
                                </td>
                                <td>
                                    @if($ingredient->min_stock)
                                        <small>{{ $ingredient->min_stock }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    @if($ingredient->isLowStock())
                                        <span class="badge bg-danger">Stok Rendah</span>
                                    @elseif($ingredient->stock == 0)
                                        <span class="badge bg-secondary">Stok Habis</span>
                                    @else
                                        <span class="badge bg-success">Normal</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.ingredients.edit', $ingredient) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('admin.ingredients.show', $ingredient) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.ingredients.destroy', $ingredient) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin?')">
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
                {{ $ingredients->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada bahan baku</p>
                <a href="{{ route('admin.ingredients.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Buat Bahan Baru
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
