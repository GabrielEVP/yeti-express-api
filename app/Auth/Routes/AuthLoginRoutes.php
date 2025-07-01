<?php

namespace App\Auth\Routes;

use App\Auth\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::put("user/password", [AuthController::class, "changePassword"]);
Route::post("logout", [AuthController::class, "logout"]);
Route::put("user/update", [AuthController::class, "update"]);
Route::get("user", fn(Request $request) => $request->user());
