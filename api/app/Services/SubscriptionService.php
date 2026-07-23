<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SubscriptionService
{
    /**
     * Get the active subscription for a provider, or null.
     */
    public function getActiveSubscription(int $providerId): ?Subscription
    {
        return Subscription::where('provider_id', $providerId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Get the current plan key for a provider ('starter' if no active sub).
     */
    public function getCurrentPlan(int $providerId): string
    {
        $sub = $this->getActiveSubscription($providerId);
        return $sub?->plan ?? Config::get('subscription.default_plan', 'starter');
    }

    /**
     * Get limits array for the given plan key.
     */
    public function getLimits(string $plan): array
    {
        return Config::get("subscription.plans.{$plan}.limits", []);
    }

    /**
     * Get limits for a provider (resolves plan from subscription).
     */
    public function getLimitsForProvider(int $providerId): array
    {
        $plan = $this->getCurrentPlan($providerId);
        return $this->getLimits($plan);
    }

    /**
     * Check if provider can add more portfolio media (total across all items).
     */
    public function canAddMedia(int $providerId, int $countToAdd = 1): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        $max = $limits['max_portfolio_media'] ?? null;

        if ($max === null) {
            return true;
        }

        $currentCount = \App\Models\PortfolioMedia::whereHas('portfolioItem', function ($q) use ($providerId) {
            $q->where('provider_id', $providerId);
        })->count();

        return ($currentCount + $countToAdd) <= $max;
    }

    /**
     * Check if provider can add more services / active publications.
     */
    public function canAddService(int $providerId): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        $max = $limits['max_services'] ?? null;

        if ($max === null) {
            return true;
        }

        $currentCount = \App\Models\Service::where('provider_id', $providerId)->count();

        return $currentCount < $max;
    }

    /**
     * Check if provider allows video uploads.
     */
    public function allowsVideo(int $providerId): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        return $limits['allows_video'] ?? false;
    }

    /**
     * Check if provider has Pro badge.
     */
    public function hasProBadge(int $providerId): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        return $limits['has_pro_badge'] ?? false;
    }

    /**
     * Check if provider has search boost (Premium).
     */
    public function hasSearchBoost(int $providerId): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        return $limits['has_search_boost'] ?? false;
    }

    /**
     * Check if provider has advanced stats (Pro/Premium).
     */
    public function hasAdvancedStats(int $providerId): bool
    {
        $limits = $this->getLimitsForProvider($providerId);
        return $limits['has_advanced_stats'] ?? false;
    }

    /**
     * Get remaining media slots.
     */
    public function remainingMediaSlots(int $providerId): ?int
    {
        $limits = $this->getLimitsForProvider($providerId);
        $max = $limits['max_portfolio_media'] ?? null;

        if ($max === null) {
            return null;
        }

        $currentCount = \App\Models\PortfolioMedia::whereHas('portfolioItem', function ($q) use ($providerId) {
            $q->where('provider_id', $providerId);
        })->count();

        return max(0, $max - $currentCount);
    }

    /**
     * Get remaining service slots.
     */
    public function remainingServiceSlots(int $providerId): ?int
    {
        $limits = $this->getLimitsForProvider($providerId);
        $max = $limits['max_services'] ?? null;

        if ($max === null) {
            return null;
        }

        $currentCount = \App\Models\Service::where('provider_id', $providerId)->count();

        return max(0, $max - $currentCount);
    }
}
