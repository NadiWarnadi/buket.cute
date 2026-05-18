@extends('layouts.admin')

@section('title', 'Buat Pesanan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Buat Pesanan Baru</h5>
            </div>
            <div class="card-body">
            @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
                <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
    @csrf

    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="mb-3">
                <label for="customer_id" class="form-label">Pelanggan <span class="text-danger">*</span></label>
                <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->name }} ({{ $customer->phone }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="mb-3">
                <label for="payment_method" class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="cod" @selected(old('payment_method') == 'cod')>COD (Bayar di Tempat)</option>
                    <option value="transfer" @selected(old('payment_method') == 'transfer')>Transfer Bank</option>
                </select>
                @error('payment_method')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="mb-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="pending" @selected(old('status') == 'pending')>Pending</option>
                    <option value="processed" @selected(old('status') == 'processed')>Diproses</option>
                    <option value="completed" @selected(old('status') == 'completed')>Selesai</option>
                    <option value="cancelled" @selected(old('status') == 'cancelled')>Dibatalkan</option>
                </select>
                @error('status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    <!-- Lanjut ke Items Section -->

                    <!-- Items Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Item Pesanan</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addItemBtn">
                                <i class="bi bi-plus"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%">Produk / Deskripsi</th>
                                        <th style="width: 12%">Qty</th>
                                        <th style="width: 18%">Harga Satuan</th>
                                        <th style="width: 20%">Subtotal</th>
                                        <th style="width: 5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Dynamically added items -->
                                </tbody>
                            </table>
                        </div>

                        @error('items')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Catatan pesanan (opsional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Summary -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-4 ms-auto">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">Rp0</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="totalAmount">Rp0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-end">
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Simpan Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
let products = @json($products);
let itemCount = 0;
const itemsBody = document.getElementById('itemsBody');

// === EVENT DELEGATION: satu listener untuk semua baris ===
itemsBody.addEventListener('change', function(e) {
    const target = e.target;
    const row = target.closest('tr');
    if (!row) return;

    // Jika yang diubah adalah dropdown produk
    if (target.classList.contains('product-select')) {
        const selectedOption = target.options[target.selectedIndex];
        const price = selectedOption.dataset.price;
        const priceInput = row.querySelector('.price-input');
        const customDesc = row.querySelector('[name*="custom_description"]');

        if (target.value && price) {
            priceInput.value = parseFloat(price) || 0;
            if (customDesc) customDesc.value = ''; // kosongkan deskripsi kustom
        }
        hitungBaris(row);
        hitungTotal();
    }

    // Jika yang diubah adalah qty atau harga
    if (target.classList.contains('quantity-input') || target.classList.contains('price-input')) {
        hitungBaris(row);
        hitungTotal();
    }
});

function hitungBaris(row) {
    const qty = parseFloat(row.querySelector('.quantity-input')?.value) || 0;
    const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
    const total = qty * price;
    const totalEl = row.querySelector('.item-total');
    if (totalEl) totalEl.textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(Math.floor(total));
}

function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.item-total').forEach(el => {
        total += parseInt(el.textContent.replace(/[^\d]/g, '')) || 0;
    });
    document.getElementById('subtotal').textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(total);
    document.getElementById('totalAmount').textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(total);
}

// === Tambah Item ===
document.getElementById('addItemBtn').addEventListener('click', function() {
    itemCount++;
    const row = document.createElement('tr');
    row.dataset.index = itemCount;
    row.innerHTML = `
        <td>
            <div class="mb-2">
                <select name="items[${itemCount}][product_id]" class="form-select form-select-sm product-select">
                    <option value="">-- Produk Reguler --</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (Rp${new Intl.NumberFormat('id-ID').format(p.price)})</option>`).join('')}
                </select>
            </div>
            <div class="or-text text-center text-muted mb-2" style="font-size: 0.85rem;">Atau</div>
            <input type="text" name="items[${itemCount}][custom_description]" class="form-control form-control-sm" placeholder="Deskripsi custom (jika tidak ada produk)" maxlength="100">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity-input" value="1" min="1" required>
        </td>
        <td>
            <input type="number" name="items[${itemCount}][price]" class="form-control form-control-sm price-input" step="100" min="0" required>
        </td>
        <td>
            <div class="item-total">Rp0</div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); hitungTotal();">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    itemsBody.appendChild(row);
    row.querySelector('.price-input').focus();
});

// === Inisialisasi baris pertama ===
document.getElementById('addItemBtn').click();
</script>
@endsection
