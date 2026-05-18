@extends('layouts.admin')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3">📊 Laporan Penjualan</h1>
            <a href="{{ route('admin.reports.export-sales') }}" class="btn btn-success btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
</div>

{{-- Filter --}}
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
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-secondary w-100">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Statistik Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Pesanan</small>
                <h3 class="mb-0">{{ $totalOrders }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Pendapatan</small>
                <h3 class="mb-0 text-success">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Rata-rata Pesanan</small>
                <h3 class="mb-0">Rp{{ number_format($avgOrderValue, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Status</small>
                <h3 class="mb-0">{{ $statusBreakdown->count() }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Grafik Penjualan Harian --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">📈 Grafik Penjualan Harian</h6>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Breakdown Status & Top Customers --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Breakdown Status Pesanan</h6>
                <button class="btn btn-sm btn-outline-primary" id="toggleStatusView">
                    📊 Lihat Grafik
                </button>
            </div>
            <div class="card-body">
                {{-- Tabel --}}
                <div id="statusTableView">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statusBreakdown as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $item->status === 'completed' ? 'success' : ($item->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    <td>{{ $item->count }}</td>
                                    <td>Rp{{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Grafik --}}
                <div id="statusChartView" style="display: none;">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Top 10 Pelanggan</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Pesanan</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $item)
                            @if($item->customer)
                                <tr>
                                    <td>{{ $item->customer->name ?? 'Unknown' }}</td>
                                    <td>{{ $item->count }}</td>
                                    <td>Rp{{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Produk Terlaris & Status Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">🏆 Produk Terlaris</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productSales as $product)
                            <tr>
                                {{-- ⚠️ Perbaikan: akses sebagai objek, bukan array --}}
                                <td><strong>{{ $product->name }}</strong></td>
                                <td class="text-end">{{ $product->quantity }}</td>
                                <td class="text-end text-success">
                                    <strong>Rp {{ number_format($product->revenue, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">📌 Status Pesanan</h5>
            </div>
            <div class="card-body">
                <div class="row text-center g-2">
                    @php
                        $statuses = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];
                    @endphp
                    @foreach($statuses as $status)
                        @php
                            $count = $orders->where('status', $status)->count();
                            $colors = [
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                            ];
                        @endphp
                        <div class="col">
                            <div class="p-3 bg-{{ $colors[$status] ?? 'secondary' }}-light rounded">
                                <strong class="d-block text-{{ $colors[$status] ?? 'secondary' }}">{{ $count }}</strong>
                                <small class="text-muted text-capitalize">{{ $status }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Detail Pesanan --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">📋 Detail Pesanan</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><small>{{ $order->created_at->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <strong>{{ $order->customer->name ?? 'Guest' }}</strong><br>
                                <small class="text-muted">{{ $order->customer->phone ?? '' }}</small>
                            </td>
                            <td>
                                @foreach($order->items as $item)
                                    <small class="d-block">{{ $item->product->name ?? 'Produk' }}</small>
                                @endforeach
                            </td>
                            <td class="text-end"><strong>{{ $order->items->sum('quantity') }}</strong></td>
                            <td class="text-end text-success">
                                <strong>Rp {{ number_format($order->items->sum(fn($i) => $i->quantity * $i->price), 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                    $color = $statusColors[$order->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }} text-capitalize">{{ $order->status }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">👁️</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada data pesanan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">← Kembali</a>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Grafik Penjualan Harian
        const dailySales = @json($dailySales);
        const labels = dailySales.map(item => item.date);
        const counts = dailySales.map(item => item.count);
        const totals = dailySales.map(item => item.total);

        const ctx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Jumlah Pesanan',
                        data: counts,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Total Pendapatan (Rp)',
                        data: totals,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Jumlah Pesanan'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Total (Rp)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Toggle Breakdown Status: Tabel <-> Grafik Donat
        const statusBreakdown = @json($statusBreakdown);
        let statusChartInstance = null;

        document.getElementById('toggleStatusView').addEventListener('click', function () {
            const tableView = document.getElementById('statusTableView');
            const chartView = document.getElementById('statusChartView');
            const btn = this;

            if (chartView.style.display === 'none') {
                tableView.style.display = 'none';
                chartView.style.display = 'block';
                btn.innerHTML = '📋 Lihat Tabel';

                if (!statusChartInstance) {
                    const ctxStatus = document.getElementById('statusChart').getContext('2d');
                    const labelsStatus = statusBreakdown.map(item => item.status);
                    const dataCounts = statusBreakdown.map(item => item.count);

                    statusChartInstance = new Chart(ctxStatus, {
                        type: 'doughnut',
                        data: {
                            labels: labelsStatus,
                            datasets: [{
                                data: dataCounts,
                                backgroundColor: [
                                    '#ffc107', // pending
                                    '#0dcaf0', // confirmed
                                    '#6f42c1', // processing
                                    '#198754', // completed
                                    '#dc3545'  // cancelled
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            } else {
                tableView.style.display = 'block';
                chartView.style.display = 'none';
                btn.innerHTML = '📊 Lihat Grafik';
            }
        });
    });
</script>
@endpush