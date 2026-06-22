<?php

namespace Database\Factories;

use App\Models\TelegramChat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramChat>
 */
class TelegramChatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->randomNumber(9),
            'type' => 'supergroup',
            'title' => fake()->company().' chat',
            'username' => fake()->optional()->userName(),
            'summaries_enabled' => true,
            'reactions_enabled' => true,
            'last_summary_at' => null,
        ];
    }

    /**
     * Indicate that summaries are disabled for the chat.
     */
    public function withoutSummaries(): static
    {
        return $this->state(fn (array $attributes) => [
            'summaries_enabled' => false,
        ]);
    }

    /**
     * Indicate that quick reactions are disabled for the chat.
     */
    public function withoutReactions(): static
    {
        return $this->state(fn (array $attributes) => [
            'reactions_enabled' => false,
        ]);
    }
}
