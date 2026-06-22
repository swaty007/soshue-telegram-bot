<?php

namespace App\Listeners\Telegram;

use App\Enums\TelegramChatSummaryStatus;
use App\Events\Telegram\TelegramMessageCreated;
use App\Jobs\Telegram\GenerateChatSummary;
use App\Models\TelegramChat;

class ChatSummaryListener
{
    /**
     * Handle the event.
     */
    public function handle(TelegramMessageCreated $event): void
    {
        $chat = $event->message->chat;

        if (! $this->shouldGenerate($chat)) {
            return;
        }

        GenerateChatSummary::dispatch($chat);
    }

    protected function shouldGenerate(TelegramChat $chat): bool
    {
        if (! $chat->summaries_enabled) {
            return false;
        }

        if ($chat->summaries()->whereIn('status', [
            TelegramChatSummaryStatus::Pending->value,
            TelegramChatSummaryStatus::Processing->value,
        ])->exists()) {
            return false;
        }

        $threshold = (int) config('telegram-bot.summary.threshold_min', 500);
        $dailyWindowHours = (int) config('telegram-bot.summary.daily_window_hours', 24);
        $since = $chat->last_summary_at ?? now()->subHours($dailyWindowHours);

        $messagesCount = $chat->messages()
            ->where('sent_at', '>=', $since)
            ->count();

        if ($messagesCount >= $threshold) {
            return true;
        }

        return $chat->last_summary_at !== null
            && $chat->last_summary_at->lte(now()->subHours($dailyWindowHours))
            && $messagesCount > 0;
    }
}
