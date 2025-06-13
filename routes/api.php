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
use App\Http\Controllers\Api\DebtController;
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

    Route::get("dashboard", [HomeController::class, "getDashboardStats"]);

    Route::prefix("clients")->group(function () {
        Route::get("search/{query}", [ClientController::class, "search"]);
        Route::get("filter", [ClientController::class, "filter"]);
        Route::get("{client}/debt-report", [ReportController::class, "clientDebtReport"]);
        Route::get("{client}/total-invoiced", [ClientController::class, "getTotalInvoiced"]);
        Route::get("{client}/earnings-delivery", [ClientController::class, "getEarningsDelivery"]);
        Route::get("{client}/pending-earnings", [ClientController::class, "getPendingEarnings"]);
        Route::get("{client}/pending-earnings/count", [ClientController::class, "getPendingEarningsCount"]);
        Route::get("{client}/earnings-delivery-current-month", [ClientController::class, "getEarningsDeliveryOfCurrentMonth"]);
    });
    Route::apiResource("clients", ClientController::class);

    Route::apiResource("couriers", CourierController::class);
    Route::apiResource("boxes", BoxController::class);

    Route::prefix('debts')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [DebtController::class, 'index']);
        Route::get('/{debt}', [DebtController::class, 'show']);
        Route::delete('/{debt}', [DebtController::class, 'destroy']);

        Route::get('/clients/with-debts', [DebtController::class, 'clientsWithDebt']);
        Route::get('/clients/debt-count', [DebtController::class, 'debtCountPerClient']);
    });

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

Route::prefix('employee')->group(function () {

    Route::middleware(['auth:employee', 'employee.permissions'])->group(function () {

        // Rutas permitidas para empleados
        Route::prefix('deliveries')->group(function () {
            Route::get('/', [DeliveryController::class, 'index']);
            Route::get('/{delivery}', [DeliveryController::class, 'show']);
            Route::get('/status/pending', [DeliveryController::class, 'getPending']);
            Route::get('/status/in-transit', [DeliveryController::class, 'getInTransit']);
            Route::put('/{delivery}/status', [DeliveryController::class, 'updateStatus']);
            Route::get('/status/received', [DeliveryController::class, 'getReceived']);
            Route::get('/status/cancelled', [DeliveryController::class, 'getCancelled']);
            Route::get('/payment/pending', [DeliveryController::class, 'getPaymentPending']);
            Route::get('/payment/partially-paid', [DeliveryController::class, 'getPartiallyPaid']);
            Route::get('/payment/paid', [DeliveryController::class, 'getPaid']);
            Route::get('/with-debt', [DeliveryController::class, 'getWithDebt']);
            Route::get('/with-debt/client/{clientId}', [DeliveryController::class, 'getWithDebtByClient']);
            Route::get('/{delivery}/ticket', [ReportController::class, 'deliveryTicket']);
        });

        Route::prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index']);
            Route::get('/{client}', [ClientController::class, 'show']);
            Route::get('/search/{query}', [ClientController::class, 'search']);
            Route::get('/filter', [ClientController::class, 'filter']);
            Route::get('/{client}/debt-report', [ReportController::class, 'clientDebtReport']);

            // Nuevas rutas para empleados también (si necesitan acceso)
            Route::get('/{client}/total-invoiced', [ClientController::class, 'getTotalInvoiced']);
            Route::get('/{client}/earnings-delivery', [ClientController::class, 'getEarningsDelivery']);
            Route::get('/{client}/pending-earnings', [ClientController::class, 'getPendingEarnings']);
            Route::get('/{client}/earnings-delivery-current-month', [ClientController::class, 'getEarningsDeliveryOfCurrentMonth']);
        });

        Route::prefix('couriers')->group(function () {
            Route::get('/', [CourierController::class, 'index']);
            Route::get('/{courier}', [CourierController::class, 'show']);
            Route::get('/search/{query}', [CourierController::class, 'search']);
            Route::get('/{courier}/deliveries-report', [ReportController::class, 'courierDeliveriesReport']);
        });

        Route::prefix('services')->group(function () {
            Route::get('/', [ServiceController::class, 'index']);
            Route::get('/{service}', [ServiceController::class, 'show']);
            Route::get('/search/{query}', [ServiceController::class, 'search']);
            Route::get('/deliveries/{deliveryId}', [ServiceController::class, 'getByDelivery']);
        });

        Route::prefix('company-bills')->group(function () {
            Route::get('/', [CompanyBillController::class, 'index']);
            Route::get('/{companyBill}', [CompanyBillController::class, 'show']);
            Route::get('/search/{query}', [CompanyBillController::class, 'search']);
        });

        Route::post("logout", [AuthController::class, "logout"]);

    });
});