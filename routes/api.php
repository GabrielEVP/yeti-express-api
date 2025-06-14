<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post("register", [AuthController::class, "register"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::post("changePassword", [AuthController::class, "changePassword"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::put("user/update", [AuthController::class, "update"]);
    Route::get("user", fn(Request $request) => $request->user());

    require __DIR__ . '/modules/dashboard.php';
    require __DIR__ . '/modules/client.php';
    require __DIR__ . '/modules/delivery.php';
    require __DIR__ . '/modules/courier.php';
    require __DIR__ . '/modules/employee.php';
    require __DIR__ . '/modules/service.php';
    require __DIR__ . '/modules/company-bills.php';
    require __DIR__ . '/modules/debt.php';
    require __DIR__ . '/modules/debtPayment.php';
    require __DIR__ . '/modules/report.php';
});
