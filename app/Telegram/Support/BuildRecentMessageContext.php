<?php

namespace App\Telegram\Support;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BuildRecentMessageContext
{
    /**
     * Build chronological plain-text context from recent chat messages.
     */
    public function handle(TelegramChat $chat, ?int $limit = null): string
    {
        return $this->messages($chat, $limit)
            ->map(function (TelegramMessage $message): string {
                $user = $message->user;
                if ($user?->first_name) {
                    $sender = trim($user->first_name.' '.($user->last_name ?? ''));
                } elseif ($user?->username) {
                    $sender = trim($user->username.' '.($user->last_name ?? ''));
                } else {
                    $sender = 'anonymous';
                }

                $sentAt = $message->sent_at?->format('Y-m-d H:i') ?? 'unknown time';

                // return "[{$sentAt}] {$sender}: {$message->text}";
                return "[{$sender}]: {$message->text}";
            })
            ->implode(PHP_EOL);
    }

    /**
     * Get recent messages in chronological order.
     *
     * @return Collection<int, TelegramMessage>
     */
    public function messages(TelegramChat $chat, ?int $limit = null): Collection
    {
        $limit ??= (int) config('telegram-bot.summary.recent_messages_limit', 30);

        return TelegramMessage::query()
            ->with('user')
            ->whereNotNull('text')
            ->recentForChat($chat, $limit)
            ->get()
            ->filter(fn (TelegramMessage $message): bool => $this->hasTextBeyondLinks($message))
            ->reverse()
            ->values();
    }

    private function hasTextBeyondLinks(TelegramMessage $message): bool
    {
        $text = trim((string) $message->text);

        return ! Str::isUrl($text, ['http', 'https'])
            && ! (Str::contains($text, '.') && Str::isUrl("https://{$text}", ['http', 'https']));
    }
}
