<?php
use App\Http\Controllers\Api\DebtPaymentController;

Route::prefix("debt-payments")->group(function () {
    Route::get("/", [DebtPaymentController::class, "index"]);
    Route::get("/{debtPayment}", [DebtPaymentController::class, "show"]);
    Route::post("full", [DebtPaymentController::class, "storeFullPayment"]);
    Route::post("partial", [DebtPaymentController::class, "storePartialPayment"]);
});