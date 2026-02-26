@extends('layouts.admin')

@section('title', 'Konfirmasi Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-lock"></i> Konfirmasi Password</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Ini adalah area yang aman. Silakan konfirmasi password Anda sebelum melanjutkan.</p>

                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-secondary">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
