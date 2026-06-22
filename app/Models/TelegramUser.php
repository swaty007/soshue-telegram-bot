<?php

namespace App\Models;

use Database\Factories\TelegramUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $telegram_id
 * @property bool $is_bot
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property string|null $language_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TelegramMessage> $messages
 */
#[Fillable([
    'telegram_id',
    'is_bot',
    'first_name',
    'last_name',
    'username',
    'language_code',
])]
class TelegramUser extends Model
{
    /** @use HasFactory<TelegramUserFactory> */
    use HasFactory;

    /**
     * Get all messages sent by this Telegram user.
     *
     * @return HasMany<TelegramMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class);
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
            'is_bot' => 'boolean',
        ];
    }
}
