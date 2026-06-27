<?php

namespace App\Telegram\Support;

use Nutgram\Laravel\Facades\Telegram;
use RuntimeException;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Message\ReplyParameters;

class QuickReactionSender
{
    public const string Text = 'text';

    public const string Photo = 'photo';

    public const string Video = 'video';

    public const string Audio = 'audio';

    /**
     * @param  array{type: string, text?: string, path?: string, caption?: string|null}  $reaction
     */
    public function send(array $reaction, int $chatId, int $replyToMessageId): void
    {
        $replyParameters = ReplyParameters::make($replyToMessageId);

        match ($reaction['type']) {
            self::Text => Telegram::sendMessage($reaction['text'] ?? '', $chatId, reply_parameters: $replyParameters),
            self::Photo, self::Video, self::Audio => $this->sendMedia($reaction, $chatId, $replyParameters),
            default => null,
        };
    }

    /**
     * @param  array{type: string, path?: string, caption?: string|null}  $reaction
     */
    protected function sendMedia(array $reaction, int $chatId, ReplyParameters $replyParameters): void
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
                : $this->sendMediaWithoutCaption($reaction['type'], $inputFile, $chatId, $replyParameters);
        } finally {
            fclose($file);
        }
    }

    protected function sendMediaWithoutCaption(string $type, InputFile $file, int $chatId, ReplyParameters $replyParameters): void
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
