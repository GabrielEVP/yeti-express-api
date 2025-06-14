<?php
use App\Http\Controllers\Api\CompanyBillController;

Route::prefix("company-bills")->group(function () {
    Route::get("search/{query}", [CompanyBillController::class, "search"]);
});
Route::apiResource("company-bills", CompanyBillController::class);