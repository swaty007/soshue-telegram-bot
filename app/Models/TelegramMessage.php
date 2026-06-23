<?php

namespace App\Models;

use App\Casts\AsTelegramMessagePayload;
use Database\Factories\TelegramMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

/**
 * @property int $id
 * @property int $telegram_chat_id
 * @property int|null $telegram_user_id
 * @property int $telegram_message_id
 * @property int|null $messages_count
 * @property string|null $text
 * @property Message $payload
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TelegramChat $chat
 * @property-read TelegramUser|null $user
 */
#[Fillable([
    'telegram_chat_id',
    'telegram_user_id',
    'telegram_message_id',
    'text',
    'payload',
    'sent_at',
])]
class TelegramMessage extends Model
{
    /** @use HasFactory<TelegramMessageFactory> */
    use HasFactory;

    /**
     * Get the Telegram chat that owns this message.
     *
     * @return BelongsTo<TelegramChat, $this>
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegramChat::class, 'telegram_chat_id');
    }

    /**
     * Get the Telegram user that sent this message.
     *
     * @return BelongsTo<TelegramUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    /**
     * Scope messages for a chat ordered newest first.
     *
     * @param  Builder<TelegramMessage>  $query
     * @return Builder<TelegramMessage>
     */
    public function scopeRecentForChat(Builder $query, TelegramChat $chat, int $limit = 30): Builder
    {
        return $query
            ->whereBelongsTo($chat, 'chat')
            ->latest('sent_at')
            ->limit($limit);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_chat_id' => 'integer',
            'telegram_user_id' => 'integer',
            'telegram_message_id' => 'integer',
            'payload' => AsTelegramMessagePayload::class,
            'sent_at' => 'datetime',
        ];
    }
}
