@extends('layouts.admin')

@section('title', 'Edit Pembelian')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Pembelian Bahan Baku</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.purchases.update', $purchase) }}" method="POST" id="purchaseForm">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="supplier" class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier', $purchase->supplier) }}" required autofocus>
                                @error('supplier')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Daftar Pembelian</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addItemBtn">
                                <i class="bi bi-plus"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Bahan Baku</th>
                                        <th style="width: 15%">Satuan</th>
                                        <th style="width: 15%">Qty</th>
                                        <th style="width: 20%">Harga Satuan</th>
                                        <th style="width: 15%">Total</th>
                                        <th style="width: 5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    @foreach($purchase->items as $item)
                                        <tr data-index="{{ $loop->index }}">
                                            <td>
                                                <select name="items[{{ $loop->index }}][ingredient_id]" class="form-select form-select-sm ingredient-select" required onchange="updateUnitDisplay(this)">
                                                    <option value="">-- Pilih Bahan --</option>
                                                    @foreach($ingredients as $ing)
                                                        <option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}" {{ $ing->id == $item->ingredient_id ? 'selected' : '' }}>{{ $ing->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm unit-display" value="{{ $item->ingredient->unit }}" disabled>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $loop->index }}][quantity]" class="form-control form-control-sm quantity-input" value="{{ $item->quantity }}" min="1" required onchange="calculateTotal(this)">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $loop->index }}][price]" class="form-control form-control-sm unit-price-input" step="0.01" value="{{ $item->price }}" min="0" required onchange="calculateTotal(this)">
                                            </td>
                                            <td>
                                                <div class="item-total">Rp{{ number_format($item->total_price, 0, ',', '.') }}</div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('items')
                            <div class="alert alert-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Summary -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-4 ms-auto">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">Rp{{ number_format($purchase->total, 0, ',', '.') }}</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="totalAmount">Rp{{ number_format($purchase->total, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-end">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Perbarui Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let ingredients = @json($ingredients);
let itemCount = Math.max(...document.querySelectorAll('#itemsBody tr').length > 0 ? Array.from(document.querySelectorAll('#itemsBody tr')).map(r => parseInt(r.dataset.index)) : [0]);

document.getElementById('addItemBtn').addEventListener('click', function() {
    itemCount++;
    const row = document.createElement('tr');
    row.dataset.index = itemCount;
    row.innerHTML = `
        <td>
            <select name="items[${itemCount}][ingredient_id]" class="form-select form-select-sm ingredient-select" required onchange="updateUnitDisplay(this)">
                <option value="">-- Pilih Bahan --</option>
                ${ingredients.map(ing => `<option value="${ing.id}" data-unit="${ing.unit}">${ing.name}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm unit-display" disabled>
        </td>
        <td>
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity-input" min="1" required onchange="calculateTotal(this)">
        </td>
        <td>
            <input type="number" name="items[${itemCount}][price]" class="form-control form-control-sm unit-price-input" step="0.01" min="0" required onchange="calculateTotal(this)">
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
    document.getElementById('itemsBody').appendChild(row);
});

function updateUnitDisplay(select) {
    const unit = select.options[select.selectedIndex].dataset.unit;
    const row = select.closest('tr');
    row.querySelector('.unit-display').value = unit || '';
    calculateTotal(select);
}

function calculateTotal(input) {
    const row = input.closest('tr');
    const qty = row.querySelector('.quantity-input').value || 0;
    const price = row.querySelector('.unit-price-input').value || 0;
    const total = (qty * price) || 0;
    row.querySelector('.item-total').textContent = 'Rp' + formatCurrency(total);
    updateGrandTotal();
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID').format(Math.round(value));
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const qty = row.querySelector('.quantity-input').value || 0;
        const price = row.querySelector('.unit-price-input').value || 0;
        total += (qty * price);
    });
    document.getElementById('totalAmount').textContent = 'Rp' + formatCurrency(total);
    document.getElementById('subtotal').textContent = 'Rp' + formatCurrency(total);
}

function removeItem(btn) {
    btn.closest('tr').remove();
    updateGrandTotal();
}

// Update grand total on form load
document.addEventListener('DOMContentLoaded', function() {
    updateGrandTotal();
});
</script>
@endsection
