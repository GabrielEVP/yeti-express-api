<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\BoxController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CompanyBillController;
use App\Http\Controllers\Api\DebtPaymentController;

Route::post("register", [AuthController::class, "register"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::post("changePassword", [AuthController::class, "changePassword"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::put("user/update", [AuthController::class, "update"]);
    Route::get("user", fn(Request $request) => $request->user());

    Route::apiResource("clients", ClientController::class);
    Route::apiResource("couriers", CourierController::class);
    Route::apiResource("boxes", BoxController::class);
    Route::apiResource("deliveries", DeliveryController::class);
    Route::apiResource("employees", EmployeeController::class);
    Route::apiResource("services", ServiceController::class);
    Route::apiResource("company-bills", CompanyBillController::class);

    Route::prefix("clients")->group(function () {
        Route::get("search/{query}", [ClientController::class, "search"]);
    });

    Route::prefix("employees")->group(function () {
        Route::get("search/{query}", [EmployeeController::class, "search"]);
    });

    Route::prefix("services")->group(function () {
        Route::get("search/{query}", [ServiceController::class, "search"]);
        Route::get("deliveries/{deliveryId}", [ServiceController::class, "getByDelivery"]);
    });

    Route::prefix("deliveries")->group(function () {
        Route::get("clients/{clientId}", [DeliveryController::class, "latestByClient"]);
        Route::get("couriers/{courierId}", [DeliveryController::class, "latestByCourier"]);
        Route::put("{delivery}/status", [DeliveryController::class, "updateStatus"]);
        Route::post("{delivery}/client-payments", [DeliveryController::class, "storeClientPayment"]);
    });

    Route::prefix("company-bills")->group(function () {
        Route::get("search/{query}", [CompanyBillController::class, "search"]);
    });

    Route::prefix("debt-payments")->group(function () {
        Route::get("/", [DebtPaymentController::class, "index"]);
        Route::get("/{debtPayment}", [DebtPaymentController::class, "show"]);
        Route::post("full", [DebtPaymentController::class, "storeFullPayment"]);
        Route::post("partial", [DebtPaymentController::class, "storePartialPayment"]);
    });
});
