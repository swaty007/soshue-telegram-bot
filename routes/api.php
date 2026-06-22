<?php

use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::post('/telegram/webhook', fn (Nutgram $bot) => $bot->run())
    ->name('telegram.webhook');
