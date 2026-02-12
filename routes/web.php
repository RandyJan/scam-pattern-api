<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;

Route::post('/reports', [ReportController::class, 'store']);
Route::get('/patterns', [ReportController::class, 'patterns']);
