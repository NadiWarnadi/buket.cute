<?php

namespace App\Http\Controllers\Admin;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the purchases.
     */
    public function index()
    {
        $purchases = Purchase::with('items')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create()
    {
        $ingredients = Ingredient::all();
        return view('admin.purchases.create', compact('ingredients'));
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Calculate total
        $total = 0;
        foreach ($validated['items'] as $item) {
            $total += $item['quantity'] * $item['price'];
        }

        $purchase = Purchase::create([
            'supplier' => $validated['supplier'],
            'total' => $total,
        ]);

        // Create purchase items and update ingredient stock
        foreach ($validated['items'] as $item) {
            $ingredient = Ingredient::findOrFail($item['ingredient_id']);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'ingredient_id' => $item['ingredient_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total_price' => $item['quantity'] * $item['price'],
            ]);

            // Update ingredient stock
            $ingredient->increment('stock', $item['quantity']);

            // Record stock movement
            StockMovement::create([
                'ingredient_id' => $item['ingredient_id'],
                'type' => 'in',
                'quantity' => $item['quantity'],
                'description' => "Pembelian dari {$validated['supplier']}",
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
            ]);
        }

        return redirect()->route('admin.purchases.index')
                        ->with('success', 'Pembelian berhasil dicatat.');
    }

    /**
     * Display the specified purchase.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load('items.ingredient');
        return view('admin.purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified purchase (untuk draft hanya).
     */
    public function edit(Purchase $purchase)
    {
        $ingredients = Ingredient::all();
        $purchase->load('items');
        return view('admin.purchases.edit', compact('purchase', 'ingredients'));
    }

    /**
     * Remove the specified purchase from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Revert stock movements
        foreach ($purchase->items as $item) {
            $ingredient = $item->ingredient;
            $ingredient->decrement('stock', $item->quantity);

            // Remove stock movement
            StockMovement::where('reference_type', Purchase::class)
                        ->where('reference_id', $purchase->id)
                        ->where('ingredient_id', $item->ingredient_id)
                        ->delete();
        }

        $purchase->items()->delete();
        $purchase->delete();

        return redirect()->route('admin.purchases.index')
                        ->with('success', 'Pembelian berhasil dihapus dan stok dikembalikan.');
    }
}
