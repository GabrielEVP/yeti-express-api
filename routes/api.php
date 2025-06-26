<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("register", [AuthController::class, "register"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::put("user/password", [AuthController::class, "changePassword"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::put("user/update", [AuthController::class, "update"]);
    Route::get("user", fn(Request $request) => $request->user());

    require __DIR__ . '/modules/dashboard.php';
    require __DIR__ . '/modules/client.php';
    require __DIR__ . '/modules/delivery.php';
    require base_path('app/Courier/Routes/CourierRoutes.php');
    require __DIR__ . '/modules/employee.php';
    require __DIR__ . '/modules/service.php';
    require base_path('app/Service/Routes/ServiceRoutes.php');
    require base_path('app/CompanyBill/Routes/CompanyBillRoutes.php');
    require __DIR__ . '/modules/debt.php';
    require __DIR__ . '/modules/debtPayment.php';
    require __DIR__ . '/modules/report.php';
});
