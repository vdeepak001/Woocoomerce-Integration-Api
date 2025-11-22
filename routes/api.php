<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\WooCommerceProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('woocommerce')->group(function () {
    // Product routes
    Route::get('products', [WooCommerceProductController::class, 'index']);
    Route::get('products/{id}', [WooCommerceProductController::class, 'show']);
    Route::post('products', [WooCommerceProductController::class, 'store']);
    Route::put('products/{id}', [WooCommerceProductController::class, 'update']);
    Route::delete('products/{id}', [WooCommerceProductController::class, 'destroy']);
        
    // Category routes
    Route::get('categories', [WooCommerceProductController::class, 'categories']);
    
    // Batch operations
    Route::post('products/batch', [WooCommerceProductController::class, 'batch']);
    
    // Sync all products from WooCommerce
    Route::post('sync', [WooCommerceProductController::class, 'sync']);
});
