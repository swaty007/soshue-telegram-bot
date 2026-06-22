<?php

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Telegram\Support\BuildRecentMessageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recent message context is built in chronological order', function () {
    $chat = TelegramChat::factory()->create();
    $user = TelegramUser::factory()->create([
        'first_name' => 'Alex',
        'username' => 'alex',
    ]);

    TelegramMessage::factory()
        ->for($chat, 'chat')
        ->for($user, 'user')
        ->create([
            'telegram_message_id' => 1,
            'text' => 'newer',
            'sent_at' => now()->subMinute(),
        ]);

    TelegramMessage::factory()
        ->for($chat, 'chat')
        ->for($user, 'user')
        ->create([
            'telegram_message_id' => 2,
            'text' => 'older',
            'sent_at' => now()->subMinutes(2),
        ]);

    $context = app(BuildRecentMessageContext::class)->handle($chat, 30);

    expect($context)->toContain('alex: older')
        ->and($context)->toContain('alex: newer')
        ->and(strpos($context, 'older'))->toBeLessThan(strpos($context, 'newer'));
});

test('recent message context respects the requested limit', function () {
    $chat = TelegramChat::factory()->create();

    TelegramMessage::factory()
        ->for($chat, 'chat')
        ->count(3)
        ->sequence(
            ['telegram_message_id' => 1, 'text' => 'first', 'sent_at' => now()->subMinutes(3)],
            ['telegram_message_id' => 2, 'text' => 'second', 'sent_at' => now()->subMinutes(2)],
            ['telegram_message_id' => 3, 'text' => 'third', 'sent_at' => now()->subMinute()],
        )
        ->create();

    $context = app(BuildRecentMessageContext::class)->handle($chat, 2);

    expect($context)->not->toContain('first')
        ->and($context)->toContain('second')
        ->and($context)->toContain('third');
});
