<?php

namespace App\Jobs;

use App\Contracts\Channels\EmailChannel;
use App\Contracts\Channels\SmsChannel;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $type,
        public array $payload,
        public bool $sendEmail = true,
        public bool $sendSms = false,
        public ?string $emailSubject = null,
        public ?string $emailBody = null,
    ) {
        $this->queue = 'database';
    }

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        Notification::create([
            'user_id' => $this->userId,
            'type' => $this->type,
            'payload' => $this->payload,
            'created_at' => now(),
        ]);

        if ($this->sendEmail && $this->emailSubject && $this->emailBody) {
            app(EmailChannel::class)->send($user, $this->emailSubject, $this->emailBody);
        }

        if ($this->sendSms && $this->emailBody) {
            app(SmsChannel::class)->send($user, $this->type, $this->emailBody);
        }
    }
}
