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
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\HomeController;

Route::post("register", [AuthController::class, "register"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::post("changePassword", [AuthController::class, "changePassword"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::put("user/update", [AuthController::class, "update"]);
    Route::get("user", fn(Request $request) => $request->user());

    // Dashboard statistics
    Route::get("dashboard", [HomeController::class, "getDashboardStats"]);

    Route::prefix("clients")->group(function () {
        Route::get("search/{query}", [ClientController::class, "search"]);
        Route::get("filter", [ClientController::class, "filter"]);
        Route::get("{client}/debt-report", [ReportController::class, "clientDebtReport"]);
    });
    Route::apiResource("clients", ClientController::class);

    Route::apiResource("couriers", CourierController::class);
    Route::apiResource("boxes", BoxController::class);
    Route::prefix("deliveries")->group(function () {
        Route::get("filter", [DeliveryController::class, "filter"]);
        Route::get("clients/{clientId}", [DeliveryController::class, "latestByClient"]);
        Route::get("couriers/{courierId}", [DeliveryController::class, "latestByCourier"]);
        Route::put("{delivery}/status", [DeliveryController::class, "updateStatus"]);
        Route::post("{delivery}/client-payments", [DeliveryController::class, "storeClientPayment"]);
        Route::get("status/received", [DeliveryController::class, "getReceived"]);
        Route::get("status/cancelled", [DeliveryController::class, "getCancelled"]);
        Route::get("status/pending", [DeliveryController::class, "getPending"]);
        Route::get("status/in-transit", [DeliveryController::class, "getInTransit"]);
        Route::get("payment/pending", [DeliveryController::class, "getPaymentPending"]);
        Route::get("payment/partially-paid", [DeliveryController::class, "getPartiallyPaid"]);
        Route::get("payment/paid", [DeliveryController::class, "getPaid"]);
        Route::get("with-debt", [DeliveryController::class, "getWithDebt"]);
        Route::get("with-debt/client/{clientId}", [DeliveryController::class, "getWithDebtByClient"]);
        Route::get("{delivery}/ticket", [ReportController::class, "deliveryTicket"]);
    });
    Route::apiResource("deliveries", DeliveryController::class);
    Route::apiResource("employees", EmployeeController::class);
    Route::apiResource("services", ServiceController::class);
    Route::apiResource("company-bills", CompanyBillController::class);

    Route::prefix("couriers")->group(function () {
        Route::get("search/{query}", [CourierController::class, "search"]);
        Route::get("{courier}/deliveries-report", [ReportController::class, "courierDeliveriesReport"]);
    });

    Route::prefix("employees")->group(function () {
        Route::get("search/{query}", [EmployeeController::class, "search"]);
    });

    Route::prefix("services")->group(function () {
        Route::get("search/{query}", [ServiceController::class, "search"]);
        Route::get("deliveries/{deliveryId}", [ServiceController::class, "getByDelivery"]);
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
