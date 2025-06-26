<?php

use App\Client\Controllers\ClientController;
use App\Http\Controllers\Api\DebtReportController;
use Illuminate\Support\Facades\Route;

Route::prefix("clients")->group(function () {
    Route::get("search/{query}", [ClientController::class, "search"]);
    Route::get("filter", [ClientController::class, "filter"]);


    Route::get("{client}/debts-report", [DebtReportController::class, "clientDebtReport"]);
    Route::get("debts-report", [DebtReportController::class, "allClientsDebtReport"]);
    Route::get("reports/unpaid-debts", [DebtReportController::class, "unpaidDebtsReport"]);
});
Route::apiResource("clients", ClientController::class);
