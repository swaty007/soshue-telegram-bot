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
        $messagesCount = $this->messagesCountToAnalyze($chat);

        if (empty($messagesCount)) {
            return;
        }

        GenerateChatSummary::dispatch($chat, max($messagesCount, 50));
    }

    protected function messagesCountToAnalyze(TelegramChat $chat): ?int
    {
        if (! $chat->summaries_enabled) {
            return null;
        }

        if ($chat->summaries()
            ->where('created_at', '>=', now()->subHours(2))
            ->whereIn('status', [
                TelegramChatSummaryStatus::Pending->value,
                TelegramChatSummaryStatus::Processing->value,
            ])->exists()) {
            return null;
        }

        $threshold = (int) config('telegram-bot.summary.threshold_min');
        $dailyWindowHours = (int) config('telegram-bot.summary.daily_window_hours', 12);
        $since = $chat->last_summary_at ?? now()->subHours($dailyWindowHours);

        $messagesCount = $chat->messages()
            ->where('sent_at', '>=', $since)
            ->count();

        if ($messagesCount >= $threshold) {
            return $messagesCount;
        }

        if (
            $chat->last_summary_at !== null
            && $chat->last_summary_at->lte(now()->subHours($dailyWindowHours))
            && $messagesCount > 0
        ) {
            return $messagesCount;
        }

        return null;
    }
}
