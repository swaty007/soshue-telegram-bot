<?php

namespace App\Telegram\Commands;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Jobs\Telegram\GenerateChatSummary;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

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

        GenerateChatSummary::dispatch($telegramMessage->chat, (int) config('telegram-bot.summary.threshold_max', 1000));

        $bot->sendMessage('Готовлю пересказ. Сейчас аккуратно превращу ваш поток сознания во что-то похожее на смысл.');
    }
}
