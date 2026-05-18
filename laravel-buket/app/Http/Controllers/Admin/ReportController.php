<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Message;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Sales Report - Laporan Penjualan
     */
    public function sales(Request $request)
{
    $startDate = $request->start_date
        ? Carbon::createFromFormat('Y-m-d', $request->start_date)
        : Carbon::now()->startOfMonth();
    $endDate = $request->end_date
        ? Carbon::createFromFormat('Y-m-d', $request->end_date)
        : Carbon::now()->endOfDay();

    // ✅ Produk Terlaris (dari order_items + products + orders)
    $productSales = DB::table('order_items')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->whereBetween('orders.created_at', [$startDate, $endDate])
        ->where('orders.status', '!=', 'cancelled') // abaikan pesanan dibatalkan
        ->groupBy('order_items.product_id', 'products.name')
        ->select(
            'products.name as name',
            DB::raw('SUM(order_items.quantity) as quantity'),
            DB::raw('SUM(order_items.subtotal) as revenue')
        )
        ->orderByDesc('revenue')
        ->get();

    // Data pesanan
    $orders = Order::with('customer')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->latest()
        ->paginate(10);

    // Statistik umum
    $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
    $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])->sum('total_price');
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

    // Order status breakdown
    $statusBreakdown = Order::whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('status')
        ->selectRaw('status, count(*) as count, sum(total_price) as total')
        ->get();

    // Daily sales data untuk chart
    $dailySales = Order::whereBetween('created_at', [$startDate, $endDate])
        ->groupByRaw('DATE(created_at)')
        ->selectRaw('DATE(created_at) as date, count(*) as count, sum(total_price) as total')
        ->orderBy('date')
        ->get();

    // Top customers
    $topCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
        ->with('customer')
        ->groupBy('customer_id')
        ->selectRaw('customer_id, count(*) as count, sum(total_price) as total')
        ->orderByDesc('total')
        ->limit(10)
        ->get();

    return view('admin.reports.sales', compact(
        'orders',
        'productSales',
        'totalOrders',
        'totalRevenue',
        'avgOrderValue',
        'statusBreakdown',
        'dailySales',
        'topCustomers',
        'startDate',
        'endDate'
    ));
}

    /**
     * Stock Report - Laporan Stok Bahan Baku
     */
  public function stock(Request $request)
{
    // stok produk (5 produk dengan stok terendah)
    $products = \App\Models\Product::orderBy('stock')->take(5)->get();

    // bahan bahan dengan filter
    $ingredientsQuery = Ingredient::query();

    if ($request->status === 'low') {
        $ingredientsQuery->whereRaw('stock <= min_stock AND min_stock > 0');
    } elseif ($request->status === 'empty') {
        $ingredientsQuery->where('stock', 0);
    }

    if ($request->search) {
        $search = $request->search;
        $ingredientsQuery->where('name', 'like', "%{$search}%");
    }

    $ingredients = $ingredientsQuery->orderBy('stock')->paginate(20);

    // Summary data
    $totalIngredients = Ingredient::count();
    $lowStockCount = Ingredient::whereRaw('stock <= min_stock AND min_stock > 0')->count();
    $emptyStockCount = Ingredient::where('stock', 0)->count();

    // Riwayat pergerakan stok bulan ini (detail, bukan agregat)
    $monthlyMovements = \App\Models\StockMovement::with('ingredient')
        ->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])
        ->latest()
        ->paginate(20);

    return view('admin.reports.stock', compact(
        'products',
        'ingredients',
        'totalIngredients',
        'lowStockCount',
        'emptyStockCount',
        'monthlyMovements'
    ));
}

    /**
     * Chat Report - Laporan Percakapan
     */
    public function chat(Request $request)
    {
        $startDate = $request->start_date ? Carbon::createFromFormat('Y-m-d', $request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::createFromFormat('Y-m-d', $request->end_date) : Carbon::now()->endOfDay();

        // Statistik umum - count distinct customers with messages as conversations
        $totalConversations = Message::whereBetween('created_at', [$startDate, $endDate])
            ->distinct()
            ->count('customer_id');
        $totalMessages = Message::whereBetween('created_at', [$startDate, $endDate])->count();
        $avgMessagesPerConv = $totalConversations > 0 ? $totalMessages / $totalConversations : 0;

        // Status breakdown - count customers by latest message chat_status
        $statusBreakdown = Message::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('chat_status')
            ->select('chat_status', DB::raw('count(*) as count'))
            ->get()
            ->map(fn ($item) => (object) [
                'status' => $item->chat_status,
                'count' => $item->count,
            ]);

        // Active conversations - get customers with latest message chat_status = active
        $activeConversations = \App\Models\Customer::with(['messages' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('chat_status', 'active')
                ->orderByDesc('created_at')
                ->limit(1);
        }])
            ->whereHas('messages', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('chat_status', 'active');
            })
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        // Daily chat messages
        $dailyMessages = Message::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count'),
                DB::raw('sum(is_incoming) as incoming'),
                DB::raw('sum(case when is_incoming = false then 1 else 0 end) as outgoing')
            )
            ->orderBy('date')
            ->get();

        return view('admin.reports.chat', compact(
            'totalConversations',
            'totalMessages',
            'avgMessagesPerConv',
            'statusBreakdown',
            'activeConversations',
            'dailyMessages',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Sales Report to Excel/PDF
     */
    public function exportSales(Request $request)
    {
        $startDate = $request->start_date ? Carbon::createFromFormat('Y-m-d', $request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::createFromFormat('Y-m-d', $request->end_date) : Carbon::now()->endOfDay();

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        // Untuk saat ini, return view untuk di print
        return view('admin.reports.export-sales', compact('orders', 'startDate', 'endDate'));
    }
}
