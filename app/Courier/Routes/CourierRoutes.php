<?php

use App\Courier\Controllers\CourierController;
use App\Courier\Controllers\CourierReportController;

Route::prefix("couriers")->group(function () {
    Route::get("filter", [CourierController::class, "filter"]);
    Route::get("deliveries-report", [CourierReportController::class, "allCouriersDeliveriesReport"]);
    Route::get("{id}/deliveries-report", [CourierReportController::class, "courierDeliveriesReport"]);
});
Route::apiResource("couriers", CourierController::class);
