<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\BoxController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('changePassword', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('user/update', [AuthController::class, 'update']);

    Route::apiResource('clients', ClientController::class);
    Route::apiResource('couriers', CourierController::class);
    Route::apiResource('boxes', BoxController::class);
    Route::apiResource('deliveries', DeliveryController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('services', ServiceController::class);

    Route::prefix('clients')->group(function () {
        Route::get('search/{query}', [ClientController::class, 'search']);
    });

    Route::prefix('employees')->group(function () {
        Route::get('search/{query}', [EmployeeController::class, 'search']);
    });

    Route::prefix('services')->group(function () {
        Route::get('search/{query}', [ServiceController::class, 'search']);
        Route::get('deliveries/{deliveryId}', [ServiceController::class, 'getByDelivery']);
    });

    Route::prefix('deliveries')->group(function () {
        Route::get('clients/{clientId}', [DeliveryController::class, 'latestByClient']);
        Route::get('couriers/{courierId}', [DeliveryController::class, 'latestByCourier']);
        Route::put('{delivery}/status', [DeliveryController::class, 'updateStatus']);
        Route::post('{delivery}/client-payments', [DeliveryController::class, 'storeClientPayment']);
        Route::post('{delivery}/courier-payments', [DeliveryController::class, 'storeCourierPayment']);
    });

    Route::get('user', fn(Request $request) => $request->user());
});
