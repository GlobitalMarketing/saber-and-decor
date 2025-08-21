<?php

use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\Touch365ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LicenseController;
use App\Http\Controllers\API\WooOrderWebhookController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('wpconnector/validate-license', [LicenseController::class, 'validateLicense']);
Route::post('wpconnector/sync-api-keys', [LicenseController::class, 'syncApiKeys']);
// Route::get('get-departments', [Touch365ApiController::class, 'getDepartment']);
Route::post('/webhooks/order-sync/{licenseKey}', [WooOrderWebhookController::class,'resolveInstallation']);

// Local Department Management
Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);        // GET /api/departments
    Route::post('/', [DepartmentController::class, 'store']);       // POST /api/departments
    Route::put('{id}', [DepartmentController::class, 'update']);    // PUT /api/departments/{id}
    Route::delete('{id}', [DepartmentController::class, 'destroy']); // DELETE /api/departments/{id}
});

// Local Product Management
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);           // GET /api/products
    Route::get('/{stockcode}', [ProductController::class, 'show']); // GET /api/products/{stockcode}
    Route::post('/', [ProductController::class, 'store']);          // POST /api/products
    Route::post('/update-stock', [ProductController::class, 'updateStock']); // POST /api/products/update-stock
});

// Touch365 API Integration Endpoints
Route::prefix('touch365')->group(function () {
    // Department Endpoints
    Route::get('/departments', [Touch365ApiController::class, 'getDepartment']);      // GET /api/touch365/departments
    Route::post('/departments', [Touch365ApiController::class, 'postDepartment']);    // POST /api/touch365/departments

    // Product Endpoints
    Route::get('/products', [Touch365ApiController::class, 'getProduct']);            // GET /api/touch365/products
    Route::post('/products', [Touch365ApiController::class, 'postProduct']);          // POST /api/touch365/products
    Route::get('/products/quantity', [Touch365ApiController::class, 'getProductQuantity']); // GET /api/touch365/products/quantity

    // Manufacturer Endpoints
    Route::get('/manufacturers', [Touch365ApiController::class, 'getManufacturer']);  // GET /api/touch365/manufacturers
    Route::post('/manufacturers', [Touch365ApiController::class, 'postManufacturer']); // POST /api/touch365/manufacturers

    // Image Endpoints
    Route::get('/images', [Touch365ApiController::class, 'getImage']);                // GET /api/touch365/images
    Route::post('/images', [Touch365ApiController::class, 'postImage']);              // POST /api/touch365/images
    Route::delete('/images', [Touch365ApiController::class, 'deleteImage']);          // DELETE /api/touch365/images

    // Order Endpoints
    Route::get('/orders', [Touch365ApiController::class, 'getOrder']);                // GET /api/touch365/orders
    Route::post('/orders', [Touch365ApiController::class, 'postOrder']);              // POST /api/touch365/orders
    Route::put('/orders', [Touch365ApiController::class, 'updateOrder']);             // PUT /api/touch365/orders

    // Option Endpoints
    Route::get('/options', [Touch365ApiController::class, 'getOption']);              // GET /api/touch365/options
    Route::post('/options', [Touch365ApiController::class, 'postOption']);            // POST /api/touch365/options

    // Product Option Endpoints
    Route::get('/product-options', [Touch365ApiController::class, 'getProductOption']); // GET /api/touch365/product-options
});
