<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class StartCommand extends Command
{
    protected string $command = 'start';

    protected ?string $description = 'Explain what this group chat bot can do';

    public function handle(Nutgram $bot): void
    {
        $bot->sendMessage(
            'Я тут, чтобы запоминать групповой хаос, реагировать на ключевые слова и пересказывать сутки так, будто у меня кончился кофе.',
        );
    }
}
