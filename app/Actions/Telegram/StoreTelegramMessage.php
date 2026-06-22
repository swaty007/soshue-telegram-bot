<?php

namespace App\Actions\Telegram;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use BackedEnum;
use Carbon\CarbonImmutable;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\Message\Message;
use SergiX44\Nutgram\Telegram\Types\User\User;

class StoreTelegramMessage
{
    /**
     * Persist the current Nutgram message and its chat/user context.
     */
    public function handle(Nutgram $bot): ?TelegramMessage
    {
        $message = $bot->message();

        if (! $message instanceof Message) {
            return null;
        }

        $telegramChat = $this->storeChat($message->chat);
        $telegramUser = $message->from instanceof User
            ? $this->storeUser($message->from)
            : null;

        return TelegramMessage::updateOrCreate(
            [
                'telegram_chat_id' => $telegramChat->id,
                'telegram_message_id' => $message->message_id,
            ],
            [
                'telegram_user_id' => $telegramUser?->id,
                'text' => $message->text ?? $message->caption ?? null,
                'payload' => $message->toArray(),
                'sent_at' => CarbonImmutable::createFromTimestamp($message->date),
            ],
        );
    }

    protected function storeChat(Chat $chat): TelegramChat
    {
        return TelegramChat::updateOrCreate(
            ['telegram_id' => $chat->id],
            [
                'type' => $chat->type instanceof BackedEnum ? $chat->type->value : (string) $chat->type,
                'title' => $chat->title,
                'username' => $chat->username,
            ],
        );
    }

    protected function storeUser(User $user): TelegramUser
    {
        return TelegramUser::updateOrCreate(
            ['telegram_id' => $user->id],
            [
                'is_bot' => $user->is_bot,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'language_code' => $user->language_code,
            ],
        );
    }
}
