<?php
use App\Http\Controllers\Api\EmployeeController;

Route::prefix("employees")->group(function () {
    Route::get("search/{query}", [EmployeeController::class, "search"]);
});
Route::apiResource("employees", EmployeeController::class);