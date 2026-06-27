<?php

use App\Events\Telegram\TelegramMessageCreated;
use App\Jobs\Telegram\GenerateQuestionAnswer;
use App\Listeners\Telegram\QuestionAnswerListener;
use App\Listeners\Telegram\QuickReactionListener;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Services\QuickReactionService;
use App\Telegram\Support\BuildRecentMessageContext;
use App\Telegram\Support\TelegramTriggerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
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
        ->and($wot['reactions'][2])->toMatchArray([
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

test('quick reactions ignore forwarded messages', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 778,
            'text' => 'time for world of tanks',
            'payload' => [
                'message_id' => 778,
                'date' => now()->timestamp,
                'chat' => [
                    'id' => -100123456789,
                    'type' => 'supergroup',
                    'title' => 'Test Group',
                ],
                'text' => 'time for world of tanks',
                'forward_origin' => [
                    'type' => 'user',
                    'date' => now()->subMinute()->timestamp,
                    'sender_user' => [
                        'id' => 43,
                        'is_bot' => false,
                        'first_name' => 'Forwarded',
                    ],
                ],
            ],
            'sent_at' => now(),
        ]);

    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => ['world of tanks'],
                'reactions' => [
                    [
                        'type' => 'text',
                        'text' => 'Tank reply.',
                    ],
                ],
            ],
        ],
    ]);

    Telegram::shouldReceive('sendMessage')->never();

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

test('quick reactions use configured message freshness window', function () {
    config(['telegram-bot.messages.freshness_minutes' => 60]);

    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 779,
            'text' => 'time for world of tanks',
            'sent_at' => now()->subMinutes(10),
        ]);

    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => ['world of tanks'],
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
            && quickReactionRepliesTo($arguments, 779));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

test('question answers use configured message freshness window', function () {
    Bus::fake();
    config(['telegram-bot.messages.freshness_minutes' => 60]);

    $message = TelegramMessage::factory()->create([
        'text' => 'бот, что тут происходит?',
        'sent_at' => now()->subMinutes(10),
    ]);

    app(QuestionAnswerListener::class)->handle(new TelegramMessageCreated($message));

    Bus::assertDispatched(GenerateQuestionAnswer::class);
});

test('telegram message factory uses configured freshness window', function () {
    config(['telegram-bot.messages.freshness_minutes' => 60]);
    $oldestAllowedSentAt = now()->subMinutes(60);

    $message = TelegramMessage::factory()->make();

    expect($message->sent_at)->toBeGreaterThanOrEqual($oldestAllowedSentAt);
});

test('quick reaction phrase triggers can match words in any order', function (string $text, bool $matches) {
    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => ['good-morning-vietnam'],
                'reactions' => [
                    [
                        'type' => 'text',
                        'text' => 'Vietnam reply.',
                    ],
                ],
            ],
        ],
    ]);

    $reaction = app(QuickReactionService::class)->findReaction($text);

    expect($reaction === null)->toBe(! $matches);

    if ($matches) {
        expect($reaction)->toMatchArray([
            'type' => 'text',
            'text' => 'Vietnam reply.',
        ]);
    }
})->with([
    'words reordered' => ['vietnam morning good', true],
    'words reordered with punctuation' => ['vietnam, good morning!', true],
    'missing word' => ['good vietnam', minimumWordsForPartialMatch() < 3],
    'too few matching words' => ['good afternoon', false],
]);

test('quick reaction short triggers match only as standalone words', function (string $trigger, string $text, bool $matches) {
    config([
        'telegram-quick-reactions' => [
            [
                'triggers' => [$trigger],
                'reactions' => [
                    [
                        'type' => 'text',
                        'text' => 'Matched reply.',
                    ],
                ],
            ],
        ],
    ]);

    $reaction = app(QuickReactionService::class)->findReaction($text);

    expect($reaction === null)->toBe(! $matches);

    if ($matches) {
        expect($reaction)->toMatchArray([
            'type' => 'text',
            'text' => 'Matched reply.',
        ]);
    }
})->with([
    'short trigger standalone' => ['php', 'php?', true],
    'short trigger inside word' => ['php', 'phpstorm?', false],
    'short phrase word inside another word' => ['good-morning-vietnam', 'goodbye vietnam?', false],
]);

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

test('quick reactions auto-map gif folder images by filename', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 1001,
            'text' => 'душно стало',
        ]);

    config(['telegram-quick-reactions' => []]);

    Telegram::shouldReceive('sendPhoto')
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] instanceof InputFile
            && $arguments[0]->getFilename() === 'душно-стало.webp'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 1001));

    app(QuickReactionListener::class)->handle(new TelegramMessageCreated($message->load('chat')));
});

test('quick reactions auto-map audio by filename part longer than five characters', function () {
    $chat = TelegramChat::factory()->create(['telegram_id' => -100123456789]);
    $message = TelegramMessage::factory()
        ->for($chat, 'chat')
        ->create([
            'telegram_message_id' => 1002,
            'text' => 'lowkick',
        ]);

    config(['telegram-quick-reactions' => []]);

    Telegram::shouldReceive('sendAudio')
        ->once()
        ->withArgs(fn (mixed ...$arguments): bool => $arguments[0] instanceof InputFile
            && $arguments[0]->getFilename() === '50-lowkick-eng.mp3'
            && $arguments[1] === -100123456789
            && quickReactionRepliesTo($arguments, 1002));

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

function minimumWordsForPartialMatch(): int
{
    $constant = (new ReflectionClass(TelegramTriggerMatcher::class))
        ->getReflectionConstant('MinimumWordsForPartialMatch');

    if ($constant === false) {
        throw new RuntimeException('Missing MinimumWordsForPartialMatch constant.');
    }

    $value = $constant->getValue();

    if (! is_int($value)) {
        throw new RuntimeException('MinimumWordsForPartialMatch must be an integer.');
    }

    return $value;
}

test('recent message context is built in chronological order', function () {
    $chat = TelegramChat::factory()->create();
    $user = TelegramUser::factory()->create([
        'first_name' => 'alex',
        'last_name' => null,
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

test('recent message context ignores messages that only contain links', function () {
    $chat = TelegramChat::factory()->create();

    TelegramMessage::factory()
        ->for($chat, 'chat')
        ->count(4)
        ->sequence(
            ['telegram_message_id' => 1, 'text' => 'https://example.com', 'sent_at' => now()->subMinutes(3)],
            ['telegram_message_id' => 2, 'text' => 't.me/huesos_helper_bot', 'sent_at' => now()->subMinutes(2)],
            ['telegram_message_id' => 3, 'text' => 'look at this https://example.com', 'sent_at' => now()->subMinute()],
            ['telegram_message_id' => 4, 'text' => 'https://example.com look at this', 'sent_at' => now()],
        )
        ->create();

    $context = app(BuildRecentMessageContext::class)->handle($chat, 30);

    expect($context)->not->toContain('[anonymous]: https://example.com')
        ->and($context)->not->toContain('[anonymous]: t.me/huesos_helper_bot')
        ->and($context)->toContain('look at this https://example.com')
        ->and($context)->toContain('https://example.com look at this');
});
