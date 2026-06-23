<?php

namespace App\Listeners\Telegram;

use App\Events\Telegram\TelegramMessageCreated;
use App\Jobs\Telegram\GenerateQuestionAnswer;
use Illuminate\Support\Str;

class QuestionAnswerListener
{
    /**
     * Handle the event.
     */
    public function handle(TelegramMessageCreated $event): void
    {
        if (! $this->shouldAnswer($event->message->text)) {
            return;
        }

        GenerateQuestionAnswer::dispatch($event->message);
    }

    protected function shouldAnswer(?string $text): bool
    {
        if (! config('telegram-bot.questions.enabled', true)) {
            return false;
        }

        if ($text === null || ! Str::contains($text, '?')) {
            return false;
        }

        $normalizedText = Str::lower($text);

        /** @var array<int, string> $triggers */
        $triggers = config('telegram-bot.questions.triggers', []);

        foreach ($triggers as $trigger) {
            if ($this->containsTrigger($normalizedText, $trigger)) {
                return true;
            }
        }

        return false;
    }

    protected function containsTrigger(string $text, string $trigger): bool
    {
        $trigger = Str::lower(trim($trigger));

        if ($trigger === '') {
            return false;
        }

        if (Str::length($trigger) > 5) {
            if (Str::contains($text, $trigger)) {
                return true;
            }

            return false;
        }

        return preg_match(
            '/(?<![\pL\pN])'.preg_quote($trigger, '/').'(?![\pL\pN])/iu',
            $text,
        ) === 1;
    }
}
