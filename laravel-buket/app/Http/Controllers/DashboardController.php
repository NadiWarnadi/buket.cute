<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FuzzyRule;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     */
    public function index()
    {
        // Get summary statistics
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();

        // Count low stock products
        $lowStockProducts = Product::where('stock', '<=', 5)
            ->where('is_active', true)
            ->count();

        // Count active products
        $activeProducts = Product::where('is_active', true)->count();

        // Fuzzy Rules statistics
        $totalFuzzyRules = FuzzyRule::count();
        $activeFuzzyRules = FuzzyRule::where('is_active', true)->count();

        // Get recent orders
        $recentOrders = Order::latest()->limit(5)->get();

        return view('dashboard', compact(
            'totalOrders',
            'totalProducts',
            'totalCustomers',
            'totalCategories',
            'lowStockProducts',
            'activeProducts',
            'totalFuzzyRules',
            'activeFuzzyRules',
            'recentOrders'
        ));
    }
}
