<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\Media; // Tambahkan ini
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function dashboard()
    {
        $totalProducts = Product::count();
        return view('dashboard', compact('totalProducts'));
    }

    public function index()
    {   
        $products = Product::with(['category', 'featured_media']) // Tambahkan featured_media di sini
                       ->when(request('category_id'), function ($q) {
                           return $q->where('category_id', request('category_id'));
                          });

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

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }
public function store(Request $request)
{
    // 1. Validasi semua input yang dibutuhkan
    $validated = $request->validate([
        'category_id' => 'required|exists:categories,id',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'price'       => 'required|numeric|min:0',
        'stock'       => 'required|integer|min:0',
        'files.*'     => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4|max:20480',
    ]);

    // 2. Buat Slug Otomatis (Gunakan Str agar tidak bentrok)
    $slug = \Illuminate\Support\Str::slug($request->name);
    
    // Tambahkan angka unik jika slug sudah ada
    $originalSlug = $slug;
    $count = 1;
    while (\App\Models\Product::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $count++;
    }

    // 3. GABUNGKAN data validasi dengan slug
    // JANGAN hanya kirim ['slug' => $slug]
    $productData = array_merge($validated, ['slug' => $slug]);

    // 4. Simpan Produk dengan SEMUA data
    $product = \App\Models\Product::create($productData);

    // 5. Handle Multiple Upload
    if ($request->hasFile('files')) {
        $this->storeProductImages($product, $request->file('files'));
    }

    return redirect()->route('admin.products.index')
                    ->with('success', 'Produk berhasil ditambahkan.');
}


    public function show(Product $product)
    {
        $product->load('category', 'media');
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:20480',
        ]);

        $product->update($validated);

        if ($request->hasFile('files')) {
            // Jika upload baru, kita bisa pilih mau menghapus yang lama atau menambah
            // Di sini saya buat menambah media baru
            $this->storeProductImages($product, $request->file('files'));
        }

        return redirect()->route('admin.products.index')
                        ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->media as $media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
        }

        $product->delete();
        return redirect()->route('admin.products.index')
                        ->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * PERBAIKAN: Fungsi untuk menangani banyak file sekaligus
     */
    private function storeProductImages(Product $product, $files)
    {    
        foreach ($files as $index => $file) {
            // Keamanan: Nama file unik
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $fileName, 'public');

            // Cek apakah produk sudah punya featured image
            $hasFeatured = $product->media()->where('is_featured', 1)->exists();

            $product->media()->create([
                'model_type' => get_class($product),
                'model_id'   => $product->id,
                'file_path'  => $path,
                'file_name'  => $fileName,
                'mime_type'  => $file->getClientMimeType(),
                'size'       => $file->getSize(),
                // File pertama jadi featured HANYA jika belum ada featured sebelumnya
                'is_featured' => (!$hasFeatured && $index === 0) ? 1 : 0,
            ]);
        }
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate(['stock' => 'required|integer|min:0']);
        $product->update($validated);

        return $request->expectsJson() 
            ? response()->json(['message' => 'Stok berhasil diperbarui.', 'stock' => $product->stock])
            : back()->with('success', 'Stok berhasil diperbarui.');
    }
}
