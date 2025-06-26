<?php

use App\Courier\Controllers\CourierController;
use App\Courier\Controllers\CourierReportController;

Route::prefix("couriers")->group(function () {
    Route::get("search/{query}", [CourierController::class, "search"]);
    Route::get("deliveries-report", [CourierReportController::class, "allCouriersDeliveriesReport"]);
    Route::get("{id}/deliveries-report", [CourierReportController::class, "courierDeliveriesReport"]);
});
Route::apiResource("couriers", CourierController::class);
