<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProviderStatsDaily extends Model
{
    protected $table = 'provider_stats_daily';

    protected $fillable = [
        'provider_id',
        'date',
        'visits',
        'clicks',
        'contacts',
        'favorites_count',
        'quote_requests_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'visits' => 'integer',
            'clicks' => 'integer',
            'contacts' => 'integer',
            'favorites_count' => 'integer',
            'quote_requests_count' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function scopeForProvider(Builder $query, int $providerId): Builder
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopePeriod(Builder $query, string $period): Builder
    {
        return match ($period) {
            '7d' => $query->where('date', '>=', now()->subDays(6)->startOfDay()),
            '30d' => $query->where('date', '>=', now()->subDays(29)->startOfDay()),
            '12m' => $query->where('date', '>=', now()->subMonths(11)->startOfMonth()),
            default => $query->where('date', '>=', now()->subDays(29)->startOfDay()),
        };
    }
}
