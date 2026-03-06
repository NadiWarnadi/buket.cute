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
                <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-12 col-md-6">
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
                    </div>

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

document.getElementById('addItemBtn').addEventListener('click', function() {
    itemCount++;
    const row = document.createElement('tr');
    row.dataset.index = itemCount;
    row.innerHTML = `
        <td>
            <div class="mb-2">
                <select name="items[${itemCount}][product_id]" class="form-select form-select-sm product-select">
                    <option value="">-- Produk Reguler --</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (Rp${formatCurrency(p.price)})</option>`).join('')}
                </select>
            </div>
            <div class="or-text text-center text-muted mb-2" style="font-size: 0.85rem;">Atau</div>
            <input type="text" name="items[${itemCount}][custom_description]" class="form-control form-control-sm" placeholder="Deskripsi custom (jika tidak ada produk)" maxlength="100">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity-input" value="1" min="1" required onchange="calculateTotal(this); updateGrandTotal();">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][price]" class="form-control form-control-sm price-input" step="100" min="0" required onchange="calculateTotal(this); updateGrandTotal();">
        </td>
        <td>
            <div class="item-total">Rp0</div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    const select = row.querySelector('.product-select');
    select.addEventListener('change', function() {
        if (this.value) {
            const price = this.options[this.selectedIndex].dataset.price;
            row.querySelector('.price-input').value = price;
            row.querySelector('[name*="custom_description"]').value = '';
        }
        calculateTotal(row.querySelector('.price-input'));
        updateGrandTotal();
    });

    document.getElementById('itemsBody').appendChild(row);
    row.querySelector('.price-input').focus();
});

function calculateTotal(input) {
    const row = input.closest('tr');
    const qty = row.querySelector('.quantity-input').value || 0;
    const price = row.querySelector('.price-input').value || 0;
    const total = (qty * price) || 0;
    row.querySelector('.item-total').textContent = 'Rp' + formatCurrency(total);
}

function removeItem(btn) {
    btn.closest('tr').remove();
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.item-total').forEach(el => {
        const amount = parseInt(el.textContent.replace('Rp', '').replaceAll('.', '') || 0);
        total += amount;
    });
    document.getElementById('subtotal').textContent = 'Rp' + formatCurrency(total);
    document.getElementById('totalAmount').textContent = 'Rp' + formatCurrency(total);
}

function formatCurrency(number) {
    return new Intl.NumberFormat('id-ID').format(Math.floor(number));
}

// Initialize first row
document.getElementById('addItemBtn').click();
</script>
@endsection
