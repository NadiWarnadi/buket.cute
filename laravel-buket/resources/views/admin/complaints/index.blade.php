@extends('layouts.admin')

@section('title', 'Daftar Komplain')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Komplain</h3>
                    <div class="card-tools">
                        <form method="GET" class="form-inline">
                            <label class="mr-2">Filter Status:</label>
                            <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="open" {{ $status == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Pesan</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaints as $complaint)
                            <tr>
                                <td>#{{ $complaint->id }}</td>
                                <td>{{ $complaint->customer->name ?? $complaint->customer->phone }}</td>
                                <td>{{ Str::limit($complaint->message, 50) }}</td>
                                <td>
                                    @if($complaint->order)
                                        <a href="{{ route('admin.orders.show', $complaint->order) }}">#{{ $complaint->order->id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = [
                                            'open' => 'danger',
                                            'in_progress' => 'warning',
                                            'resolved' => 'success',
                                            'closed' => 'secondary',
                                        ][$complaint->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $complaint->status)) }}</span>
                                </td>
                                <td>{{ $complaint->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.complaints.show', $complaint) }}" class="btn btn-sm btn-info">Detail</a>
                                    <a href="{{ route('admin.chat.show', $complaint->customer_id) }}" class="btn btn-sm btn-primary">Chat</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada komplain.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    {{ $complaints->appends(['status' => $status])->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection