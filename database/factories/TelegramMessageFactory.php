<?php

namespace Database\Factories;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramMessage>
 */
class TelegramMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sentAt = fake()->dateTimeBetween('-1 day');
        $text = fake()->sentence();

        return [
            'telegram_chat_id' => TelegramChat::factory(),
            'telegram_user_id' => TelegramUser::factory(),
            'telegram_message_id' => fake()->unique()->randomNumber(7),
            'text' => $text,
            'payload' => [
                'message_id' => fake()->unique()->randomNumber(7),
                'date' => $sentAt->getTimestamp(),
                'text' => $text,
            ],
            'sent_at' => $sentAt,
        ];
    }
}
