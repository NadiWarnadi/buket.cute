<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::with('category', 'media')
                           ->when(request('category_id'), function ($q) {
                               return $q->where('category_id', request('category_id'));
                           })
                           ->when(request('search'), function ($q) {
                               return $q->where('name', 'like', '%' . request('search') . '%');
                           })
                           ->paginate(15);

        $categories = Category::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product = Product::create($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            $this->storeProductImage($product, $request->file('image'));
        }

        return redirect()->route('admin.products.index')
                        ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load('category', 'media');
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product->update($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            $oldMedia = $product->media()->where('is_featured', true)->first();
            if ($oldMedia) {
                Storage::delete($oldMedia->file_path);
                $oldMedia->delete();
            }

            $this->storeProductImage($product, $request->file('image'));
        }

        return redirect()->route('admin.products.index')
                        ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        // Delete all associated media
        foreach ($product->media as $media) {
            Storage::delete($media->file_path);
            $media->delete();
        }

        $product->delete();

        return redirect()->route('admin.products.index')
                        ->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Store product image.
     */
    private function storeProductImage(Product $product, $image)
    {
        $path = $image->store('products', 'public');

        $product->media()->create([
            'file_path' => $path,
            'file_name' => $image->getClientOriginalName(),
            'mime_type' => $image->getMimeType(),
            'size' => $image->getSize(),
            'is_featured' => true,
        ]);
    }

    /**
     * Update product stock.
     */
    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stok berhasil diperbarui.', 'stock' => $product->stock]);
        }

        return back()->with('success', 'Stok berhasil diperbarui.');
    }
}
