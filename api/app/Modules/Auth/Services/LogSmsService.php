<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsServiceInterface
{
    public function send(string $phone, string $message): bool
    {
        Log::info('SMS sent', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }
}
