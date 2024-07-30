<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\AuthenticateMiddleware;
use Illuminate\Support\Facades\Route;

// Authentication routes

Route::prefix('auth')
    ->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });


// Customer routes

Route::prefix('customers')
    ->middleware(AuthenticateMiddleware::class)
    ->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::get('/{customer_id}', [CustomerController::class, 'show']);
        Route::get('/{customer_id}/services', [ServiceController::class, 'getCustomerServices']);
        Route::post('', [CustomerController::class, 'store']);
        Route::put('/{customer_id}', [CustomerController::class, 'update']);
        Route::delete('/{customer_id}', [CustomerController::class, 'destroy']);
    });

// Services routes

Route::prefix('services')
    ->middleware(AuthenticateMiddleware::class)
    ->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{service_id}', [ServiceController::class, 'update']);
        Route::delete('/{service_id}', [ServiceController::class, 'destroy']);
    });
