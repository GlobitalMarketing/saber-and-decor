<?php

use App\Http\Controllers\API\Touch365ApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('wpconnector/validate-license', [LicenseController::class, 'validateLicense']);
Route::post('wpconnector/sync-api-keys', [LicenseController::class, 'syncApiKeys']);
// Route::get('get-departments', [Touch365ApiController::class, 'getDepartment']);