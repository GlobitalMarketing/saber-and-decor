<?php

use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\Touch365ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);       // GET /api/departments
    Route::post('/', [DepartmentController::class, 'store']);      // POST /api/departments
    // Route::get('{id}', [DepartmentController::class, 'show']);     // GET /api/departments/{id}
    Route::put('{id}', [DepartmentController::class, 'update']);   // PUT /api/departments/{id}
    Route::delete('{id}', [DepartmentController::class, 'destroy']); // DELETE /api/departments/{id}
});
// Route::get('get-departments', [Touch365ApiController::class, 'getDepartment']);
// Route::post('post-department', [Touch365ApiController::class, 'postDepartment']);///upload departments to touch365
