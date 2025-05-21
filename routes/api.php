<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ProfileImageController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\BoxController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\EmployerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile_images/{filename}', [ProfileImageController::class, 'show']);
    Route::post('changePassword', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('/user/update', [AuthController::class, 'update']);

    Route::resource('clients', ClientController::class);
    Route::resource('couriers', CourierController::class);
    Route::resource('boxes', BoxController::class);
    Route::resource('deliveries', DeliveryController::class);
    Route::resource('employees', EmployerController::class);

    Route::get('/clients/search/{query}', [ClientController::class, 'search']);
});
