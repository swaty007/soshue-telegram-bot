<?php

namespace App\Listeners\Telegram;

use App\Events\Telegram\TelegramMessageCreated;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Nutgram\Laravel\Facades\Telegram;

class QuickReactionListener
{
    /**
     * Handle the event.
     */
    public function handle(TelegramMessageCreated $event): void
    {
        $message = $event->message;

        if (! $message->chat->reactions_enabled) {
            return;
        }

        $reply = $this->findReply($message->text);

        if ($reply === null) {
            return;
        }

        Telegram::sendMessage($reply, $message->chat->telegram_id);
    }

    protected function findReply(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $normalizedText = Str::lower($text);

        /** @var array<string, array<int, string>|string> $reactions */
        $reactions = config('telegram-bot.quick_reactions', []);

        foreach ($reactions as $trigger => $reply) {
            if (! Str::contains($normalizedText, Str::lower($trigger))) {
                continue;
            }

            return is_array($reply) ? Arr::random($reply) : $reply;
        }

        return null;
    }
}
