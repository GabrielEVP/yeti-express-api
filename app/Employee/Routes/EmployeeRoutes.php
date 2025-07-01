<?php

use App\Employee\Controller\EmployeeController;

Route::prefix("employees")->group(function () {
    Route::get("search/{query}", [EmployeeController::class, "search"]);
    Route::put("{employee}/password", [EmployeeController::class, "updatePassword"]);
});
Route::apiResource("employees", EmployeeController::class);
