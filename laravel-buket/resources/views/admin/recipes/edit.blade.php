@extends('layouts.admin')

@section('title', 'Edit Resep Produk')
@section('page-title', 'Edit Resep')

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('admin.recipes.update', [$recipe->product_id, $recipe->ingredient_id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Produk</label>
                <input type="text" class="form-control" value="{{ $recipe->product->name }}" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Bahan Baku</label>
                <input type="text" class="form-control" value="{{ $recipe->ingredient->name }} ({{ $recipe->ingredient->unit }})" disabled>
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label">Kuantitas</label>
                <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $recipe->quantity) }}" min="1" required>
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Perbarui Resep</button>
            </div>
        </form>
    </div>
</div>
@endsection