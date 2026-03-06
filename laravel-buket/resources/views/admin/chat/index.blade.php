@extends('layouts.admin')

@section('title', 'Chat & Percakapan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">💬 Chat & Percakapan</h1>
            <div class="badge bg-info">
                {{ $customers->total() }} Pelanggan
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Search & Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="search" class="form-label">Cari Customer atau No. Telepon</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nama atau nomor telepon..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status Chat</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archive</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Ditutup</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="{{ route('admin.chat.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Chat List -->
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($customers->count())
                    <div class="list-group list-group-flush">
                        @foreach($customers as $customer)
                            <a href="{{ route('admin.chat.show', $customer) }}" class="list-group-item list-group-item-action p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 d-flex align-items-center gap-2">
                                            {{ $customer->name ?? $customer->phone }}
                                            @php
                                                $chatStatus = $customer->getChatStatus();
                                            @endphp
                                            @if($chatStatus === 'active')
                                                <span class="badge bg-success">Aktif</span>
                                            @elseif($chatStatus === 'archived')
                                                <span class="badge bg-warning">Archive</span>
                                            @else
                                                <span class="badge bg-secondary">Ditutup</span>
                                            @endif
                                        </h6>
                                        <p class="text-muted small mb-1">
                                            <i class="bi bi-telephone"></i> {{ $customer->phone }}
                                        </p>
                                        @php
                                            $lastMessage = $customer->getLastMessage();
                                        @endphp
                                        @if($lastMessage)
                                            <p class="text-muted small mb-0">
                                                @if($lastMessage->is_incoming)
                                                    <i class="bi bi-arrow-down-left text-info"></i>
                                                @else
                                                    <i class="bi bi-arrow-up-right text-success"></i>
                                                @endif
                                                {{ Str::limit($lastMessage->body, 80) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-end small text-muted">
                                        @if($lastMessage)
                                            <div>{{ $lastMessage->created_at->format('d M') }}</div>
                                            <div>{{ $lastMessage->created_at->format('H:i') }}</div>
                                        @else
                                            <div>-</div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="p-3 border-top">
                        {{ $customers->links() }}
                    </div>
                @else
                    <div class="p-5 text-center">
                        <i class="bi bi-chat-left text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">Belum ada percakapan</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .list-group-item-action:hover {
        background-color: #f8f9fa;
    }
    
    .list-group-item {
        transition: background-color 0.2s ease;
    }
</style>
@endsection

