<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemIngredient; // Tambahan
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with('customer');

        // Search by customer name or order id
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('id', $search);
        }

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Sort
        $sort = $request->sort ?? 'latest';
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'customer' => $query->orderBy('customer_id', 'asc'),
            'total-high' => $query->orderBy('total_price', 'desc'),
            'total-low' => $query->orderBy('total_price', 'asc'),
            'latest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $orders = $query->paginate(15);
        $statuses = ['pending', 'processed', 'completed', 'cancelled'];

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.orders.create', compact('customers', 'products'));
    }

    /**
     * Store a newly created order in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:pending,processed,completed,cancelled',
              'payment_method' => 'required|in:cod,transfer,qris',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // Hitung total price
            $totalPrice = 0;
            foreach ($validated['items'] as $item) {
                $totalPrice += $item['quantity'] * $item['price'];
            }

            // Buat order
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'total_price' => $totalPrice,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                 'payment_method' => $validated['payment_method'], 
            ]);

            // Buat order items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'custom_description' => $item['custom_description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ]);
            }

            // Tambahan: jika status langsung processed, kurangi stok
            if ($order->status === 'processed') {
                $this->processOrderIngredients($order);
            }

            DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Pesanan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'items' => function ($query) {
            $query->with('product');
        }]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified order
     */
    public function edit(Order $order)
    {
        $order->load(['items' => function ($query) {
            $query->with('product');
        }]);
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.orders.edit', compact('order', 'customers', 'products'));
    }

    /**
     * Update the specified order in storage
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:pending,processed,completed,cancelled',
              'payment_method' => 'required|in:cod,transfer,qris',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // Hitung total price baru
            $totalPrice = 0;
            foreach ($validated['items'] as $item) {
                $totalPrice += $item['quantity'] * $item['price'];
            }

           $order->load('items.ingredients.ingredient');
            
            foreach ($order->items as $oldItem) {
                foreach ($oldItem->ingredients as $usedIngredient) {
                    if ($usedIngredient->ingredient) {
                        // Tambahkan kembali stok yang sempat terpotong sebelumnya
                        $usedIngredient->ingredient->increment('stock', $usedIngredient->quantity);
                    }
                }
                // Hapus log pemakaian bahan baku lama di DB agar tidak yatim/piatu
                $oldItem->ingredients()->delete();
            }
            
            // Update order
            $order->update([
                'customer_id' => $validated['customer_id'],
                'total_price' => $totalPrice,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                 'payment_method' => $validated['payment_method'], 
            ]);

            // Hapus items lama
            $order->items()->delete();

            // Buat items baru
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'custom_description' => $item['custom_description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ]);
            }

            // Tambahan: jika status menjadi processed, kurangi stok
            if ($order->status === 'processed') {
                $this->processOrderIngredients($order);
            }

            DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Pesanan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified order from storage
     */
    public function destroy(Order $order)
    {
        try {
            $order->items()->delete();
            $order->delete();

            return redirect()->route('admin.orders.index')
                ->with('success', 'Pesanan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processed,completed,cancelled',
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Tambahan: jika status berubah menjadi processed, kurangi stok (hanya sekali)
        if ($validated['status'] === 'processed' && $oldStatus !== 'processed') {
            $this->processOrderIngredients($order);
        }

        return redirect()->back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /**
     * ============================================
     * METHOD TAMBAHAN: Kurangi stok bahan baku
     * ============================================
     */
    private function processOrderIngredients(Order $order): void
    {
        // Cegah pemotongan ganda
        $alreadyProcessed = OrderItemIngredient::whereHas('orderItem', function ($q) use ($order) {
            $q->where('order_id', $order->id);
        })->exists();

        if ($alreadyProcessed) {
            return;
        }

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            if (!$product) continue; // custom item tidak ada resep

            $recipeItems = $product->productIngredients; // dari product_ingredient
            if ($recipeItems->isEmpty()) continue;

            foreach ($recipeItems as $recipe) {
                $ingredient = $recipe->ingredient;
                $quantityNeeded = $recipe->quantity * $orderItem->quantity;

                // Catat pemakaian bahan
                OrderItemIngredient::create([
                    'order_item_id' => $orderItem->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantityNeeded,
                ]);

                // Kurangi stok (biarkan minus)
                $ingredient->decrement('stock', $quantityNeeded);
            }
        }
    }
}