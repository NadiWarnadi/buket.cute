<?php

namespace App\Http\Controllers\Admin;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases
     */
    public function index(Request $request)
    {
        $query = Purchase::query();

        if ($request->search) {
            $search = $request->search;
            $query->where('supplier', 'like', "%{$search}%");
        }

        $sort = $request->sort ?? 'latest';
        $query = match($sort) {
            'supplier' => $query->orderBy('supplier', 'asc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'latest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $purchases = $query->with('items')->paginate(15);

        return view('admin.purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new purchase
     */
    public function create()
    {
        $ingredients = Ingredient::orderBy('name')->get();
        return view('admin.purchases.create', compact('ingredients'));
    }

    /**
     * Store a newly created purchase in storage
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

        DB::beginTransaction();

        try {
            // Hitung total
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['price'];
            }

            // Buat purchase
            $purchase = Purchase::create([
                'supplier' => $validated['supplier'],
                'total' => $total,
            ]);

            // Tambah purchase items dan update stok
            foreach ($validated['items'] as $item) {
                // Buat purchase item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                // Update stok ingredient
                $ingredient = Ingredient::findOrFail($item['ingredient_id']);
                $ingredient->stock = $ingredient->stock + $item['quantity'];
                $ingredient->save();

                // Log stock movement
                StockMovement::create([
                    'ingredient_id' => $item['ingredient_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'description' => "Pembelian dari {$validated['supplier']}",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.purchases.show', $purchase)
                            ->with('success', 'Pembelian bahan berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase
     */
    public function show(Purchase $purchase)
    {
        $purchase->load('items.ingredient');
        return view('admin.purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified purchase
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load('items');
        $ingredients = Ingredient::orderBy('name')->get();
        return view('admin.purchases.edit', compact('purchase', 'ingredients'));
    }

    /**
     * Update the specified purchase in storage
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Revert stok lama
            foreach ($purchase->items as $oldItem) {
                $ingredient = Ingredient::findOrFail($oldItem->ingredient_id);
                $ingredient->stock = $ingredient->stock - $oldItem->quantity;
                $ingredient->save();

                StockMovement::where('purchase_id', $purchase->id)
                            ->where('ingredient_id', $oldItem->ingredient_id)
                            ->delete();
            }

            // Hapus items lama
            $purchase->items()->delete();

            // Hitung total baru
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['price'];
            }

            // Update purchase
            $purchase->update([
                'supplier' => $validated['supplier'],
                'total' => $total,
            ]);

            // Tambah items baru
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                // Update stok
                $ingredient = Ingredient::findOrFail($item['ingredient_id']);
                $ingredient->stock = $ingredient->stock + $item['quantity'];
                $ingredient->save();

                // Log stock movement
                StockMovement::create([
                    'ingredient_id' => $item['ingredient_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'description' => "Pembelian dari {$validated['supplier']} (update)",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.purchases.show', $purchase)
                            ->with('success', 'Pembelian berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase from storage
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            // Revert stok
            foreach ($purchase->items as $item) {
                $ingredient = Ingredient::findOrFail($item->ingredient_id);
                $ingredient->stock = $ingredient->stock - $item->quantity;
                $ingredient->save();
            }

            // Hapus stock movements
            StockMovement::where([
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
            ])->delete();

            // Hapus purchase
            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();

            return redirect()->route('admin.purchases.index')
                            ->with('success', 'Pembelian berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
