@extends('layouts.admin')

@section('title', 'Daftar Pelanggan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">Daftar Pelanggan</h1>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Pelanggan Baru
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($customers->count())
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Nama</th>
                            <th style="width: 20%">Telepon</th>
                            <th style="width: 25%">Email</th>
                            <th style="width: 10%">Pesanan</th>
                            <th style="width: 10%">Kota</th>
                            <th style="width: 10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <strong>{{ $customer->name }}</strong>
                                </td>
                                <td>
                                    <a href="https://wa.me/{{ $customer->getWhatsAppNumber() }}" target="_blank" class="text-decoration-none">
                                        {{ $customer->phone }}
                                    </a>
                                </td>
                                <td>
                                    {{ $customer->email ?? '-' }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $customer->orders->count() }}</span>
                                </td>
                                <td>
                                    {{ $customer->city ?? '-' }}
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-info" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center p-3">
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada pelanggan</p>
                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Pelanggan
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
