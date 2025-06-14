<?php
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ReportController;

Route::prefix("clients")->group(function () {
    Route::get("search/{query}", [ClientController::class, "search"]);
    Route::get("filter", [ClientController::class, "filter"]);
    Route::get("{client}/debt-report", [ReportController::class, "clientDebtReport"]);
    Route::get("{client}/total-invoiced", [ClientController::class, "getTotalInvoiced"]);
    Route::get("{client}/earnings-delivery", [ClientController::class, "getEarningsDelivery"]);
    Route::get("{client}/pending-earnings", [ClientController::class, "getPendingEarnings"]);
    Route::get("{client}/pending-earnings/count", [ClientController::class, "getPendingEarningsCount"]);
    Route::get("{client}/earnings-delivery-current-month", [ClientController::class, "getEarningsDeliveryOfCurrentMonth"]);
});
Route::apiResource("clients", ClientController::class);