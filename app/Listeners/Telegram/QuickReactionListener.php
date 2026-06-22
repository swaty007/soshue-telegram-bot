<?php

namespace App\Listeners\Telegram;

use App\Events\Telegram\TelegramMessageCreated;
use App\Telegram\Support\QuickReactionService;

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

        if (! $message->chat->reactions_enabled) {
            return;
        }

        $this->quickReactions->sendForMessage($message);
    }
}
