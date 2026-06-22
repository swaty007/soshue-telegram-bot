<?php

namespace App\Telegram\Commands;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Jobs\Telegram\GenerateRecentMessagesRoast;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class RoastCommand extends Command
{
    protected string $command = 'roast';

    protected ?string $description = 'Roast the recent chat context';

    public function handle(Nutgram $bot, StoreTelegramMessage $storeTelegramMessage): void
    {
        $telegramMessage = $storeTelegramMessage->handle($bot);

        if ($telegramMessage === null) {
            $bot->sendMessage('Я бы вас прожарил, но вы даже сообщение нормально не дали.');

            return;
        }

        GenerateRecentMessagesRoast::dispatch(
            $telegramMessage->chat,
            (int) config('telegram-bot.summary.recent_messages_limit', 30),
        );

        $bot->sendMessage('Ща гляну последние сообщения и скажу, кто тут главный генератор шума.');
    }
}
