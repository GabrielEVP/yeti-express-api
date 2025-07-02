<?php

use App\Employee\Controllers\EmployeeController;

Route::prefix("employees")->group(function () {
    Route::get("filter", [EmployeeController::class, "filter"]);
    Route::put("{employee}/password", [EmployeeController::class, "updatePassword"]);
});
Route::apiResource("employees", EmployeeController::class);
