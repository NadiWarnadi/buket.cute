@extends('layouts.admin')

@section('title', 'Edit Pesanan')

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
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Pesanan #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('admin.orders.update', $order) }}" method="POST" id="orderForm">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Pelanggan <span class="text-danger">*</span></label>
                                <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <option value="{{ $order->customer->id }}" selected>
                                        {{ $order->customer->name }} ({{ $order->customer->phone }})
                                    </option>
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    @foreach(\App\Models\Order::getStatuses() as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" @selected($order->status === $statusKey)>
                                            {{ $statusLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
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
                                    <!-- Existing items will be loaded here -->
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
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $order->notes) }}</textarea>
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
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let products = @json($products);
let existingItems = @json($order->items);
let itemCount = 0;

// Load existing items
document.addEventListener('DOMContentLoaded', function() {
    existingItems.forEach(function(item) {
        addItemRow(item);
    });
    
    if (!existingItems.length) {
        addItemRow();
    }
    
    updateGrandTotal();
});

function addItemRow(item = null) {
    itemCount++;
    const isExisting = item && item.id;
    const row = document.createElement('tr');
    row.dataset.index = itemCount;
    
    let productOptions = '<option value="">-- Produk Reguler --</option>';
    products.forEach(p => {
        const selected = isExisting && item.product_id === p.id ? 'selected' : '';
        productOptions += `<option value="${p.id}" data-price="${p.price}" ${selected}>${p.name} (Rp${formatCurrency(p.price)})</option>`;
    });

    row.innerHTML = `
        <td>
            ${isExisting ? `<input type="hidden" name="items[${itemCount}][id]" value="${item.id}">` : ''}
            <div class="mb-2">
                <select name="items[${itemCount}][product_id]" class="form-select form-select-sm product-select">
                    ${productOptions}
                </select>
            </div>
            <div class="or-text text-center text-muted mb-2" style="font-size: 0.85rem;">Atau</div>
            <input type="text" name="items[${itemCount}][custom_description]" class="form-control form-control-sm" placeholder="Deskripsi custom" value="${isExisting && !item.product_id ? item.custom_description : ''}">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity-input" value="${isExisting ? item.quantity : 1}" min="1" required onchange="calculateTotal(this); updateGrandTotal();">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][price]" class="form-control form-control-sm price-input" step="100" value="${isExisting ? item.price : ''}" min="0" required onchange="calculateTotal(this); updateGrandTotal();">
        </td>
        <td>
            <div class="item-total">Rp${isExisting ? formatCurrency(item.subtotal) : '0'}</div>
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
}

document.getElementById('addItemBtn').addEventListener('click', function() {
    addItemRow();
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
</script>
@endsection
