@extends('layouts.admin')

@section('title', 'Catat Pembelian')

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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Catat Pembelian Bahan Baku</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchaseForm">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="supplier" class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier') }}" required autofocus>
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
                                    <!-- Dynamically added items -->
                                </tbody>
                            </table>
                        </div>

                        @error('items')
                            <div class="alert alert-danger">{{ $message }}</div>
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
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Simpan Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let ingredients = @json(\App\Models\Ingredient::select('id', 'name', 'unit')->get());
let itemCount = 0;

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
            <small class="text-danger item-error" style="display:none;"></small>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm unit-display" disabled>
        </td>
        <td>
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity-input" min="1" required onchange="calculateTotal(this)">
            <small class="text-danger item-error" style="display:none;"></small>
        </td>
        <td>
            <input type="number" name="items[${itemCount}][unit_price]" class="form-control form-control-sm unit-price-input" step="0.01" min="0" required onchange="calculateTotal(this)">
            <small class="text-danger item-error" style="display:none;"></small>
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
}

function calculateTotal(input) {
    const row = input.closest('tr');
    const qty = row.querySelector('.quantity-input').value;
    const price = row.querySelector('.unit-price-input').value;
    const total = (qty * price) || 0;
    row.querySelector('.item-total').textContent = 'Rp' + formatCurrency(total);
    updateGrandTotal();
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
