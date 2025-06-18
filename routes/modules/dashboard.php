<?php
use App\Http\Controllers\Api\HomeController;

Route::get("dashboard", [HomeController::class, "getDashboardStats"]);
Route::get("dashboard/report", [HomeController::class, "getCashRegisterReport"]);