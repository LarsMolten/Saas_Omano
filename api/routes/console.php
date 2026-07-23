<?php

use App\Console\Commands\ExpireSubscriptions;
use App\Jobs\AggregateProviderStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireSubscriptions::class)->everyMinute();
Schedule::job(AggregateProviderStats::class)->dailyAt('02:00');
