<?php

namespace App\Services;

use App\Models\TelegramMessage;
use App\Telegram\Support\QuickReactionSender;
use App\Telegram\Support\TelegramTriggerMatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class QuickReactionService
{
    private const string AutoVideoDirectory = 'images';

    private const string AutoAudioDirectory = 'audio';

    /**
     * @var array<string, array<string, string>>
     */
    private const array AutoReactionMediaTypes = [
        self::AutoVideoDirectory => [
            'mp4' => QuickReactionSender::Video,
            'webp' => QuickReactionSender::Photo,
            'png' => QuickReactionSender::Photo,
            'gif' => QuickReactionSender::Photo,
        ],
        self::AutoAudioDirectory => [
            'mp3' => QuickReactionSender::Audio,
            'ogg' => QuickReactionSender::Audio,
        ],
    ];

    public function __construct(
        protected QuickReactionSender $sender,
    ) {}

    public function sendForMessage(TelegramMessage $message): void
    {
        $reaction = $this->findReaction($message->text);

        if (empty($reaction)) {
            return;
        }

        RateLimiter::attempt(
            $this->rateLimitKey($message->chat->telegram_id, $reaction),
            maxAttempts: 1,
            callback: fn () => $this->sender->send(
                $reaction,
                $message->chat->telegram_id,
                $message->telegram_message_id,
            ),
            decaySeconds: 60 * 20,
        );
    }

    /**
     * @param  array{type: string, text?: string, path?: string, caption?: string|null}  $reaction
     */
    private function rateLimitKey(int $chatId, array $reaction): string
    {
        $reactionKey = $reaction['path'] ?? $reaction['text'];

        return "telegram:reaction:{$chatId}:reaction:{$reactionKey}";
    }

    /**
     * @return array{type: string, text?: string, path?: string, caption?: string|null}|null
     */
    public function findReaction(?string $text): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        foreach ($this->reactionGroups() as $group) {
            if (! TelegramTriggerMatcher::matchesAny($text, $group['triggers'])) {
                continue;
            }

            return Arr::random($group['reactions']);
        }

        return null;
    }

    /**
     * @return list<array{triggers: list<string>, reactions: list<array{type: string, text?: string, path?: string, caption?: string|null}>}>
     */
    protected function reactionGroups(): array
    {
        /** @var list<array{triggers: list<string>, reactions: list<array{type: string, text?: string, path?: string, caption?: string|null}>}> $configuredGroups */
        $configuredGroups = config('telegram-quick-reactions', []);

        return [
            ...$configuredGroups,
            ...$this->autoVideoReactionGroups($configuredGroups),
        ];
    }

    /**
     * @param  list<array{triggers: list<string>, reactions: list<array{type: string, text?: string, path?: string, caption?: string|null}>}>  $configuredGroups
     * @return list<array{triggers: list<string>, reactions: list<array{type: string, path: string}>}>
     */
    protected function autoVideoReactionGroups(array $configuredGroups): array
    {
        $configuredTriggers = collect($configuredGroups)
            ->flatMap(fn (array $group): array => TelegramTriggerMatcher::normalizeMany($group['triggers']))
            ->all();

        return $this->autoReactionMediaFiles()
            ->map(fn (array $media): array => [
                'triggers' => $this->autoVideoTriggers($media['path']),
                'reactions' => [[
                    'type' => $media['type'],
                    'path' => $media['path'],
                ]],
            ])
            ->reject(fn (array $group): bool => in_array(Str::lower($group['triggers'][0]), $configuredTriggers, true))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{type: string, path: string}>
     */
    protected function autoReactionMediaFiles(): Collection
    {
        return collect(self::AutoReactionMediaTypes)
            ->flatMap(
                fn (array $mediaTypes, string $directory): array => collect($mediaTypes)
                    ->pipe(fn (Collection $types): Collection => collect(File::allFiles(resource_path($directory)))
                        ->filter(fn (SplFileInfo $file): bool => $types->has($file->getExtension()))
                        ->map(fn (SplFileInfo $file): array => [
                            'type' => $types->get($file->getExtension()),
                            'path' => Str::of($file->getPathname())
                                ->after(resource_path().DIRECTORY_SEPARATOR)
                                ->replace(DIRECTORY_SEPARATOR, '/')
                                ->toString(),
                        ]))
                    ->all(),
            )
            ->values();
    }

    /**
     * @return list<string>
     */
    protected function autoVideoTriggers(string $path): array
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        return collect([$filename, str_replace('-', ' ', $filename)])
            ->merge(
                collect(explode('-', $filename))
                    ->map(fn (string $part): string => trim($part))
                    ->filter(fn (string $part): bool => Str::length($part) > 6),
            )
            ->unique()
            ->values()
            ->all();
    }
}
