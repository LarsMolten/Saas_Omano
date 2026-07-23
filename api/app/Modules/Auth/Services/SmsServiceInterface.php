<?php

namespace App\Modules\Auth\Services;

interface SmsServiceInterface
{
    public function send(string $phone, string $message): bool;
}
