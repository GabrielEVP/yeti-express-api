<?php

use App\CompanyBill\Controllers\CompanyBillController;

Route::prefix("company-bills")->group(function () {
    Route::get("filter", [CompanyBillController::class, "filter"]);
});
Route::apiResource("company-bills", CompanyBillController::class);


