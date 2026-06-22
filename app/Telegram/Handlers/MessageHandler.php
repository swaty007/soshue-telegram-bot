<?php

namespace App\Telegram\Handlers;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Events\Telegram\TelegramMessageCreated;
use SergiX44\Nutgram\Nutgram;

class MessageHandler
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected StoreTelegramMessage $storeTelegramMessage,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $telegramMessage = $this->storeTelegramMessage->handle($bot);

        if ($telegramMessage === null) {
            return;
        }

        TelegramMessageCreated::dispatch($telegramMessage);
    }
}
