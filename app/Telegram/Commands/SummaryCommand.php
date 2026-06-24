<?php

namespace App\Telegram\Commands;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Enums\TelegramChatSummaryStatus;
use App\Jobs\Telegram\GenerateChatSummary;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\ReplyParameters;

class SummaryCommand extends Command
{
    protected string $command = 'summary';

    protected ?string $description = 'Summarize recent group messages';

    public function handle(Nutgram $bot, StoreTelegramMessage $storeTelegramMessage): void
    {
        $telegramMessage = $storeTelegramMessage->handle($bot);

        if ($telegramMessage === null) {
            $bot->sendMessage('Не вижу сообщения. Отличное начало, ничего не скажешь.');

            return;
        }

        $chat = $telegramMessage->chat;

        if ($chat->summaries()
            ->where('created_at', '>=', now()->subHours(1))
            ->whereIn('status', [
                TelegramChatSummaryStatus::Pending->value,
                TelegramChatSummaryStatus::Processing->value,
            ])->exists()) {
            return;
        }

        $since = $chat->last_summary_at ?? now()->subHours(24);

        $messagesCount = $chat->messages()
            ->where('sent_at', '>=', $since)
            ->count();
        $messagesCount = max($messagesCount, 50);

        GenerateChatSummary::dispatch($chat, (int) $messagesCount); // config('telegram-bot.summary.threshold_max', 5000)

        $bot->sendMessage(
            'Готовлю пересказ. Сейчас аккуратно превращу ваш поток сознания во что-то похожее на смысл. Количество сообщений для анализа: '.$messagesCount,
            reply_parameters: ReplyParameters::make($telegramMessage->telegram_message_id)
        );
    }
}
