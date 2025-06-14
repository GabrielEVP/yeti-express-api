<?php
use App\Http\Controllers\Api\HomeController;

Route::get("dashboard", [HomeController::class, "getDashboardStats"]);