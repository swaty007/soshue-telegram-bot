<?php

namespace App\Enums;

enum TelegramChatSummaryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
