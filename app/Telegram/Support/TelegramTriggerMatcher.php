<?php

namespace App\Telegram\Support;

use Illuminate\Support\Str;

class TelegramTriggerMatcher
{
    private const int ShortTriggerMaxLength = 5;

    private const int MinimumWordsForPartialMatch = 3;

    /**
     * @param  list<string>  $triggers
     */
    public static function matchesAny(string $text, array $triggers): bool
    {
        $normalizedText = Str::lower($text);

        foreach (self::normalizeMany($triggers) as $trigger) {
            if (self::matches($normalizedText, $trigger)) {
                return true;
            }
        }

        return false;
    }

    public static function contains(string $text, string $trigger): bool
    {
        $normalizedText = Str::lower($text);
        $normalizedTrigger = Str::lower(trim($trigger));

        if ($normalizedTrigger === '') {
            return false;
        }

        if (Str::length($normalizedTrigger) > self::ShortTriggerMaxLength) {
            return Str::contains($normalizedText, $normalizedTrigger);
        }

        return preg_match(
            '/(?<![\pL\pN])'.preg_quote($normalizedTrigger, '/').'(?![\pL\pN])/iu',
            $normalizedText,
        ) === 1;
    }

    /**
     * @param  list<string>  $triggers
     * @return list<string>
     */
    public static function normalizeMany(array $triggers): array
    {
        return collect($triggers)
            ->map(fn (string $trigger): string => trim($trigger))
            ->filter()
            ->map(fn (string $trigger): string => Str::lower($trigger))
            ->values()
            ->all();
    }

    protected static function matches(string $normalizedText, string $trigger): bool
    {
        if (self::contains($normalizedText, $trigger)) {
            return true;
        }

        $words = self::triggerWords($trigger);

        if (count($words) < 2) {
            return false;
        }

        $requiredMatches = count($words) > self::MinimumWordsForPartialMatch
            ? count($words) - 1
            : count($words);

        $matchedWords = collect($words)
            ->filter(fn (string $word): bool => self::contains($normalizedText, $word))
            ->count();

        return $matchedWords >= $requiredMatches;
    }

    /**
     * @return list<string>
     */
    protected static function triggerWords(string $trigger): array
    {
        return Str::of($trigger)
            ->replace('-', ' ')
            ->explode(' ')
            ->map(fn (string $word): string => trim($word))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
