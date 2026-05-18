@extends('layouts.admin')

@section('title', 'Daftar Resep Produk')
@section('page-title', 'Resep Produk')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Daftar Resep</span>
        <a href="{{ route('admin.recipes.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Resep
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th>Bahan Baku</th>
                        <th>Kuantitas</th>
                        <th>Satuan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipes as $recipe)
                    <tr>
                        <td>{{ $recipe->product->name ?? '-' }}</td>
                        <td>{{ $recipe->ingredient->name ?? '-' }}</td>
                        <td>{{ $recipe->quantity }}</td>
                        <td>{{ $recipe->ingredient->unit ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.recipes.edit', [$recipe->product_id, $recipe->ingredient_id]) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.recipes.destroy', [$recipe->product_id, $recipe->ingredient_id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus resep ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada resep.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $recipes->links() }}
    </div>
</div>
@endsection