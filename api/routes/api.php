<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Middleware\CheckRole;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);

    Route::get('/providers/{id}/services', [ServiceController::class, 'index']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/auth/send-email-verification', [AuthController::class, 'sendEmailVerification']);
        Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
        Route::post('/auth/send-phone-verification', [AuthController::class, 'sendPhoneVerification']);

        Route::middleware(CheckRole::class . ':prestataire')->group(function () {
            Route::post('/providers/{id}/services', [ServiceController::class, 'store']);
        });

        Route::patch('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        Route::middleware(CheckRole::class . ':admin')->prefix('admin')->group(function () {
            Route::get('/users', fn () => response()->json(['message' => 'Admin users list']));
        });
    });
});
