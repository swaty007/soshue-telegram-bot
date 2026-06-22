<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;

class FallbackHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $text = $bot->message()?->text;

        if ($text !== null && str_starts_with($text, '/')) {
            $bot->sendMessage('Такой команды нет. Смело, но бесполезно.');
        }
    }
}
