@extends('layouts.admin')

@section('title', 'Chat Pelanggan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3">Pesan Pelanggan</h1>
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
            <div class="list-group list-group-flush">
                @foreach($customers as $customer)
                    <a href="{{ route('admin.chat.show', $customer) }}" class="list-group-item list-group-item-action p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="mb-1">{{ $customer->name }}</h6>
                                <p class="mb-0 text-muted small">
                                    {{ $customer->formatted_phone ?? $customer->phone }}
                                </p>
                            </div>
                            <div class="col-auto text-end">
                                @if($customer->messages && $customer->messages->count())
                                    <small class="d-block text-muted">{{ $customer->messages->first()->created_at->diffForHumans() }}</small>
                                    <small>{{ Str::limit($customer->messages->first()->body, 30) }}</small>
                                @else
                                    <small class="text-muted">Belum ada pesan</small>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="d-flex justify-content-center p-3">
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-5 text-center">
                <i class="bi bi-chat-dots" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada pesan dari pelanggan</p>
            </div>
        @endif
    </div>
</div>
@endsection
