<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

/**
 * @implements CastsAttributes<Message, Message|array<string, mixed>>
 */
class AsTelegramMessagePayload implements CastsAttributes, SerializesCastableAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the stored Telegram payload to a Nutgram message.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws JsonException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Message
    {
        return Message::fromArray($this->payloadArray($value));
    }

    /**
     * Prepare a Nutgram message or payload array for storage.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof Message) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Telegram message payload must be a Nutgram message or an array.');
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Get the serialized representation of the cast value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value instanceof Message) {
            return $value->toArray();
        }

        return $this->payloadArray($attributes[$key] ?? $value);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    protected function payloadArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Telegram message payload must decode to an array.');
        }

        return $decoded;
    }
}
