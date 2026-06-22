<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramUser>
 */
class TelegramUserFactory extends Factory
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
            'is_bot' => false,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->optional()->userName(),
            'language_code' => 'en',
        ];
    }

    /**
     * Indicate that the Telegram user is a bot.
     */
    public function bot(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bot' => true,
        ]);
    }
}
