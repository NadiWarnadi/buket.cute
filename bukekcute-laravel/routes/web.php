<?php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;
use routes\api;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

// ========== PUBLIC ROUTES (No Authentication Required) ==========

// Redirect home to public site
Route::get('/', [PublicController::class, 'home'])->name('public.home');

// Public pages
Route::get('/katalog', [PublicController::class, 'catalog'])->name('public.catalog');
Route::get('/produk/{slug}', [PublicController::class, 'detail'])->name('public.detail');
Route::get('/tentang', [PublicController::class, 'about'])->name('public.about');
Route::get('/kontak', [PublicController::class, 'contact'])->name('public.contact');
Route::get('/faq', [PublicController::class, 'faq'])->name('public.faq');
Route::get('/custom-request', [PublicController::class, 'customRequest'])->name('public.customRequest');
Route::post('/custom-request', [PublicController::class, 'submitCustomRequest'])->name('public.submitCustomRequest');

// API endpoints for public frontend
Route::post('/order-to-whatsapp', [PublicController::class, 'orderToWhatsApp'])->name('public.orderToWhatsApp');

// Public media files
Route::get('/media/{filename}', function ($filename) {
    $path = public_path('media/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('media.show');

// Dashboard (hanya untuk authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Categories
        Route::resource('categories', CategoryController::class);

        // Products
        Route::resource('products', ProductController::class);
        Route::patch('products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.updateStock');

        // Ingredients
        Route::resource('ingredients', IngredientController::class);
        Route::patch('ingredients/{ingredient}/stock', [IngredientController::class, 'updateStock'])->name('ingredients.updateStock');

        // Purchases
        Route::resource('purchases', PurchaseController::class);

        // Customers
        Route::resource('customers', CustomerController::class);

        // Orders
        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

        // Chat
        Route::resource('chat', ChatController::class)->only(['index', 'show']);
        Route::post('chat/{customer}/send', [ChatController::class, 'sendReply'])->name('chat.send');
        Route::get('chat/stats', [ChatController::class, 'getStats'])->name('chat.stats');

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('stock', [ReportController::class, 'stock'])->name('reports.stock');
            Route::get('chat', [ReportController::class, 'chat'])->name('reports.chat');
            Route::get('export-sales', [ReportController::class, 'exportSales'])->name('reports.export-sales');
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('settings.index');
            Route::put('/', [SettingController::class, 'update'])->name('settings.update');
            Route::get('whatsapp-status', [SettingController::class, 'checkWhatsAppStatus'])->name('settings.whatsapp-status');
            Route::post('rescan-qr', [SettingController::class, 'rescanQR'])->name('settings.rescan-qr');
        });
    });
});

// Auth routes (login, register, password reset, etc)
require __DIR__.'/auth.php';
