<?php

namespace App\Events\Telegram;

use App\Models\TelegramMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelegramMessageCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public TelegramMessage $message,
    ) {}
}
