<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review->provider_id);
    }

    public function updated(Review $review): void
    {
        $this->recalculate($review->provider_id);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->provider_id);
    }

    protected function recalculate(int $providerId): void
    {
        $stats = DB::table('reviews')
            ->where('provider_id', $providerId)
            ->where('status', 'published')
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as cnt')
            ->first();

        User::where('id', $providerId)->update([
            'average_rating' => round($stats->avg_rating, 2),
            'rating_count' => $stats->cnt,
        ]);
    }
}
