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
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Middleware\CheckRole;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    // Public pages
    Route::get('/homepage', [PublicController::class, 'homepage']);
    Route::get('/categories', [PublicController::class, 'categories']);
    Route::get('/categories/slug/{slug}', [PublicController::class, 'categoryBySlug']);
    Route::get('/providers/slug/{slug}', [PublicController::class, 'providerProfile']);

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
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::post('/payments/webhook/{operator}', [PaymentController::class, 'webhook']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

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

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);

        Route::post('/subscriptions/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);

        Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('/payments/{id}/status', [PaymentController::class, 'status']);

        Route::get('/providers/{id}/stats', [StatsController::class, 'index']);
        Route::get('/my/stats', [StatsController::class, 'myStats']);

        Route::middleware(CheckRole::class . ':admin')->prefix('admin')->group(function () {
            Route::get('/users', [AdminController::class, 'users']);
            Route::patch('/users', [AdminController::class, 'updateUser']);

            Route::get('/categories', [AdminController::class, 'categories']);
            Route::post('/categories', [AdminController::class, 'storeCategory']);
            Route::patch('/categories/{id}', [AdminController::class, 'updateCategory']);
            Route::delete('/categories/{id}', [AdminController::class, 'destroyCategory']);

            Route::get('/subscriptions', [AdminController::class, 'subscriptions']);
            Route::patch('/subscriptions/{id}', [AdminController::class, 'updateSubscription']);

            Route::get('/reports', [AdminController::class, 'reports']);
            Route::patch('/reports/{id}', [AdminController::class, 'resolveReport']);

            Route::get('/stats', [AdminController::class, 'stats']);
        });
    });
});
