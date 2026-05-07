@extends('layouts.admin')

@section('title', 'Edit Produk')

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
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Produk</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Kategori & Harga --}}
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
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
                                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" step="100" min="0" required>
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
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Stok & Status --}}
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0" required>
                                @error('stock')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========== MEDIA SEKARANG (POLYMORPHIC) ========== --}}
                    <div class="mb-4">
                        <label class="form-label">Media Saat Ini</label>

                        @php
                            $existingMedia = $product->media; // morphMany, mengembalikan Collection
                        @endphp

                        @if($existingMedia->count())
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40">Featured</th>
                                            <th>Pratinjau</th>
                                            <th>Nama File</th>
                                            <th>Tipe</th>
                                            <th width="80">Hapus?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($existingMedia as $media)
                                            @php
                                                $isVideo = Str::contains($media->mime_type, 'video');
                                                $isFeatured = $media->is_featured;
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                               name="featured_media_id"
                                                               value="{{ $media->id }}"
                                                               id="featured_{{ $media->id }}"
                                                               @checked($isFeatured)>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($isVideo)
                                                        <video width="100" muted controls style="object-fit: cover;">
                                                            <source src="{{ Storage::url($media->file_path) }}" type="{{ $media->mime_type }}">
                                                            Browser Anda tidak mendukung video.
                                                        </video>
                                                    @else
                                                        <img src="{{ Storage::url($media->file_path) }}"
                                                             alt="{{ $media->file_name }}"
                                                             class="img-thumbnail"
                                                             style="max-height: 60px; width: auto;">
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-truncate d-inline-block" style="max-width: 180px;" title="{{ $media->file_name }}">
                                                        {{ $media->file_name }}
                                                    </small>
                                                    @if($isFeatured)
                                                        <i class="bi bi-star-fill text-warning ms-1" title="Media utama"></i>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $isVideo ? 'info' : 'primary' }}">
                                                        {{ $isVideo ? 'Video' : 'Gambar' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="delete_media[]"
                                                               value="{{ $media->id }}">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <small class="text-muted">
                                    <i class="bi bi-star-fill text-warning"></i> = Gambar/video unggulan. Pilih satu media baru untuk mengganti.
                                    Centang <strong>Hapus?</strong> untuk menghapus permanen saat disimpan.
                                </small>
                            </div>
                        @else
                            <div class="alert alert-light border">
                                <i class="bi bi-camera"></i> Belum ada media. Silakan tambahkan di bawah.
                            </div>
                        @endif
                    </div>

                    {{-- ========== TAMBAH MEDIA BARU ========== --}}
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-cloud-upload"></i> Tambah Media Baru (Gambar / Video)
                        </label>
                        <input type="file"
                               class="form-control @error('media') is-invalid @enderror @error('media.*') is-invalid @enderror"
                               name="media[]"
                               id="media"
                               accept="image/*,video/*"
                               multiple
                               onchange="previewMultipleMedia(event)">
                        <small class="text-muted">Anda dapat memilih beberapa file sekaligus. Format: jpeg, png, mp4, mov, dll. Maks. 2MB per file (opsional).</small>
                        @error('media')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('media.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        {{-- Preview grid untuk file baru --}}
                        <div class="row g-3 mt-3" id="newMediaPreview"></div>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="d-grid gap-2 d-md-flex justify-content-end">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
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

            // Tampilkan preview gambar atau video
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
                video.onloadeddata = () => URL.revokeObjectURL(url); // opsional
                cardBody.appendChild(video);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark text-muted display-6';
                cardBody.appendChild(icon);
            }

            // Nama file
            const name = document.createElement('small');
            name.className = 'd-block text-truncate mt-1';
            name.title = file.name;
            name.textContent = file.name;
            cardBody.appendChild(name);

            card.appendChild(cardBody);
            col.appendChild(card);
            container.appendChild(col);
        });
    }

    // Inisialisasi hapus radio featured jika kosong (tidak ada media existing)
    // Agar bisa reset featured, kita pertahankan radio dgn opsi "Tidak ada" tidak wajib, karena bisa diceklis hapus saja.
</script>
@endpush