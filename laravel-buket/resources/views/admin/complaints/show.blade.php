@extends('layouts.admin')

@section('title', 'Detail Komplain #' . $complaint->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Komplain</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">ID</dt>
                        <dd class="col-sm-9">#{{ $complaint->id }}</dd>
                        
                        <dt class="col-sm-3">Customer</dt>
                        <dd class="col-sm-9">
                            {{ $complaint->customer->name ?? $complaint->customer->phone }}
                            <a href="{{ route('admin.chat.show', $complaint->customer_id) }}" class="btn btn-sm btn-link">Chat</a>
                        </dd>
                        
                        <dt class="col-sm-3">Order Terkait</dt>
                        <dd class="col-sm-9">
                            @if($complaint->order)
                                <a href="{{ route('admin.orders.show', $complaint->order) }}">#{{ $complaint->order->id }}</a>
                            @else
                                Tidak ada order terkait
                            @endif
                        </dd>
                        
                        <dt class="col-sm-3">Pesan Keluhan</dt>
                        <dd class="col-sm-9">{{ nl2br(e($complaint->message)) }}</dd>
                        
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <form action="{{ route('admin.complaints.update', $complaint) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <select name="status" class="form-control form-control-sm" style="width: auto; display: inline-block;">
                                        <option value="open" {{ $complaint->status == 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $complaint->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ $complaint->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ $complaint->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                                </div>
                            </form>
                        </dd>
                        
                        <dt class="col-sm-3">Dibuat</dt>
                        <dd class="col-sm-9">{{ $complaint->created_at->format('d/m/Y H:i:s') }}</dd>
                        
                        @if($complaint->resolved_at)
                        <dt class="col-sm-3">Selesai</dt>
                        <dd class="col-sm-9">{{ $complaint->resolved_at->format('d/m/Y H:i:s') }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.complaints.index') }}" class="btn btn-secondary">Kembali</a>
                    <a href="{{ route('admin.chat.show', $complaint->customer_id) }}" class="btn btn-primary">Buka Chat Customer</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection