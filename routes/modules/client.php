<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DebtReportController;

Route::prefix("clients")->group(function () {
    Route::get("search/{query}", [ClientController::class, "search"]);
    Route::get("filter", [ClientController::class, "filter"]);
    Route::get("with-debt", [ClientController::class, "clientsWithDebt"]);
    Route::get("{client}/total-invoiced", [ClientController::class, "getTotalInvoiced"]);
    Route::get("{client}/earnings-delivery", [ClientController::class, "getEarningsDelivery"]);
    Route::get("{client}/pending-earnings", [ClientController::class, "getPendingEarnings"]);
    Route::get("{client}/pending-earnings/count", [ClientController::class, "getPendingEarningsCount"]);
    Route::get("{client}/earnings-delivery-current-month", [ClientController::class, "getEarningsDeliveryOfCurrentMonth"]);
    Route::post("{client}/addresses", [ClientController::class, "createAddress"]);
    Route::get("{client}/debts-report", [DebtReportController::class, "clientDebtReport"]);
    Route::get("debts-report", [DebtReportController::class, "allClientsDebtReport"]);
    Route::get("reports/unpaid-debts", [DebtReportController::class, "unpaidDebtsReport"]);
});
Route::apiResource("clients", ClientController::class);
