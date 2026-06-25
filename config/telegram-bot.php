<?php

return [
    'ai' => [
        'model' => env('TELEGRAM_AI_MODEL', 'qwen/qwen3.6-27b'),
        'model_fast' => env('TELEGRAM_AI_MODEL_FAST', 'qwen/qwen3.6-35b-a3b'),
        'queue' => env('TELEGRAM_AI_QUEUE', 'long_running'),
    ],

    'summary' => [
        'threshold_min' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MIN', 400),
        'threshold_max' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MAX', 5000),
        'daily_window_hours' => (int) env('TELEGRAM_SUMMARY_DAILY_WINDOW_HOURS', 48),
        'recent_messages_limit' => (int) env('TELEGRAM_RECENT_MESSAGES_LIMIT', 40),
    ],

    'dice' => [
        'decay_seconds' => (int) env('TELEGRAM_DICE_DECAY_SECONDS', 60),
    ],

    'questions' => [
        'enabled' => (bool) env('TELEGRAM_QUESTION_ANSWERS_ENABLED', true),
        'triggers' => [
            'почему',
            'пидор',
            'а как',
            'бот',
            'bot',
            'понедельник',
        ],
    ],
];
