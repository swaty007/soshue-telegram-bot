<?php

namespace Database\Factories;

use App\Enums\TelegramChatSummaryStatus;
use App\Models\TelegramChat;
use App\Models\TelegramChatSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramChatSummary>
 */
class TelegramChatSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodEndedAt = now();

        return [
            'telegram_chat_id' => TelegramChat::factory(),
            'period_started_at' => $periodEndedAt->copy()->subDay(),
            'period_ended_at' => $periodEndedAt,
            'message_count' => fake()->numberBetween(30, 1000),
            'prompt_fingerprint' => fake()->sha256(),
            'summary' => null,
            'status' => TelegramChatSummaryStatus::Pending,
            'error' => null,
        ];
    }

    /**
     * Indicate that the summary has completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => fake()->paragraph(),
            'status' => TelegramChatSummaryStatus::Completed,
            'error' => null,
        ]);
    }

    /**
     * Indicate that the summary has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => null,
            'status' => TelegramChatSummaryStatus::Failed,
            'error' => fake()->sentence(),
        ]);
    }
}
