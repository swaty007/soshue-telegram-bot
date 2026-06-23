<?php

namespace App\Jobs\Telegram;

use App\Ai\Agents\QuestionAnswerAgent;
use App\Ai\Telegram\Moods\TelegramBotMoodResolver;
use App\Models\TelegramMessage;
use App\Telegram\Support\BuildRecentMessageContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Nutgram\Laravel\Facades\Telegram;
use SergiX44\Nutgram\Telegram\Types\Message\ReplyParameters;
use Stringable;

class GenerateQuestionAnswer implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 330;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TelegramMessage $message,
    ) {
        $this->onQueue(config('telegram-bot.ai.queue', 'long_running'));
    }

    /**
     * Execute the job.
     */
    public function handle(BuildRecentMessageContext $buildRecentMessageContext, TelegramBotMoodResolver $moodResolver): void
    {
        $message = $this->message->fresh(['chat']);

        if (! $message instanceof TelegramMessage || $message->text === null) {
            return;
        }

        $context = $buildRecentMessageContext->handle(
            $message->chat,
            (int) config('telegram-bot.summary.recent_messages_limit', 30),
        );

        $agent = new QuestionAnswerAgent($moodResolver->resolve($message->text));
        $response = $agent->prompt($agent->promptForQuestion($message->text, $context));

        Telegram::sendMessage(
            $this->responseText($response),
            $message->chat->telegram_id,
            reply_parameters: ReplyParameters::make($message->telegram_message_id),
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

        return 'Ответ есть, но AI решила спрятать его, очень по-взрослому.';
    }
}
