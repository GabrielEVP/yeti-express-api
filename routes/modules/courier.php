<?php
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\ReportController;

Route::prefix("couriers")->group(function () {
    Route::get("search/{query}", [CourierController::class, "search"]);
    Route::get("{courier}/deliveries-report", [ReportController::class, "courierDeliveriesReport"]);
});
Route::apiResource("couriers", CourierController::class);
