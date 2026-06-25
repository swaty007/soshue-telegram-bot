<?php

namespace App\Jobs\Telegram;

use App\Ai\Agents\RecentMessagesRoastAgent;
use App\Ai\Telegram\Moods\TelegramBotMoodResolver;
use App\Models\TelegramChat;
use App\Telegram\Support\BuildRecentMessageContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\UniqueFor;
use Nutgram\Laravel\Facades\Telegram;
use Stringable;

#[UniqueFor(1230)]
class GenerateRecentMessagesRoast implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 1230;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TelegramChat $chat,
        public int $limit = 30,
    ) {
        $this->onQueue(config('telegram-bot.ai.queue', 'long_running'));
    }

    public function uniqueId(): string
    {
        return (string) $this->chat->telegram_id;
    }

    /**
     * Execute the job.
     */
    public function handle(BuildRecentMessageContext $buildRecentMessageContext, TelegramBotMoodResolver $moodResolver): void
    {
        $chat = $this->chat->fresh();

        if (! $chat instanceof TelegramChat) {
            return;
        }

        $context = $buildRecentMessageContext->handle($chat, $this->limit);

        if (trim($context) === '') {
            Telegram::sendMessage('Я бы проанализировал последние сообщения, но там пустота. Очень в стиле этого чата.', $chat->telegram_id);

            return;
        }

        $mood = $moodResolver->resolve($context);
        $agent = new RecentMessagesRoastAgent($mood);
        $response = $agent->prompt($agent->promptForMessages($context));

        Telegram::sendMessage(
            $this->responseText($response).PHP_EOL.'Mood: '.$mood->key(),
            $chat->telegram_id
        );
    }

    protected function responseText(mixed $response): string
    {
        $text = data_get($response, 'text');

        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }

        if ($response instanceof Stringable) {
            return trim((string) $response);
        }

        return 'Я посмотрел и пожалел. Детали машина отказалась формулировать.';
    }
}
