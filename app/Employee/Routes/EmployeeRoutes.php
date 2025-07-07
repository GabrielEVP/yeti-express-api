<?php

use App\Employee\Controllers\EmployeeController;
use App\Employee\Controllers\EmployeeEventReportController;

Route::prefix("employees")->group(function () {
    Route::get("filter", [EmployeeController::class, "filter"]);
    Route::put("{id}/password", [EmployeeController::class, "updatePassword"])->name('employees.updatePassword');
    Route::get("{id}/report-event", [EmployeeEventReportController::class, "getEvents"])->name('employees.report-event');
});
Route::apiResource("employees", EmployeeController::class);
