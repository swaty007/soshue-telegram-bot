<?php

namespace App\Telegram\Commands;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Models\TelegramMessage;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class StatsCommand extends Command
{
    protected string $command = 'stats';

    protected ?string $description = 'Show saved chat message statistics';

    public function handle(Nutgram $bot, StoreTelegramMessage $storeTelegramMessage): void
    {
        $telegramMessage = $storeTelegramMessage->handle($bot);

        if ($telegramMessage === null) {
            $bot->sendMessage('Статистики нет. Даже считать нечего, трагедия.');

            return;
        }

        $chat = $telegramMessage->chat;
        $dailyStats = $this->messageStatsSince($chat->id, now()->subDay());
        $weeklyStats = $this->messageStatsSince($chat->id, now()->subWeek());
        $totalMessagesCount = $chat->messages()->count();

        $bot->sendMessage(<<<TEXT
Статистика сообщений:
За последние 24 часа: {$this->totalMessages($dailyStats)}
{$this->formatUserBreakdown($dailyStats)}

За последние 7 дней: {$this->totalMessages($weeklyStats)}
{$this->formatUserBreakdown($weeklyStats)}

Всего сообщений: {$totalMessagesCount}
TEXT);
    }

    /**
     * @return EloquentCollection<int, TelegramMessage>
     */
    protected function messageStatsSince(int $telegramChatId, mixed $since): EloquentCollection
    {
        $chatColumn = 'telegram_chat_id';
        $sentAtColumn = 'sent_at';
        $userColumn = 'telegram_user_id';
        $countColumn = 'messages_count';

        return TelegramMessage::query()
            ->select([
                $userColumn,
                DB::raw("count(*) as {$countColumn}"),
            ])
            ->with('user')
            ->where($chatColumn, $telegramChatId)
            ->where($sentAtColumn, '>=', $since)
            ->groupBy($userColumn)
            ->orderByDesc($countColumn)
            ->orderBy($userColumn)
            ->get();
    }

    /**
     * @param  EloquentCollection<int, TelegramMessage>  $messageStats
     */
    protected function formatUserBreakdown(EloquentCollection $messageStats): string
    {
        if ($messageStats->isEmpty()) {
            return '- сообщений пока нет';
        }

        return $messageStats
            ->map(fn (TelegramMessage $message): string => "- {$this->senderName($message)}: {$message->getAttribute('messages_count')}")
            ->implode(PHP_EOL);
    }

    /**
     * @param  EloquentCollection<int, TelegramMessage>  $messageStats
     */
    protected function totalMessages(EloquentCollection $messageStats): int
    {
        $countColumn = 'messages_count';

        return (int) $messageStats->sum($countColumn);
    }

    protected function senderName(TelegramMessage $message): string
    {
        if ($message->user?->username) {
            return '@'.$message->user->username;
        }

        $name = trim(implode(' ', array_filter([
            $message->user?->first_name,
            $message->user?->last_name,
        ])));

        if ($name !== '') {
            return $name;
        }

        return $message->telegram_user_id !== null
            ? "Telegram user #{$message->telegram_user_id}"
            : 'Unknown user';
    }
}
