<?php

namespace App\Contracts\Channels;

use App\Models\User;

interface NotificationChannel
{
    public function send(User $user, string $subject, string $body): void;
}
