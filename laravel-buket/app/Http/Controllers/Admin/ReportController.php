<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\Ingredient;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Sales Report - Laporan Penjualan
     */
    public function sales(Request $request)
    {
        $startDate = $request->start_date ? Carbon::createFromFormat('Y-m-d', $request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::createFromFormat('Y-m-d', $request->end_date) : Carbon::now()->endOfDay();
          
        $productSales = collect([]);
       // ini untuk keranggka ketika data kosong
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
    ->selectRaw('status, count(*) as count, sum(total_price) as total') // Gunakan selectRaw
    ->get();

        // Daily sales data untuk chart
    $dailySales = Order::whereBetween('created_at', [$startDate, $endDate])
    ->groupByRaw('DATE(created_at)') // Gunakan groupByRaw
    ->selectRaw('DATE(created_at) as date, count(*) as count, sum(total_price) as total') // Gunakan selectRaw
    ->orderBy('date')
    ->get();

        // Top customers
      $topCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
    ->with('customer')
    ->groupBy('customer_id')
    ->selectRaw('customer_id, count(*) as count, sum(total_price) as total') // Gunakan selectRaw
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
        $ingredients = Ingredient::query();

        if ($request->status === 'low') {
            $ingredients->whereRaw('stock <= min_stock AND min_stock > 0');
        } elseif ($request->status === 'empty') {
            $ingredients->where('stock', 0);
        }

        if ($request->search) {
            $ingredients->where('name', 'like', "%{$request->search}%");
        }

        $ingredients = $ingredients->orderBy('stock')->paginate(20);

        // Summary data
        $totalIngredients = Ingredient::count();
        $lowStockCount = Ingredient::whereRaw('stock <= min_stock AND min_stock > 0')->count();
        $emptyStockCount = Ingredient::where('stock', 0)->count();

        // Stock movements untuk month ini
        $monthlyMovements = \App\Models\StockMovement::whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])
            ->groupBy('ingredient_id')
            ->select('ingredient_id', DB::raw('count(*) as count'))
            ->get();

        return view('admin.reports.stock', compact(
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
            ->map(fn($item) => (object)[
                'status' => $item->chat_status,
                'count' => $item->count
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
