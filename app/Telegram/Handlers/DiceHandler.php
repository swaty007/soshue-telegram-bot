<?php

namespace App\Telegram\Handlers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\DiceEmoji;

class DiceHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $chatId = $bot->chatId();

        if ($chatId === null) {
            return;
        }

        RateLimiter::attempt(
            $this->rateLimitKey($chatId),
            maxAttempts: 1,
            callback: fn () => $bot->sendDice($chatId, emoji: $this->randomEmoji()),
            decaySeconds: $this->decaySeconds(),
        );
    }

    private function rateLimitKey(int $chatId): string
    {
        return "telegram:dice:chat:{$chatId}";
    }

    private function decaySeconds(): int
    {
        return (int) config('telegram-bot.dice.decay_seconds');
    }

    private function randomEmoji(): string
    {
        return Arr::random(DiceEmoji::cases())->value;
    }
}
