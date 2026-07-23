<?php

namespace App\Services;

use App\Models\AdminActionLog;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function logAction(
        int $adminId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $reason = null,
        ?array $metadata = null,
    ): AdminActionLog {
        return AdminActionLog::create([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    public function getGlobalStats(): array
    {
        $users = DB::table('users');

        $totalUsers = (clone $users)->count();
        $totalPrestataires = (clone $users)->where('role', 'prestataire')->count();
        $activePrestataires = (clone $users)
            ->where('role', 'prestataire')
            ->where('status', 'active')
            ->count();
        $totalClients = (clone $users)->where('role', 'client')->count();
        $suspendedUsers = (clone $users)->where('status', 'suspended')->count();
        $bannedUsers = (clone $users)->where('status', 'banned')->count();

        $subscriptions = DB::table('subscriptions')
            ->where('status', 'active')
            ->selectRaw("plan, count(*) as count")
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        $revenue = DB::table('payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'payments.subscription_id')
            ->where('payments.status', 'success')
            ->selectRaw("subscriptions.plan, sum(payments.amount) as total")
            ->groupBy('subscriptions.plan')
            ->pluck('total', 'plan')
            ->toArray();

        $pendingReports = DB::table('reports')
            ->where('status', 'pending')
            ->count();

        $totalReviews = DB::table('reviews')->count();
        $reportedReviews = DB::table('reviews')
            ->where('status', 'reported')
            ->count();

        return [
            'users' => [
                'total' => $totalUsers,
                'clients' => $totalClients,
                'prestataires' => $totalPrestataires,
                'active_prestataires' => $activePrestataires,
                'suspended' => $suspendedUsers,
                'banned' => $bannedUsers,
            ],
            'subscriptions' => [
                'active' => array_sum($subscriptions),
                'by_plan' => $subscriptions,
            ],
            'revenue' => [
                'by_plan' => $revenue,
                'total' => array_sum($revenue),
            ],
            'reports' => [
                'pending' => $pendingReports,
            ],
            'reviews' => [
                'total' => $totalReviews,
                'reported' => $reportedReviews,
            ],
        ];
    }
}
