<?php

namespace App\Listeners\Telegram;

use App\Events\Telegram\TelegramMessageCreated;
use App\Services\QuickReactionService;

class QuickReactionListener
{
    public function __construct(
        protected QuickReactionService $quickReactions,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(TelegramMessageCreated $event): void
    {
        $message = $event->message;
        if (
            ! $message->chat->reactions_enabled
            || now()->subMinutes((int) config('telegram-bot.messages.freshness_minutes')) >= $message->sent_at
            || $message->payload->isForwarded() // || $message->payload->is_automatic_forward === true
        ) {
            return;
        }

        $this->quickReactions->sendForMessage($message);
    }
}
