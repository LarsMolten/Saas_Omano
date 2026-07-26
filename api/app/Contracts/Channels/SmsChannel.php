<?php

namespace App\Contracts\Channels;

use App\Models\User;

class SmsChannel implements NotificationChannel
{
    public function send(User $user, string $subject, string $body): void
    {
        if (!$user->phone) {
            return;
        }

        // TODO: integrate with SMS provider (Twilio, Vonage, etc.)
        // For now, log the SMS that would be sent (without sensitive content)
        \Log::info("SMS sent to user #{$user->id}");
    }
}
