<?php

namespace App\Modules\Providers;

use App\Modules\Auth\Services\LogSmsService;
use App\Modules\Auth\Services\SmsServiceInterface;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsServiceInterface::class, function () {
            return match (config('services.sms.driver')) {
                'log' => new LogSmsService(),
                default => new LogSmsService(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
