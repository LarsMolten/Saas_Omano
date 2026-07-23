<?php

namespace App\Services;

use App\Models\ProviderEvent;
use Illuminate\Support\Facades\DB;

class ProviderEventTracker
{
    /**
     * Log a provider event asynchronously (fire-and-forget, no queue).
     */
    public static function track(int $providerId, string $eventType): void
    {
        ProviderEvent::create([
            'provider_id' => $providerId,
            'event_type' => $eventType,
            'created_at' => now(),
        ]);
    }
}
