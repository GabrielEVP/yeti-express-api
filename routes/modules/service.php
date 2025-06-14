<?php
use App\Http\Controllers\Api\ServiceController;

Route::prefix("services")->group(function () {
    Route::get("search/{query}", [ServiceController::class, "search"]);
    Route::get("deliveries/{deliveryId}", [ServiceController::class, "getByDelivery"]);
});
Route::apiResource("services", ServiceController::class);