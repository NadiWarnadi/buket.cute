<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /**
     * Display a listing of ingredients
     */
    public function index(Request $request)
    {
        $query = Ingredient::query();

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Sort
        $sort = $request->sort ?? 'latest';
        $query = match ($sort) {
            'name-asc' => $query->orderBy('name', 'asc'),
            'name-desc' => $query->orderBy('name', 'desc'),
            'stock-low' => $query->orderBy('stock', 'asc'),
            'stock-high' => $query->orderBy('stock', 'desc'),
            'latest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $ingredients = $query->paginate(15);
        $lowStockCount = Ingredient::whereRaw('stock <= min_stock AND min_stock > 0')->count();

        return view('admin.ingredients.index', compact('ingredients', 'lowStockCount'));
    }

    /**
     * Show the form for creating a new ingredient
     */
    public function create()
    {
        return view('admin.ingredients.create');
    }

    /**
     * Store a newly created ingredient in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        $ingredient = Ingredient::create($validated);

        // Log stock movement
        if ($validated['stock'] > 0) {
            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'in',
                'quantity' => $validated['stock'],
                'description' => 'Stok awal saat pembuatan bahan',
            ]);
        }

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    /**
     * Display the specified ingredient
     */
    public function show(Ingredient $ingredient)
    {
        $ingredient->load('productIngredients.product', 'stockMovements');
        $stockHistory = $ingredient->stockMovements()->latest()->paginate(20);

        return view('admin.ingredients.show', compact('ingredient', 'stockHistory'));
    }

    /**
     * Show the form for editing the specified ingredient
     */
    public function edit(Ingredient $ingredient)
    {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    /**
     * Update the specified ingredient in storage
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,'.$ingredient->id,
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        $ingredient->update($validated);

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Bahan baku berhasil diperbarui.');
    }

    /**
     * Remove the specified ingredient from storage
     */
    public function destroy(Ingredient $ingredient)
    {
        if ($ingredient->products()->exists()) {
            return redirect()->route('admin.ingredients.index')
                ->with('error', 'Tidak dapat menghapus bahan yang sudah digunakan dalam produk.');
        }

        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }

    /**
     * Update stock untuk bahan baku
     */
    public function updateStock(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:in,out',
            'description' => 'nullable|string|max:500',
        ]);

        $oldStock = $ingredient->stock;
        $quantity = $validated['quantity'];
        $type = $validated['type'];

        // Update stok
        if ($type === 'in') {
            $ingredient->stock = $ingredient->stock + $quantity;
            $ingredient->save();
        } else {
            if ($ingredient->stock < $quantity) {
                return back()->with('error', 'Stok tidak mencukupi untuk pengurangan.');
            }
            $ingredient->stock = $ingredient->stock - $quantity;
            $ingredient->save();
        }

        // Log stock movement
        StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => $type,
            'quantity' => $quantity,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.ingredients.show', $ingredient)
            ->with('success', "Stok berhasil diperbarui dari {$oldStock} menjadi {$ingredient->stock}.");
    }
}
