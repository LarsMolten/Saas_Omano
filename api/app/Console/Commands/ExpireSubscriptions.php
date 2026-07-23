<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions whose ends_at has passed and notify providers';

    public function handle(): int
    {
        $expired = Subscription::expired()->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'expired']);

            SendNotification::dispatch(
                userId: $subscription->provider_id,
                type: 'subscription.expired',
                payload: [
                    'subscription_id' => $subscription->id,
                    'plan' => $subscription->plan,
                    'period' => $subscription->period,
                    'expired_at' => $subscription->ends_at->toISOString(),
                ],
                emailSubject: 'Votre abonnement a expiré',
                emailBody: "Votre abonnement {$subscription->plan} a expiré le {$subscription->ends_at->format('d/m/Y')}. Renouvelez pour continuer à profiter de vos fonctionnalités.",
            );

            $count++;
        }

        $this->info("{$count} subscription(s) expirée(s).");

        return self::SUCCESS;
    }
}
