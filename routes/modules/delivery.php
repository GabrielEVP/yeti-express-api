<?php
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\ReportController;

Route::prefix("deliveries")->group(function () {
    Route::get("filter", [DeliveryController::class, "filter"]);
    Route::get("clients/{clientId}", [DeliveryController::class, "latestByClient"]);
    Route::get("couriers/{courierId}", [DeliveryController::class, "latestByCourier"]);
    Route::put("{delivery}/status", [DeliveryController::class, "updateStatus"]);
    Route::post("{delivery}/client-payments", [DeliveryController::class, "storeClientPayment"]);

    // Rutas por estado
    Route::get("status/received", [DeliveryController::class, "getReceived"]);
    Route::get("status/cancelled", [DeliveryController::class, "getCancelled"]);
    Route::get("status/pending", [DeliveryController::class, "getPending"]);
    Route::get("status/in-transit", [DeliveryController::class, "getInTransit"]);

    // Rutas por pago
    Route::get("payment/pending", [DeliveryController::class, "getPaymentPending"]);
    Route::get("payment/partially-paid", [DeliveryController::class, "getPartiallyPaid"]);
    Route::get("payment/paid", [DeliveryController::class, "getPaid"]);

    // Rutas de deuda
    Route::get("with-debt", [DeliveryController::class, "getWithDebt"]);
    Route::get("with-debt/client/{clientId}", [DeliveryController::class, "getWithDebtByClient"]);
    Route::get("{delivery}/ticket", [ReportController::class, "deliveryTicket"]);
});
Route::apiResource("deliveries", DeliveryController::class);