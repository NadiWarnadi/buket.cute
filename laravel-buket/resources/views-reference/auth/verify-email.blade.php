@extends('layouts.admin')

@section('title', 'Verifikasi Email')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-envelope-check"></i> Verifikasi Email</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Terima kasih telah mendaftar! Sebelum melanjutkan, silakan verifikasi email Anda dengan mengklik link yang kami kirimkan.</p>

                @if(session('resent'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> Link verifikasi baru telah dikirim ke email Anda
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <p class="text-muted mb-3">Tidak menerima email?</p>

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-info">Kirim Ulang Link Verifikasi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
