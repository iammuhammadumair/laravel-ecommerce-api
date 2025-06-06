<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ProductInventoryController;

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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Product Management API Routes
|--------------------------------------------------------------------------
|
| All product and product variant related endpoints
| Base URL: /api/
|
*/

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'Laravel Product Management API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
*/

Route::prefix('products')->name('products.')->group(function () {
    // Core CRUD Operations
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::patch('/{id}', [ProductController::class, 'update'])->name('patch')->where('id', '[0-9]+');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    
    // Bulk Operations
    Route::post('/bulk/delete', [ProductController::class, 'bulkDelete'])->name('bulk.delete');
    Route::patch('/bulk/status', [ProductController::class, 'bulkUpdateStatus'])->name('bulk.status');
    
    // Search & Filter
    Route::get('/search', [ProductController::class, 'search'])->name('search');
    Route::get('/categories', [ProductController::class, 'getCategories'])->name('categories');
    Route::get('/vendors', [ProductController::class, 'getVendors'])->name('vendors');
    Route::get('/tags', [ProductController::class, 'getTags'])->name('tags');
    
    // Product Statistics
    Route::get('/stats', [ProductController::class, 'getStats'])->name('stats');
    Route::get('/low-stock', [ProductController::class, 'getLowStock'])->name('low-stock');
    Route::get('/out-of-stock', [ProductController::class, 'getOutOfStock'])->name('out-of-stock');
    
    // Product Export/Import
    Route::get('/export', [ProductController::class, 'export'])->name('export');
    Route::post('/import', [ProductController::class, 'import'])->name('import');
});

// Product Variants Nested Routes
Route::prefix('products/{productId}/variants')->name('products.variants.')->where(['productId' => '[0-9]+'])->group(function () {
    Route::get('/', [ProductVariantController::class, 'getByProduct'])->name('index');
    Route::post('/', [ProductVariantController::class, 'store'])->name('store');
    Route::get('/{id}', [ProductVariantController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::put('/{id}', [ProductVariantController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::patch('/{id}', [ProductVariantController::class, 'update'])->name('patch')->where('id', '[0-9]+');
    Route::delete('/{id}', [ProductVariantController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    
    // Variant Positioning
    Route::patch('/positions', [ProductVariantController::class, 'updatePositions'])->name('positions.update');
    Route::patch('/{id}/position', [ProductVariantController::class, 'updatePosition'])->name('position.update')->where('id', '[0-9]+');
});

/*
|--------------------------------------------------------------------------
| Product Variant Routes (Standalone)
|--------------------------------------------------------------------------
*/

Route::prefix('variants')->name('variants.')->group(function () {
    // Core CRUD Operations
    Route::get('/', [ProductVariantController::class, 'index'])->name('index');
    Route::post('/', [ProductVariantController::class, 'store'])->name('store');
    Route::get('/{id}', [ProductVariantController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::put('/{id}', [ProductVariantController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::patch('/{id}', [ProductVariantController::class, 'update'])->name('patch')->where('id', '[0-9]+');
    Route::delete('/{id}', [ProductVariantController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    
    // Bulk Operations
    Route::post('/bulk/delete', [ProductVariantController::class, 'bulkDelete'])->name('bulk.delete');
    Route::patch('/bulk/positions', [ProductVariantController::class, 'bulkUpdatePositions'])->name('bulk.positions');
    
    // Search & Filter
    Route::get('/search', [ProductVariantController::class, 'search'])->name('search');
    Route::get('/by-options', [ProductVariantController::class, 'getByOptions'])->name('by-options');
    
    // Statistics
    Route::get('/stats', [ProductVariantController::class, 'getStats'])->name('stats');
    Route::get('/low-stock', [ProductVariantController::class, 'getLowStock'])->name('low-stock');
    Route::get('/out-of-stock', [ProductVariantController::class, 'getOutOfStock'])->name('out-of-stock');
});

/*
|--------------------------------------------------------------------------
| Inventory Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('inventory')->name('inventory.')->group(function () {
    // Product Inventory Management
    Route::patch('/products/{id}', [ProductInventoryController::class, 'updateProductInventory'])->name('products.update')->where('id', '[0-9]+');
    Route::patch('/products/bulk', [ProductInventoryController::class, 'bulkUpdateProductInventory'])->name('products.bulk');
    
    // Variant Inventory Management
    Route::patch('/variants/{id}', [ProductInventoryController::class, 'updateVariantInventory'])->name('variants.update')->where('id', '[0-9]+');
    Route::patch('/variants/bulk', [ProductInventoryController::class, 'bulkUpdateVariantInventory'])->name('variants.bulk');
});

// Legacy inventory routes for backward compatibility
Route::patch('/products/{id}/inventory', [ProductInventoryController::class, 'updateProductInventory'])->name('products.inventory.update')->where('id', '[0-9]+');
Route::patch('/variants/{id}/inventory', [ProductInventoryController::class, 'updateVariantInventory'])->name('variants.inventory.update')->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| API Utility Routes
|--------------------------------------------------------------------------
*/

Route::prefix('utils')->name('utils.')->group(function () {
    // SKU Generation
    Route::post('/generate-sku', function (Request $request) {
        $prefix = $request->input('prefix', 'PRD');
        $timestamp = now()->timestamp;
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return response()->json([
            'success' => true,
            'sku' => strtoupper($prefix . '-' . $timestamp . '-' . $random)
        ]);
    })->name('generate-sku');
    
    // Barcode Generation
    Route::post('/generate-barcode', function (Request $request) {
        $length = $request->input('length', 13);
        $barcode = '';
        
        for ($i = 0; $i < $length; $i++) {
            $barcode .= rand(0, 9);
        }
        
        return response()->json([
            'success' => true,
            'barcode' => $barcode
        ]);
    })->name('generate-barcode');
    
    // Weight Unit Conversion
    Route::post('/convert-weight', function (Request $request) {
        $weight = $request->input('weight');
        $from = $request->input('from');
        $to = $request->input('to');
        
        // Conversion rates to grams
        $rates = [
            'g' => 1,
            'kg' => 1000,
            'lb' => 453.592,
            'oz' => 28.3495
        ];
        
        if (!isset($rates[$from]) || !isset($rates[$to])) {
            return response()->json(['error' => 'Invalid weight unit'], 400);
        }
        
        $grams = $weight * $rates[$from];
        $converted = $grams / $rates[$to];
        
        return response()->json([
            'success' => true,
            'original' => ['weight' => $weight, 'unit' => $from],
            'converted' => ['weight' => round($converted, 4), 'unit' => $to]
        ]);
    })->name('convert-weight');
});

/*
|--------------------------------------------------------------------------
| API Documentation Route
|--------------------------------------------------------------------------
*/

Route::get('/docs', function () {
    return redirect('/api/documentation');
})->name('docs.redirect'); 