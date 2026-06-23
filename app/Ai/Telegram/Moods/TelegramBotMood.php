<?php

namespace App\Ai\Telegram\Moods;

use App\Ai\Telegram\TelegramAgentTask;

interface TelegramBotMood
{
    public function key(): string;

    public function instructions(TelegramAgentTask $task): string;
}
