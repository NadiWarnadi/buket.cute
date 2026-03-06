@extends('layouts.admin')

@section('title', 'Laporan Chat')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3">💬 Laporan Chat & Percakapan</h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Percakapan</small>
                <h3 class="mb-0">{{ $totalConversations }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Pesan</small>
                <h3 class="mb-0">{{ $totalMessages }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Rata-rata Pesan</small>
                <h3 class="mb-0">{{ number_format($avgMessagesPerConv, 1) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Percakapan Aktif</small>
                <h3 class="mb-0">{{ $activeConversations->count() }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('admin.reports.chat') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Status Breakdown & Active Conversations -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Status Percakapan</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statusBreakdown as $item)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $item->status === 'active' ? 'success' : ($item->status === 'archived' ? 'secondary' : 'danger') }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td>{{ $item->count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Percakapan Aktif Terbaru</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Pelanggan</th>
                            <th>Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeConversations as $conv)
                            @if($conv->customer)
                                <tr>
                                    <td>{{ $conv->customer->name ?? 'Unknown' }}</td>
                                    <td>
                                        <small>{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '-' }}</small>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">Belum ada percakapan aktif</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Daily Messages Chart -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Pesan Harian</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyMessages as $item)
                            <tr>
                                <td>{{ Carbon\Carbon::parse($item->date)->format('d M Y') }}</td>
                                <td>{{ $item->count }}</td>
                                <td>{{ $item->incoming }}</td>
                                <td>{{ $item->outgoing }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada pesan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
                    <select class="form-select" id="type" name="type">
                        <option value="">-- Semua --</option>
                        <option value="incoming" {{ request('type') === 'incoming' ? 'selected' : '' }}>Masuk</option>
                        <option value="outgoing" {{ request('type') === 'outgoing' ? 'selected' : '' }}>Keluar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        🔍 Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Total Pesan</small>
                    <h4 class="mb-0">{{ $totalMessages ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Pesan Masuk</small>
                    <h4 class="mb-0 text-info">{{ $incomingCount ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Pesan Keluar</small>
                    <h4 class="mb-0 text-success">{{ $outgoingCount ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <small class="text-muted d-block">Pelanggan Aktif</small>
                    <h4 class="mb-0 text-warning">{{ $activeCustomers ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Top Customers -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">👥 Pelanggan Paling Aktif</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pelanggan</th>
                                    <th class="text-end">Pesan</th>
                                    <th class="text-end">Chat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCustomers as $customer)
                                    <tr>
                                        <td>
                                            <strong>{{ $customer['name'] }}</strong><br>
                                            <small class="text-muted">{{ $customer['phone'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            {{ $customer['message_count'] }}
                                        </td>
                                        <td class="text-end">
                                            <small class="badge bg-info">
                                                {{ $customer['chat_count'] }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.chat.show', $customer['id']) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                👁️
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Types Distribution -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">📊 Distribusi Tipe Pesan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 bg-info-light rounded">
                                <small class="text-muted d-block">Teks</small>
                                <h5 class="mb-0 text-info">{{ $messageTypeDistribution['text'] ?? 0 }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-success-light rounded">
                                <small class="text-muted d-block">Gambar</small>
                                <h5 class="mb-0 text-success">{{ $messageTypeDistribution['image'] ?? 0 }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-primary-light rounded">
                                <small class="text-muted d-block">Video</small>
                                <h5 class="mb-0 text-primary">{{ $messageTypeDistribution['video'] ?? 0 }}</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-warning-light rounded">
                                <small class="text-muted d-block">File</small>
                                <h5 class="mb-0 text-warning">{{ $messageTypeDistribution['document'] ?? 0 }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Detail -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">📋 Detail Pesan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Tipe</th>
                            <th>Arah</th>
                            <th>Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td>
                                    <small>{{ $message->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $message->customer->name ?? 'Unknown' }}</strong><br>
                                    <small class="text-muted">{{ $message->customer->phone ?? '-' }}</small>
                                </td>
                                <td>
                                    @php
                                        $typeIcons = [
                                            'text' => '📝',
                                            'image' => '🖼️',
                                            'video' => '🎥',
                                            'audio' => '🎵',
                                            'document' => '📄',
                                            'sticker' => '🎨',
                                        ];
                                    @endphp
                                    <span class="badge bg-secondary">
                                        {{ $typeIcons[$message->type] ?? '📬' }} {{ $message->type }}
                                    </span>
                                </td>
                                <td>
                                    @if($message->is_incoming)
                                        <span class="badge bg-info">↓ Masuk</span>
                                    @else
                                        <span class="badge bg-success">↑ Keluar</span>
                                    @endif
                                </td>
                                <td>
                                    @if($message->type === 'text')
                                        <small>{{ Str::limit($message->body, 50) }}</small>
                                    @else
                                        <small class="text-muted">
                                            [{{ Str::upper($message->type) }}]
                                            {{ $message->caption ? Str::limit($message->caption, 40) : '' }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.chat.show', $message->customer) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        👁️
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data pesan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($messages && $messages->hasPages())
                <div class="p-3 border-top">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>
</div>
@endsection
