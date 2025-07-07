<?php

use App\Employee\Controllers\EmployeeController;

Route::prefix("employees")->group(function () {
    Route::get("filter", [EmployeeController::class, "filter"]);
    Route::put("{id}/password", [EmployeeController::class, "updatePassword"])->name('employees.updatePassword');
});
Route::apiResource("employees", EmployeeController::class);
