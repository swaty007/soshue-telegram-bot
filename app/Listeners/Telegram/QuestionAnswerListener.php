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
            if (Str::contains($normalizedText, Str::lower($trigger))) {
                return true;
            }
        }

        return false;
    }
}
