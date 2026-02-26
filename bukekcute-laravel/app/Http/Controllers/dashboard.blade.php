@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <h1>Selamat Datang, {{ Auth::user()->name }}!</h1>
            <p class="text-muted">Ini adalah halaman dashboard admin Toko Bucket Cutie.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Pesanan</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="bi bi-cart fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Produk</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="bi bi-box fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Stok Menipis</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Placeholder untuk grafik atau aktivitas terbaru -->
    <div class="row mt-5">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    Aktivitas Terbaru
                </div>
                <div class="card-body">
                    <p class="text-muted">Belum ada aktivitas.</p>
                </div>
            </div>
        </div>
    </div>
@endsection