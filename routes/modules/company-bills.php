<?php
use App\Http\Controllers\Api\CompanyBillController;

Route::prefix("CompanyBill")->group(function () {
    Route::get("search/{query}", [CompanyBillController::class, "search"]);
});
Route::apiResource("CompanyBill", CompanyBillController::class);
