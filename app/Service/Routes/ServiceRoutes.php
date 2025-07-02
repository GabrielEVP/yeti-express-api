<?php

use App\Service\Controllers\ServiceController;

Route::get("services/filter", [ServiceController::class, "filter"]);
Route::apiResource("services", ServiceController::class);

