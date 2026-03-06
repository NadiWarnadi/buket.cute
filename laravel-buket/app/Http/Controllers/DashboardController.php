<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $lowStockProducts = Product::where('stock', '<=', 5)->count();
        $activeProducts = Product::where('is_active', true)->count();

        return view('dashboard', compact(
            'totalProducts',
            'totalCategories',
            'lowStockProducts',
            'activeProducts'
        ));
    }
}