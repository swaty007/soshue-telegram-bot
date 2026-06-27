<?php

namespace App\Jobs\Telegram;

use App\Ai\Agents\DailyChatSummaryAgent;
use App\Ai\Telegram\Moods\PoisonMood;
use App\Ai\Telegram\Moods\TelegramBotMoodResolver;
use App\Enums\TelegramChatSummaryStatus;
use App\Models\TelegramChat;
use App\Models\TelegramChatSummary;
use App\Telegram\Support\BuildRecentMessageContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\UniqueFor;
use Nutgram\Laravel\Facades\Telegram;
use Stringable;
use Throwable;

#[UniqueFor(1530)]
class GenerateChatSummary implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const int MINIMUM_MESSAGE_LIMIT = 50;

    public int $tries = 3;

    public int $timeout = 1530;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TelegramChat $chat,
        public ?int $limit = null,
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

        $limit = $this->limit ?? (int) config('telegram-bot.summary.threshold_max');
        $messages = $buildRecentMessageContext->messages($chat, $limit);

        if ($messages->isEmpty()) {
            Telegram::sendMessage('Пересказывать нечего. Невероятный уровень пустоты.', $chat->telegram_id);

            return;
        }

        $context = $buildRecentMessageContext->handle($chat, $limit);
        $periodStartedAt = $messages->first()->sent_at ?? now();
        $periodEndedAt = $messages->last()->sent_at ?? now();

        $summary = TelegramChatSummary::create([
            'telegram_chat_id' => $chat->id,
            'period_started_at' => $periodStartedAt,
            'period_ended_at' => $periodEndedAt,
            'message_count' => $messages->count(),
            'prompt_fingerprint' => hash('sha256', $context),
            'status' => TelegramChatSummaryStatus::Processing,
        ]);

        try {
            $agent = new DailyChatSummaryAgent(new PoisonMood); // $moodResolver->resolve($context)
            $response = $agent->prompt($agent->promptForMessages($context));
            $summaryText = $this->responseText($response);

            $summary->update([
                'summary' => $summaryText,
                'status' => TelegramChatSummaryStatus::Completed,
            ]);

            $chat->update([
                'last_summary_at' => $periodEndedAt,
            ]);

            Telegram::sendMessage($summaryText, $chat->telegram_id);
        } catch (Throwable $exception) {
            $summary->update([
                'status' => TelegramChatSummaryStatus::Failed,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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

        return 'AI ничего не сказала. Даже машина решила, что это слишком.';
    }
}
