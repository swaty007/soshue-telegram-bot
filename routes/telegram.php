<?php

/** @var Nutgram $bot */

use App\Telegram\Commands\RoastCommand;
use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\StatsCommand;
use App\Telegram\Commands\SummaryCommand;
use App\Telegram\Handlers\FallbackHandler;
use App\Telegram\Handlers\MessageHandler;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

$bot->registerCommand(StartCommand::class);
$bot->registerCommand(SummaryCommand::class);
$bot->registerCommand(RoastCommand::class);
$bot->registerCommand(StatsCommand::class);

$bot->onMessage(MessageHandler::class);
$bot->fallback(FallbackHandler::class);
