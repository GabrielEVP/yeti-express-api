<?php

use App\Delivery\Controllers\DeliveryController;
use App\Delivery\Controllers\DeliveryReportController;

Route::prefix("deliveries")->group(function () {
    Route::get("filter", [DeliveryController::class, "filter"]);
    Route::put("{delivery}/status", [DeliveryController::class, "updateStatus"]);
    Route::put("{delivery}/cancel", [DeliveryController::class, "cancelDelivery"]);
    Route::get("{delivery}/ticket", [DeliveryReportController::class, "getTicketReportDelivery"]);
});
Route::apiResource("deliveries", DeliveryController::class);
