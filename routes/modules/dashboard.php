<?php

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\HomeReportController;

Route::get("dashboard", [HomeController::class, "getDashboardStats"]);
Route::get("dashboard/report", [HomeReportController::class, "cashRegisterReport"]);
Route::get("dashboard/simplified-report", [HomeReportController::class, "simplifiedCashRegisterReport"]);
