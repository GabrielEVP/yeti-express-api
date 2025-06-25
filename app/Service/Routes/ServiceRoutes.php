<?php

use App\Service\Controllers\ServiceController;

Route::apiResource("services", ServiceController::class);
Route::prefix("services")->group(function () {
    Route::get("search/{query}", [ServiceController::class, "search"]);
    Route::get("deliveries/{deliveryId}", [ServiceController::class, "getByDelivery"]);
});

