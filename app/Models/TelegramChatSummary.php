<?php

namespace App\Models;

use App\Enums\TelegramChatSummaryStatus;
use Database\Factories\TelegramChatSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $telegram_chat_id
 * @property Carbon $period_started_at
 * @property Carbon $period_ended_at
 * @property int $message_count
 * @property string $prompt_fingerprint
 * @property string|null $summary
 * @property TelegramChatSummaryStatus $status
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TelegramChat $chat
 */
#[Fillable([
    'telegram_chat_id',
    'period_started_at',
    'period_ended_at',
    'message_count',
    'prompt_fingerprint',
    'summary',
    'status',
    'error',
])]
class TelegramChatSummary extends Model
{
    /** @use HasFactory<TelegramChatSummaryFactory> */
    use HasFactory;

    /**
     * Get the Telegram chat this summary belongs to.
     *
     * @return BelongsTo<TelegramChat, $this>
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegramChat::class, 'telegram_chat_id');
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
            'period_started_at' => 'datetime',
            'period_ended_at' => 'datetime',
            'message_count' => 'integer',
            'status' => TelegramChatSummaryStatus::class,
        ];
    }
}
