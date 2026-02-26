<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('admin.orders.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ], [
            'customer_id.required' => 'Pelanggan harus dipilih',
            'items.required' => 'Minimal ada 1 item pesanan',
            'items.*.product_id.exists' => 'Produk tidak ditemukan',
            'items.*.quantity.min' => 'Qty minimal 1',
            'items.*.price.min' => 'Harga tidak boleh negatif',
        ]);

        // At least one item must have product_id or custom_description
        $hasValidItem = false;
        foreach ($validated['items'] as $item) {
            if ($item['product_id'] || $item['custom_description']) {
                $hasValidItem = true;
                break;
            }
        }

        if (!$hasValidItem) {
            return back()->withErrors(['items' => 'Minimal ada 1 item dengan produk atau deskripsi custom']);
        }

        try {
            $totalPrice = 0;

            // Create order
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'total_price' => 0, // Will be recalculated
                'status' => Order::STATUS_PENDING,
                'notes' => $validated['notes'],
            ]);

            // Create order items
            foreach ($validated['items'] as $itemData) {
                if (!$itemData['product_id'] && !$itemData['custom_description']) {
                    continue;
                }

                $subtotal = $itemData['quantity'] * $itemData['price'];
                $totalPrice += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'custom_description' => $itemData['custom_description'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'subtotal' => $subtotal,
                ]);
            }

            // Update total price
            $order->update(['total_price' => $totalPrice]);

            return redirect()->route('admin.orders.show', $order)->with('success', 'Pesanan berhasil dibuat');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $order->load('customer', 'items.product', 'messages');
        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if (!$order->canBeUpdated()) {
            return back()->with('error', 'Pesanan tidak bisa diubah (status: ' . $order->getStatusLabel() . ')');
        }

        $order->load('items');
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('admin.orders.edit', compact('order', 'products', 'customers'));
    }

    public function update(Request $request, Order $order)
    {
        if (!$order->canBeUpdated()) {
            return back()->with('error', 'Pesanan tidak bisa diubah');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $order->update([
                'customer_id' => $validated['customer_id'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
            ]);

            // Delete removed items
            $itemIds = collect($validated['items'])->pluck('id')->filter()->values()->toArray();
            $order->items()->whereNotIn('id', $itemIds)->delete();

            // Create/update order items
            $totalPrice = 0;
            foreach ($validated['items'] as $itemData) {
                if (!$itemData['product_id'] && !$itemData['custom_description']) {
                    continue;
                }

                $subtotal = $itemData['quantity'] * $itemData['price'];
                $totalPrice += $subtotal;

                if ($itemData['id']) {
                    OrderItem::find($itemData['id'])->update([
                        'product_id' => $itemData['product_id'],
                        'custom_description' => $itemData['custom_description'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $subtotal,
                    ]);
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'custom_description' => $itemData['custom_description'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            $order->update(['total_price' => $totalPrice]);

            return redirect()->route('admin.orders.show', $order)->with('success', 'Pesanan berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        if (!$order->canBeCancelled()) {
            return back()->with('error', 'Pesanan tidak bisa dibatalkan (status: ' . $order->getStatusLabel() . ')');
        }

        try {
            $order->update(['status' => Order::STATUS_CANCELLED]);
            return redirect()->route('admin.orders.index')->with('success', 'Pesanan berhasil dibatalkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Update order status
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
        ]);

        if (!$order->canBeUpdated() && $validated['status'] !== Order::STATUS_CANCELLED) {
            return back()->with('error', 'Status tidak bisa diubah');
        }

        $order->update(['status' => $validated['status']]);
        return back()->with('success', 'Status pesanan diperbarui');
    }
}
