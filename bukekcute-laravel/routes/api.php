<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\MediaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication for bot)
Route::prefix('messages')->group(function () {
    Route::post('store', [MessageController::class, 'store']);
    Route::get('unparsed', [MessageController::class, 'getUnparsed']);
    Route::patch('{message}/parsed', [MessageController::class, 'markParsed']);
});

// Outgoing messages - status updates from Node.js
Route::prefix('outgoing-messages')->group(function () {
    Route::patch('mark-sent', [MessageController::class, 'markSent']);
    Route::patch('mark-delivered', [MessageController::class, 'markDelivered']);
    Route::patch('mark-read', [MessageController::class, 'markRead']);
});

// Media routes (for authenticated users)
Route::middleware('auth:sanctum')->prefix('media')->group(function () {
    Route::post('upload', [MediaController::class, 'store']);
    Route::get('list', [MediaController::class, 'index']);
    Route::get('{media}', [MediaController::class, 'show']);
    Route::get('{media}/download', [MediaController::class, 'download']);
    Route::delete('{media}', [MediaController::class, 'destroy']);
    Route::post('{media}/featured', [MediaController::class, 'setFeatured']);
});

