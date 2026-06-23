<?php

namespace App\Ai\Telegram\Moods;

use InvalidArgumentException;

final class TelegramBotMoodResolver
{
    public function resolve(?string $text = null): TelegramBotMood
    {
        return $this->resolveByRoll(random_int(1, 100));
    }

    public function resolveByRoll(int $roll): TelegramBotMood
    {
        if ($roll < 1 || $roll > 100) {
            throw new InvalidArgumentException('Mood roll must be between 1 and 100.');
        }

        if ($roll <= 70) {
            return new PoisonMood;
        }

        return new FriendlyMood;
    }
}
