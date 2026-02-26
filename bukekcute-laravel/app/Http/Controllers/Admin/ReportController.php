<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Message;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Sales Report
     */
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : now()->endOfDay();

        // Get orders in date range
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with('customer', 'items.product')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate totals
        $totalRevenue = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum(DB::raw('quantity * price'));

        // Sales by product
        $productSales = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->product->name,
                    'quantity' => $items->sum('quantity'),
                    'revenue' => $items->sum(DB::raw('quantity * price')),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return view('admin.reports.sales', compact(
            'orders',
            'totalRevenue',
            'productSales'
        ));
    }

    /**
     * Stock Report
     */
    public function stock(Request $request)
    {
        $type = $request->get('type', '');
        $status = $request->get('status', '');

        // Product stock
        $products = Product::orderBy('name')->get();
        $totalProducts = $products->count();
        $lowStockProducts = $products->where('stock', '<=', DB::raw('minimum_stock ?? 5'))->count();

        // Ingredient stock
        $ingredients = Ingredient::orderBy('name')->get();
        $totalIngredients = $ingredients->count();
        $lowStockIngredients = $ingredients->where('stock', '<=', DB::raw('minimum_stock ?? 10'))->count();

        // Stock movements history
        $movements = StockMovement::orderBy('created_at', 'desc')
            ->with('ingredient', 'product')
            ->paginate(50);

        return view('admin.reports.stock', compact(
            'products',
            'ingredients',
            'movements',
            'totalProducts',
            'lowStockProducts',
            'totalIngredients',
            'lowStockIngredients'
        ));
    }

    /**
     * Export sales report to Excel
     */
    public function exportSales(Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();

        // Return CSV for now (can upgrade to Excel later)
        $filename = "sales_report_" . now()->format('Y-m-d_H-i-s') . ".csv";
        $headers = array(
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        );

        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Order ID', 'Customer', 'Phone', 'Total Items', 'Total Price', 'Status', 'Date']);

        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->id,
                $order->customer->name,
                $order->customer->phone,
                $order->items->sum('quantity'),
                $order->items->sum(\DB::raw('quantity * price')),
                $order->status,
                $order->created_at->format('d-m-Y H:i'),
            ]);
        }

        fclose($handle);

        return response()->stream(
            function () use ($handle) {},
            200,
            $headers
        );
    }

    /**
     * Chat/Message report
     */
    public function chat(Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : now()->endOfDay();
        $type = $request->get('type', '');

        // Get messages in date range
        $messagesQuery = Message::whereBetween('created_at', [$startDate, $endDate])
            ->with('customer');

        if ($type === 'incoming') {
            $messagesQuery->where('is_incoming', true);
        } elseif ($type === 'outgoing') {
            $messagesQuery->where('is_incoming', false);
        }

        $messages = $messagesQuery->orderBy('created_at', 'desc')->paginate(50);

        // Statistics
        $totalMessages = Message::whereBetween('created_at', [$startDate, $endDate])->count();
        $incomingCount = Message::whereBetween('created_at', [$startDate, $endDate])->where('is_incoming', true)->count();
        $outgoingCount = Message::whereBetween('created_at', [$startDate, $endDate])->where('is_incoming', false)->count();
        $activeCustomers = Customer::whereHas('messages', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->count();

        // Top customers
        $topCustomers = Customer::withCount(['messages' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }])
            ->orderByDesc('messages_count')
            ->take(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'message_count' => $customer->messages_count,
                    'chat_count' => $customer->messages()->count(),
                ];
            });

        // Message type distribution
        $messageTypeDistribution = Message::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('`type`, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return view('admin.reports.chat', compact(
            'messages',
            'totalMessages',
            'incomingCount',
            'outgoingCount',
            'activeCustomers',
            'topCustomers',
            'messageTypeDistribution'
        ));
    }
}
