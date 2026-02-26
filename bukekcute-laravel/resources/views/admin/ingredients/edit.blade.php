@extends('layouts.admin')

@section('title', 'Edit Bahan Baku')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.ingredients.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Bahan Baku</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ingredients.update', $ingredient) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Bahan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $ingredient->name) }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $ingredient->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Satuan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit" value="{{ old('unit', $ingredient->unit) }}" required>
                                @error('unit')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Stok Saat Ini</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $ingredient->stock }}" disabled>
                                    <span class="input-group-text">{{ $ingredient->unit }}</span>
                                </div>
                                <a href="{{ route('admin.ingredients.show', $ingredient) }}" class="small">Ubah via halaman detail</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="min_stock" class="form-label">Batas Minimum Stok</label>
                        <input type="number" class="form-control @error('min_stock') is-invalid @enderror" id="min_stock" name="min_stock" value="{{ old('min_stock', $ingredient->min_stock) }}" min="0">
                        @error('min_stock')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-end">
                        <a href="{{ route('admin.ingredients.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
