<?php

use App\Cash\Controllers\CashController;
use App\Cash\Controllers\CashReportController;

Route::get("dashboard", [CashController::class, "getDashboardStats"]);
Route::get("dashboard/report", [CashReportController::class, "cashRegisterReport"]);
Route::get("dashboard/simplified-report", [CashReportController::class, "simplifiedCashRegisterReport"]);
