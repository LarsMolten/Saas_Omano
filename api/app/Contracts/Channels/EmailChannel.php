<?php

namespace App\Contracts\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements NotificationChannel
{
    public function send(User $user, string $subject, string $body): void
    {
        if (!$user->email) {
            return;
        }

        Mail::raw($body, function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->subject($subject);
        });
    }
}
