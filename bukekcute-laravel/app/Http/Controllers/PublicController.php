<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Beranda (Home Page)
     */
    public function home()
    {
        // Featured products (produk unggulan)
        $featured = Product::where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        // Latest products
        $latest = Product::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // Categories for preview
        $categories = Category::withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->limit(8)->get();

        return view('public.home', compact('featured', 'latest', 'categories'));
    }

    /**
     * Katalog Produk (Product Catalog)
     */
    public function catalog(Request $request)
    {
        $query = Product::where('is_active', true);

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        $query = match($sort) {
            'price-low' => $query->orderBy('price', 'asc'),
            'price-high' => $query->orderBy('price', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        // Pagination
        $products = $query->paginate(12);
        $categories = Category::all();

        return view('public.catalog', compact('products', 'categories'));
    }

    /**
     * Detail Produk (Product Detail)
     */
    public function detail($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Related products
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('public.product-detail', compact('product', 'related'));
    }

    /**
     * Tentang Kami (About Us)
     */
    public function about()
    {
        return view('public.about');
    }

    /**
     * Kontak (Contact)
     */
    public function contact()
    {
        return view('public.contact');
    }

    /**
     * FAQ / Cara Pemesanan
     */
    public function faq()
    {
        return view('public.faq');
    }

    /**
     * Custom Request
     */
    public function customRequest()
    {
        return view('public.custom-request');
    }

    /**
     * Send custom request to WhatsApp
     */
    public function submitCustomRequest(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'description' => 'required|string|max:1000',
            'budget' => 'nullable|string|max:100',
        ]);

        // Format WhatsApp message
        $message = "Halo 👋\n\n";
        $message .= "Nama: {$validated['name']}\n";
        $message .= "Nomor: {$validated['phone']}\n";
        $message .= "Permintaan Custom:\n{$validated['description']}\n";
        if ($validated['budget']) {
            $message .= "Budget: {$validated['budget']}\n";
        }

        // Get store WhatsApp from env
        $storeWhatsApp = env('STORE_WHATSAPP', '6281234567890');
        $whatsAppUrl = "https://wa.me/{$storeWhatsApp}?text=" . urlencode($message);

        return back()->with('success', 'Silakan klik tombol WhatsApp di bawah untuk mengirim permintaan custom Anda!')
            ->with('whatsapp_url', $whatsAppUrl);
    }

    /**
     * Send product order to WhatsApp
     */
    public function orderToWhatsApp(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($validated['product_id']);
        $quantity = $validated['quantity'];

        // Check stock
        if ($product->stock < $quantity) {
            return back()->with('error', 'Stok produk tidak sesuai dengan pesanan Anda');
        }

        // Format WhatsApp message
        $message = "Halo 👋\n\n";
        $message .= "Saya ingin memesan:\n";
        $message .= "*Produk:* {$product->name}\n";
        $message .= "*Jumlah:* {$quantity}\n";
        $message .= "*Harga per item:* Rp " . number_format($product->price, 0, ',', '.') . "\n";
        $message .= "*Total:* Rp " . number_format($product->price * $quantity, 0, ',', '.') . "\n";
        if ($product->description) {
            $message .= "\n*Deskripsi:*\n{$product->description}\n";
        }

        $storeWhatsApp = env('STORE_WHATSAPP', '6281234567890');
        $whatsAppUrl = "https://wa.me/{$storeWhatsApp}?text=" . urlencode($message);

        return response()->json([
            'success' => true,
            'whatsapp_url' => $whatsAppUrl,
            'message' => 'Pesanan siap dikirim ke WhatsApp!'
        ]);
    }
}
