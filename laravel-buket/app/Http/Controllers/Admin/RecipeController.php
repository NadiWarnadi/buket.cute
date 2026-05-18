<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = ProductIngredient::with('product', 'ingredient')
            ->orderBy('product_id')
            ->paginate(20);

        return view('admin.recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('admin.recipes.create', compact('products', 'ingredients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'ingredient_id'=> 'required|exists:ingredients,id',
            'quantity'     => 'required|integer|min:1',
        ]);

        // Cegah duplikat
        $exists = ProductIngredient::where('product_id', $validated['product_id'])
                    ->where('ingredient_id', $validated['ingredient_id'])
                    ->exists();
        if ($exists) {
            return back()->with('error', 'Bahan sudah ada di resep produk ini.')->withInput();
        }

        ProductIngredient::create($validated);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Resep berhasil ditambahkan.');
    }

    public function edit($productId, $ingredientId)
    {
        $recipe = ProductIngredient::where('product_id', $productId)
                    ->where('ingredient_id', $ingredientId)->firstOrFail();
        $products = Product::orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('admin.recipes.edit', compact('recipe', 'products', 'ingredients'));
    }

    public function update(Request $request, $productId, $ingredientId)
    {
        $recipe = ProductIngredient::where('product_id', $productId)
                    ->where('ingredient_id', $ingredientId)->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $recipe->update($validated);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Resep berhasil diperbarui.');
    }

    public function destroy($productId, $ingredientId)
    {
        $recipe = ProductIngredient::where('product_id', $productId)
                    ->where('ingredient_id', $ingredientId)->firstOrFail();
        $recipe->delete();

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Resep berhasil dihapus.');
    }
}