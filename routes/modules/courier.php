<?php

use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\CourierReportController;

Route::prefix("couriers")->group(function () {
    Route::get("search/{query}", [CourierController::class, "search"]);
    Route::get("{courier}/deliveries-report", [CourierReportController::class, "courierDeliveriesReport"]);
    Route::get("deliveries-report", [CourierReportController::class, "allCouriersDeliveriesReport"]);
});
Route::apiResource("couriers", CourierController::class);
