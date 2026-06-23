<?php

return [
    'ai' => [
        'model' => env('TELEGRAM_AI_MODEL', 'qwen/qwen3.6-27b'),
        'model_fast' => env('TELEGRAM_AI_MODEL', 'qwen/qwen3.6-35b-a3b'),
        'queue' => env('TELEGRAM_AI_QUEUE', 'long_running'),
    ],

    'summary' => [
        'threshold_min' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MIN', 500),
        'threshold_max' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MAX', 1500),
        'daily_window_hours' => (int) env('TELEGRAM_SUMMARY_DAILY_WINDOW_HOURS', 24),
        'recent_messages_limit' => (int) env('TELEGRAM_RECENT_MESSAGES_LIMIT', 30),
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
