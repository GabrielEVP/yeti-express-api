<?php

namespace App\Auth\Routes;

use App\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post("register", [AuthController::class, "register"]);
Route::post("login", [AuthController::class, "login"]);
