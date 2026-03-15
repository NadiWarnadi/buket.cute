<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index()
    {
        $customers = Customer::query()
            ->when(request('search'), function ($q) {
                $search = request('search');

                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->when(request('sort'), function ($q) {
                $sort = request('sort');

                return match ($sort) {
                    'name-asc' => $q->orderBy('name', 'asc'),
                    'name-desc' => $q->orderBy('name', 'desc'),
                    'phone-asc' => $q->orderBy('phone', 'asc'),
                    'latest' => $q->orderBy('created_at', 'desc'),
                    'oldest' => $q->orderBy('created_at', 'asc'),
                    default => $q->orderBy('created_at', 'desc'),
                };
            }, fn ($q) => $q->orderBy('created_at', 'desc'))
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created customer in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'address' => 'nullable|string|max:500',
        ]);

        Customer::create($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $customer->load(['orders', 'conversations']);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Display customer by phone number (for quick access from chat)
     */
    public function showByPhone($phone)
    {
        $customer = Customer::where('phone', $phone)->firstOrFail();
        $customer->load(['orders', 'conversations']);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,'.$customer->id,
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Remove the specified customer from storage
     */
    public function destroy(Customer $customer)
    {
        // Cek apakah customer memiliki orders yang aktif
        if ($customer->orders()->exists()) {
            return redirect()->route('admin.customers.index')
                ->with('error', 'Tidak dapat menghapus pelanggan yang memiliki pesanan.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
