<?php

namespace App\Jobs;

use App\Models\ProviderEvent;
use App\Models\ProviderStatsDaily;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateProviderStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'default';
    }

    /**
     * Aggregate provider_events into provider_stats_daily for the previous day.
     */
    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        $eventCounts = ProviderEvent::whereDate('created_at', $yesterday)
            ->select('provider_id', 'event_type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('provider_id', 'event_type')
            ->get()
            ->groupBy('provider_id');

        foreach ($eventCounts as $providerId => $events) {
            $counts = $events->pluck('count', 'event_type')->toArray();

            ProviderStatsDaily::updateOrCreate(
                ['provider_id' => $providerId, 'date' => $yesterday],
                [
                    'visits' => $counts['visit'] ?? 0,
                    'clicks' => $counts['click_contact'] ?? 0,
                    'contacts' => $counts['click_quote'] ?? 0,
                    'favorites_count' => $counts['favorite'] ?? 0,
                    'quote_requests_count' => $counts['quote_request'] ?? 0,
                ]
            );
        }
    }
}
