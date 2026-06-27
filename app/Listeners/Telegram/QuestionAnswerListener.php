<?php

namespace App\Listeners\Telegram;

use App\Events\Telegram\TelegramMessageCreated;
use App\Jobs\Telegram\GenerateQuestionAnswer;
use App\Telegram\Support\TelegramTriggerMatcher;
use Illuminate\Support\Str;

class QuestionAnswerListener
{
    /**
     * Handle the event.
     */
    public function handle(TelegramMessageCreated $event): void
    {
        if (
            ! $this->shouldAnswer($event->message->text)
            || now()->subMinutes((int) config('telegram-bot.messages.freshness_minutes')) >= $event->message->sent_at
        ) {
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

        /** @var list<string> $triggers */
        $triggers = config('telegram-bot.questions.triggers', []);

        return TelegramTriggerMatcher::matchesAny($normalizedText, $triggers);
        // foreach ($triggers as $trigger) {
        //     if (TelegramTriggerMatcher::contains($normalizedText, $trigger)) {
        //         return true;
        //     }
        // }

        // return false;
    }
}
