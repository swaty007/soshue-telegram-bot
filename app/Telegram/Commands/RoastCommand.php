<?php

namespace App\Telegram\Commands;

use App\Actions\Telegram\StoreTelegramMessage;
use App\Jobs\Telegram\GenerateRecentMessagesRoast;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class RoastCommand extends Command
{
    protected string $command = 'roast ?{limit}';

    protected ?string $description = 'Generate a roast based on recent messages in the chat. You can enter the number of roasts you want to send.';

    public function handle(Nutgram $bot, ?string $limit, StoreTelegramMessage $storeTelegramMessage): void
    {
        $telegramMessage = $storeTelegramMessage->handle($bot);

        if ($telegramMessage === null) {
            $bot->sendMessage('Я бы вас прожарил, но вы даже сообщение нормально не дали.');

            return;
        }

        $limit = min($this->messageLimit($limit), (int) config('telegram-bot.summary.threshold_max'));

        GenerateRecentMessagesRoast::dispatch(
            $telegramMessage->chat,
            $limit,
        );

        $bot->sendMessage("Ща гляну последние {$limit} сообщений и скажу, кто тут главный генератор шума.");
    }

    private function messageLimit(?string $limit): int
    {
        if (is_string($limit) && trim($limit) !== '') {
            return max(1, (int) trim($limit));
        }

        return (int) config('telegram-bot.summary.recent_messages_limit');
    }
}
