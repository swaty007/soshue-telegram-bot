<?php

use App\Events\Telegram\TelegramMessageCreated;
use App\Listeners\Telegram\QuickReactionListener;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Telegram\Support\BuildRecentMessageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Nutgram\Laravel\Facades\Telegram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Message\ReplyParameters;

uses(RefreshDatabase::class);

test('quick reaction config uses grouped trigger and reaction format', function () {
    $groups = require config_path('telegram-quick-reactions.php');
    $wot = Collection::make($groups)->first(
        fn (array $group): bool => in_array('WOT', $group['triggers'], true),
    );

    expect($wot['triggers'])->toContain('world of tanks')
        ->and($wot['reactions'][1])->toMatchArray([
            'type' => 'video',
            'path' => 'images/gif/WOT.mp4',
        ]);
});

test('quick reaction groups can match multiple triggers', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 777,
            'text' => 'time for world of tanks',
        ]);

    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => ['wot', 'world of tanks', 'танки'],
                'reactions' => [
                    [
                        'type' => 'text',
                        'text' => 'Tank reply.',
                    ],
                ],
            ],
        ],
    ]);

    Telegram::shouldReceive('sendMessage')
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] === 'Tank reply.'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 777));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

test('quick reactions can send media replies', function (string $type, string $method) {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 888,
            'text' => "send {$type} now",
        ]);

    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => [$type],
                'reactions' => [
                    [
                        'type' => $type,
                        'path' => 'images/gif/сарказм.mp4',
                    ],
                ],
            ],
        ],
    ]);

    Telegram::shouldReceive($method)
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] instanceof InputFile
            && $arguments[0]->getFilename() === 'сарказм.mp4'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 888));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
})->with([
    'photo' => ['photo', 'sendPhoto'],
    'video' => ['video', 'sendVideo'],
    'audio' => ['audio', 'sendAudio'],
]);

test('quick reactions auto-map gif videos by filename', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 999,
            'text' => 'сарказм',
        ]);

    config(['telegram-quick-reactions' => []]);

    Telegram::shouldReceive('sendVideo')
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] instanceof InputFile
            && $arguments[0]->getFilename() === 'сарказм.mp4'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 999));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

test('quick reactions auto-map gif videos by filename part longer than five characters', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 1000,
            'text' => 'аксиома',
        ]);

    config(['telegram-quick-reactions' => []]);

    Telegram::shouldReceive('sendVideo')
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] instanceof InputFile
            && $arguments[0]->getFilename() === 'эскобар-аксиома.mp4'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 1000));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

/**
 * @param  array<int|string, mixed>  $arguments
 */
function quickReactionRepliesTo(array $arguments, int $messageId): bool
{
    foreach ($arguments as $argument) {
        if (! $argument instanceof ReplyParameters) {
            continue;
        }

        return ($argument->jsonSerialize()['message_id'] ?? null) === $messageId;
    }

    return false;
}

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

    expect($context)->toContain('[alex]: older')
        ->and($context)->toContain('[alex]: newer')
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
