@extends('layouts.admin')

@section('title', 'Tambah Produk')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Tambah Produk Baru</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Kategori & Harga --}}
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Harga <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" step="100" min="0" required>
                                </div>
                                @error('price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Nama Produk --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Deskripsi produk (opsional)">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Stok --}}
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', 0) }}" min="0" required>
                        @error('stock')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ========== UPLOAD MEDIA BARU ========== --}}
                    <div class="mb-4">
                        <label for="media" class="form-label">
                            <i class="bi bi-cloud-upload"></i> Upload Media (Gambar & Video)
                        </label>
                        <input type="file"
                               class="form-control @error('media') is-invalid @enderror @error('media.*') is-invalid @enderror"
                               id="media"
                               name="media[]"
                               accept="image/*,video/*"
                               multiple
                               onchange="previewMultipleMedia(event)">
                        <small class="text-muted">
                            Anda bisa memilih beberapa file sekaligus. Format: jpeg, png, jpg, gif, mp4, mov, avi. Maks. 20MB per file.
                            File pertama otomatis menjadi media unggulan (bisa diubah nanti).
                        </small>
                        @error('media')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('media.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        {{-- Preview grid --}}
                        <div class="row g-3 mt-3" id="newMediaPreview"></div>
                    </div>

                    {{-- Tombol --}}
                    <div class="d-grid gap-2 d-md-flex justify-content-end">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewMultipleMedia(event) {
        const files = Array.from(event.target.files);
        const container = document.getElementById('newMediaPreview');
        container.innerHTML = '';

        files.forEach((file, index) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'card border shadow-sm';

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'img-fluid rounded';
                img.style.maxHeight = '100px';
                img.style.objectFit = 'cover';
                const reader = new FileReader();
                reader.onload = (e) => img.src = e.target.result;
                reader.readAsDataURL(file);
                cardBody.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.className = 'rounded';
                video.style.maxHeight = '100px';
                video.style.width = '100%';
                video.muted = true;
                video.controls = true;
                video.preload = 'metadata';
                const url = URL.createObjectURL(file);
                video.src = url;
                video.onloadeddata = () => URL.revokeObjectURL(url);
                cardBody.appendChild(video);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark text-muted display-6';
                cardBody.appendChild(icon);
            }

            const name = document.createElement('small');
            name.className = 'd-block text-truncate mt-1';
            name.title = file.name;
            name.textContent = file.name;
            cardBody.appendChild(name);

            // Jika file pertama, beri badge "Featured"
            if (index === 0) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-warning text-dark position-absolute top-0 start-0 m-1';
                badge.innerHTML = '<i class="bi bi-star-fill"></i> Featured';
                // badge kecil di atas card (gunakan CSS positioning)
                card.style.position = 'relative';
                card.appendChild(badge);
            }

            card.appendChild(cardBody);
            col.appendChild(card);
            container.appendChild(col);
        });
    }
</script>
@endpush