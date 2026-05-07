<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media; // Tambahkan ini
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;

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
                return $q->where('name', 'like', '%'.request('search').'%');
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
    // 1. Validasi
    $validated = $request->validate([
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4|max:20480',
    ]);

    return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated) {
        // 2. Buat Slug (Logika slug sudah otomatis jika Anda pakai Booted di model, 
        // tapi jika ingin manual di sini juga oke)
        $slug = \Illuminate\Support\Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;
        while (\App\Models\Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        // 3. Simpan Produk
        $product = \App\Models\Product::create(array_merge($validated, ['slug' => $slug]));

        // 4. Handle Multiple Upload via Polymorphic
        if ($request->hasFile('files')) {
    $manager = new ImageManager(new Driver());
    
    foreach ($request->file('files') as $index => $file) {
        $mime = $file->getMimeType();
        
        if (str_starts_with($mime, 'image/')) {
            // Konversi ke WebP 800px
            $fileName = uniqid() . '.webp';
            $path = 'products/' . $fileName;
            
            $image = $manager->read($file);
            $image->scale(width: 800);
            
            // Simpan ke storage
            file_put_contents(storage_path('app/public/' . $path), $image->toWebp(80));
            
            $fileType = 'image';
            $mimeFinal = 'image/webp';
        } else {
            // Video simpan biasa
            $path = $file->store('products', 'public');
            $fileType = 'video';
            $mimeFinal = $mime;
        }

        $product->media()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeFinal,
            'size' => $file->getSize(),
            'file_type' => $fileType,
            'collection' => 'product_gallery',
            'is_featured' => ($index === 0), // File pertama otomatis jadi thumbnail
        ]);
    }
}


        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    });
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
        'category_id'        => 'required|exists:categories,id',
        'name'               => 'required|string|max:255',
        'description'        => 'nullable|string',
        'price'              => 'required|numeric|min:0',
        'stock'              => 'required|integer|min:0',
        'is_active'          => 'boolean',
         'collection' => 'product_gallery',
        'media'              => 'nullable|array',
        'media.*'            => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',

        'featured_media_id'  => 'nullable|exists:media,id',
        'delete_media'       => 'nullable|array',
        'delete_media.*'     => 'exists:media,id',
    ]);

    // Update data dasar
    $product->update($validated);

    // 1. Hapus hanya media yang dipilih (tidak semua)
    if ($request->has('delete_media')) {
        // Hanya hapus media yang id-nya ada di array delete_media
        Media::whereIn('id', $request->delete_media)
             ->where('model_type', Product::class)
             ->where('model_id', $product->id)
             ->delete();
    }

    // 2. Ubah featured ke media yang dipilih
    if ($request->filled('featured_media_id')) {
        // Reset semua featured produk ini dulu
        $product->media()->update(['is_featured' => false]);
        // Set featured baru
        $product->media()->where('id', $request->featured_media_id)->update(['is_featured' => true]);
    }

    // 3. Upload file baru (hanya menambah, tidak mengganggu yang lama)
  // 3. Upload file baru (hanya menambah, tidak mengganggu yang lama)
if ($request->hasFile('media')) {
    $manager = new ImageManager(new Driver());

    foreach ($request->file('media') as $file) {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            // Konversi gambar ke WebP 800px
            $fileName = uniqid() . '.webp';
            $path = 'products/' . $fileName;
            
            $image = $manager->read($file);
            $image->scale(width: 800);
            $image->toWebp(80)->save(storage_path('app/public/' . $path));
            
            $fileType = 'image';
            $mimeFinal = 'image/webp';
        } else {
            // Video simpan biasa
            $path = $file->store('products', 'public');
            $fileType = 'video';
            $mimeFinal = $mime;
        }

        $product->media()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeFinal,
            'size' => $file->getSize(),
            'file_type' => $fileType,
            'is_featured' => false, // Fitur featured diatur sendiri via featured_media_id
        ]);
    }
}


    return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
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
    // private function storeProductImages(Product $product, $files)
    // {
    //     foreach ($files as $index => $file) {
    //         // Keamanan: Nama file unik
    //         $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
    //         $path = $file->storeAs('products', $fileName, 'public');

    //         // Cek apakah produk sudah punya featured image
    //         $hasFeatured = $product->media()->where('is_featured', 1)->exists();

    //         $product->media()->create([
    //             'model_type' => get_class($product),
    //             'model_id' => $product->id,
    //             'file_path' => $path,
    //             'file_name' => $fileName,
    //             'mime_type' => $file->getClientMimeType(),
    //             'size' => $file->getSize(),
    //             // File pertama jadi featured HANYA jika belum ada featured sebelumnya
    //             'is_featured' => (! $hasFeatured && $index === 0) ? 1 : 0,
    //         ]);
    //     }
    // }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate(['stock' => 'required|integer|min:0']);
        $product->update($validated);

        return $request->expectsJson()
            ? response()->json(['message' => 'Stok berhasil diperbarui.', 'stock' => $product->stock])
            : back()->with('success', 'Stok berhasil diperbarui.');
    }
}
