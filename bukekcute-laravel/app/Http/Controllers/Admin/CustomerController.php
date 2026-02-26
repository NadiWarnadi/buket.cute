<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('orders')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|unique:customers,phone',
           
            'address' => 'nullable|string',
            
        ], [
            'name.required' => 'Nama pelanggan harus diisi',
            'phone.required' => 'Nomor telepon harus diisi',
            'phone.unique' => 'Nomor telepon sudah terdaftar',
           
        ]);

        Customer::create($validated);
        return redirect()->route('admin.customers.index')->with('success', 'Pelanggan berhasil ditambahkan');
    }

    public function show(Customer $customer)
    {
        $customer->load('orders', 'messages');
        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|unique:customers,phone,' . $customer->id,
          
            'address' => 'nullable|string',
           
            
        ]);

        $customer->update($validated);
        return redirect()->route('admin.customers.index')->with('success', 'Pelanggan berhasil diperbarui');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders->count() > 0) {
            return back()->with('error', 'Tidak bisa menghapus pelanggan yang memiliki pesanan');
        }

        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Pelanggan berhasil dihapus');
    }
}
