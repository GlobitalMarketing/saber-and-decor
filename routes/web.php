<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('wpconnector/validate-license', [LicenseController::class, 'validateLicense']);
Route::post('wpconnector/sync-api-keys', [LicenseController::class, 'syncApiKeys']);