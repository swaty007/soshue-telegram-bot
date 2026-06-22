<?php

namespace App\Models;

use Database\Factories\TelegramChatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $telegram_id
 * @property string $type
 * @property string|null $title
 * @property string|null $username
 * @property bool $summaries_enabled
 * @property bool $reactions_enabled
 * @property Carbon|null $last_summary_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TelegramMessage> $messages
 * @property-read Collection<int, TelegramChatSummary> $summaries
 */
#[Fillable([
    'telegram_id',
    'type',
    'title',
    'username',
    'summaries_enabled',
    'reactions_enabled',
    'last_summary_at',
])]
class TelegramChat extends Model
{
    /** @use HasFactory<TelegramChatFactory> */
    use HasFactory;

    /**
     * Get all messages for this Telegram chat.
     *
     * @return HasMany<TelegramMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class);
    }

    /**
     * Get all AI summaries generated for this Telegram chat.
     *
     * @return HasMany<TelegramChatSummary, $this>
     */
    public function summaries(): HasMany
    {
        return $this->hasMany(TelegramChatSummary::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'summaries_enabled' => 'boolean',
            'reactions_enabled' => 'boolean',
            'last_summary_at' => 'datetime',
        ];
    }
}
