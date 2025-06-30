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
    require base_path('app/Client/Routes/ClientRoutes.php');
    require base_path('app/Delivery/Routes/DeliveryRoutes.php');
    require base_path('app/Courier/Routes/CourierRoutes.php');
    require __DIR__ . '/modules/employee.php';
    require base_path('app/Service/Routes/ServiceRoutes.php');
    require base_path('app/CompanyBill/Routes/CompanyBillRoutes.php');
    require base_path('app/Debt/Routes/DebtRoutes.php.php');
    require base_path('app/Debt/Routes/DebtPaymentRoutes.php.php');
});
