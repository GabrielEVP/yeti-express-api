<?php

use App\CompanyBill\Controllers\CompanyBillController;

Route::apiResource("company-bills", CompanyBillController::class);
Route::prefix("company-bills")->group(function () {
    Route::get("search/{query}", [CompanyBillController::class, "search"]);
});


