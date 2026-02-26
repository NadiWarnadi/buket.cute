@extends('layouts.admin')

@section('title', 'Kelola Produk')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Daftar Produk</h1>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Produk
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

<!-- Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 g-md-3">
            <div class="col-12 col-md-6 col-lg-4">
                <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
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
        @if($products->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%">Status</th>
                            <th style="width: 35%">Produk</th>
                            <th style="width: 15%">Kategori</th>
                            <th style="width: 15%">Harga</th>
                            <th style="width: 10%">Stok</th>
                            <th style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($product->getFeaturedImage())
                                            <img src="{{ $product->getFeaturedImage()->getUrl() }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        @else
                                            <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-image" style="color: #ccc;"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <strong>{{ $product->name }}</strong>
                                            <br><small class="text-muted">{{ $product->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><small>{{ $product->category->name }}</small></td>
                                <td><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong></td>
                                <td>
                                    <span class="badge bg-info">{{ $product->stock }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?')">
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

            <!-- Pagination -->
            <div class="d-flex justify-content-center p-3">
                {{ $products->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada produk</p>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Buat Produk Baru
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
