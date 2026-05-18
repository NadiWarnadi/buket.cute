@extends('layouts.admin')

@section('title', 'Tambah Resep Produk')
@section('page-title', 'Tambah Resep')

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('admin.recipes.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="product_id" class="form-label">Produk</label>
                <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="ingredient_id" class="form-label">Bahan Baku</label>
                <select name="ingredient_id" id="ingredient_id" class="form-select @error('ingredient_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Bahan --</option>
                    @foreach($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}" {{ old('ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
                @error('ingredient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label">Kuantitas</label>
                <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" min="1" required>
                <small class="text-muted">Jumlah bahan untuk 1 produk</small>
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Simpan Resep</button>
            </div>
        </form>
    </div>
</div>
@endsection