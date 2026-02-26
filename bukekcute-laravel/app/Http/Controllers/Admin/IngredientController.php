<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IngredientController extends Controller
{
    /**
     * Display a listing of the ingredients.
     */
    public function index()
    {
        $ingredients = Ingredient::paginate(15);
        $lowStockCount = Ingredient::whereNotNull('min_stock')
                                   ->whereRaw('stock <= min_stock')
                                   ->count();

        return view('admin.ingredients.index', compact('ingredients', 'lowStockCount'));
    }

    /**
     * Show the form for creating a new ingredient.
     */
    public function create()
    {
        return view('admin.ingredients.create');
    }

    /**
     * Store a newly created ingredient in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        Ingredient::create($validated);

        return redirect()->route('admin.ingredients.index')
                        ->with('success', 'Bahan berhasil ditambahkan.');
    }

    /**
     * Display the specified ingredient.
     */
    public function show(Ingredient $ingredient)
    {
        $ingredient->load('products', 'stockMovements');
        $stockMovements = $ingredient->stockMovements()->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.ingredients.show', compact('ingredient', 'stockMovements'));
    }

    /**
     * Show the form for editing the specified ingredient.
     */
    public function edit(Ingredient $ingredient)
    {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    /**
     * Update the specified ingredient in storage.
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        $ingredient->update($validated);

        return redirect()->route('admin.ingredients.index')
                        ->with('success', 'Bahan berhasil diperbarui.');
    }

    /**
     * Remove the specified ingredient from storage.
     */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')
                        ->with('success', 'Bahan berhasil dihapus.');
    }

    /**
     * Update ingredient stock (quick update).
     */
    public function updateStock(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $oldStock = $ingredient->stock;
        $newStock = $validated['stock'];
        $difference = $newStock - $oldStock;

        $ingredient->update(['stock' => $newStock]);

        // Record stock movement
        if ($difference != 0) {
            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => $difference > 0 ? 'in' : 'out',
                'quantity' => abs($difference),
                'description' => $validated['description'] ?? 'Update stok manual',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stok berhasil diperbarui.']);
        }

        return back()->with('success', 'Stok berhasil diperbarui.');
    }
}
