<?php

namespace App\Ai\Telegram;

enum TelegramAgentTask
{
    case DailyChatSummary;
    case QuestionAnswer;
    case RecentMessagesRoast;
}
