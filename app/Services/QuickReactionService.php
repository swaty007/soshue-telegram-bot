<?php

namespace App\Services;

use App\Models\TelegramMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Nutgram\Laravel\Facades\Telegram;
use RuntimeException;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Message\ReplyParameters;
use Symfony\Component\Finder\SplFileInfo;

class QuickReactionService
{
    public const string Text = 'text';

    public const string Photo = 'photo';

    public const string Video = 'video';

    public const string Audio = 'audio';

    private const string AutoVideoDirectory = 'images';

    private const string AutoAudioDirectory = 'audio';

    /**
     * @var array<string, array<string, string>>
     */
    private const array AutoReactionMediaTypes = [
        self::AutoVideoDirectory => [
            'mp4' => self::Video,
            'webp' => self::Photo,
            'png' => self::Photo,
            'gif' => self::Photo,
        ],
        self::AutoAudioDirectory => [
            'mp3' => self::Audio,
            'ogg' => self::Audio,
        ],
    ];

    public function sendForMessage(TelegramMessage $message): void
    {
        $reaction = $this->findReaction($message->text);

        if (empty($reaction)) {
            return;
        }

        RateLimiter::attempt(
            $this->rateLimitKey($message->chat->telegram_id, $reaction),
            maxAttempts: 1,
            callback: fn () => $this->sendReaction(
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

        $normalizedText = Str::lower($text);

        foreach ($this->reactionGroups() as $group) {
            if (! Str::contains($normalizedText, $this->normalizedTriggers($group['triggers']))) {
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
            ->flatMap(fn (array $group): array => $this->normalizedTriggers($group['triggers']))
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

    /**
     * @param  list<string>  $triggers
     * @return list<string>
     */
    protected function normalizedTriggers(array $triggers): array
    {
        return collect($triggers)
            ->map(fn (string $trigger): string => trim($trigger))
            ->filter()
            ->map(fn (string $trigger): string => Str::lower($trigger))
            ->values()
            ->all();
    }

    /**
     * @param  array{type: string, text?: string, path?: string, caption?: string|null}  $reaction
     */
    protected function sendReaction(array $reaction, int $chatId, int $replyToMessageId): void
    {
        $replyParameters = ReplyParameters::make($replyToMessageId);

        match ($reaction['type']) {
            self::Text => Telegram::sendMessage($reaction['text'] ?? '', $chatId, reply_parameters: $replyParameters),
            self::Photo, self::Video, self::Audio => $this->sendMediaReaction($reaction, $chatId, $replyParameters),
            default => null,
        };
    }

    /**
     * @param  array{type: string, path?: string, caption?: string|null}  $reaction
     */
    protected function sendMediaReaction(array $reaction, int $chatId, ReplyParameters $replyParameters): void
    {
        $mediaPath = $reaction['path'] ?? null;

        if ($mediaPath === null) {
            return;
        }

        $path = resource_path($mediaPath);

        if (! is_file($path)) {
            report(new RuntimeException("Quick reaction media file does not exist: {$path}"));

            return;
        }

        $file = fopen($path, 'rb');

        if ($file === false) {
            report(new RuntimeException("Quick reaction media file cannot be opened: {$path}"));

            return;
        }

        $inputFile = InputFile::make($file, basename($path));
        $caption = $reaction['caption'] ?? null;

        try {
            $caption !== null && trim($caption) !== ''
                ? $this->sendMediaWithCaption($reaction['type'], $inputFile, $chatId, $replyParameters, $caption)
                : $this->sendMedia($reaction['type'], $inputFile, $chatId, $replyParameters);
        } finally {
            fclose($file);
        }
    }

    protected function sendMedia(string $type, InputFile $file, int $chatId, ReplyParameters $replyParameters): void
    {
        match ($type) {
            self::Photo => Telegram::sendPhoto($file, $chatId, reply_parameters: $replyParameters),
            self::Video => Telegram::sendVideo($file, $chatId, reply_parameters: $replyParameters),
            self::Audio => Telegram::sendAudio($file, $chatId, reply_parameters: $replyParameters),
            default => null,
        };
    }

    protected function sendMediaWithCaption(string $type, InputFile $file, int $chatId, ReplyParameters $replyParameters, string $caption): void
    {
        match ($type) {
            self::Photo => Telegram::sendPhoto($file, $chatId, caption: $caption, reply_parameters: $replyParameters),
            self::Video => Telegram::sendVideo($file, $chatId, caption: $caption, reply_parameters: $replyParameters),
            self::Audio => Telegram::sendAudio($file, $chatId, caption: $caption, reply_parameters: $replyParameters),
            default => null,
        };
    }
}
