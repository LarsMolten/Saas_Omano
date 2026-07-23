<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\ReviewController;
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
    Route::get('/providers/{id}/portfolio', [PortfolioController::class, 'index']);
    Route::get('/providers/{id}/reviews', [ReviewController::class, 'providerReviews']);
    Route::get('/search', SearchController::class);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/auth/send-email-verification', [AuthController::class, 'sendEmailVerification']);
        Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
        Route::post('/auth/send-phone-verification', [AuthController::class, 'sendPhoneVerification']);

        Route::middleware(CheckRole::class . ':prestataire')->group(function () {
            Route::post('/providers/{id}/services', [ServiceController::class, 'store']);
            Route::post('/providers/{id}/portfolio', [PortfolioController::class, 'store']);
        });

        Route::patch('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        Route::patch('/portfolio/{id}', [PortfolioController::class, 'update']);
        Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{provider_id}', [FavoriteController::class, 'destroy']);

        Route::get('/quotes', [QuoteController::class, 'index']);
        Route::post('/quotes', [QuoteController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::patch('/quotes/{id}/respond', [QuoteController::class, 'respond']);

        Route::middleware(CheckRole::class . ':client')->group(function () {
            Route::post('/reviews', [ReviewController::class, 'store']);
        });

        Route::patch('/reviews/{id}', [ReviewController::class, 'update']);
        Route::post('/reviews/{id}/report', [ReviewController::class, 'report']);

        Route::middleware(CheckRole::class . ':admin')->prefix('admin')->group(function () {
            Route::get('/users', fn () => response()->json(['message' => 'Admin users list']));
        });
    });
});
