<?php

return [
    'summary' => [
        'threshold_min' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MIN', 500),
        'threshold_max' => (int) env('TELEGRAM_SUMMARY_MESSAGE_THRESHOLD_MAX', 1500),
        'daily_window_hours' => (int) env('TELEGRAM_SUMMARY_DAILY_WINDOW_HOURS', 24),
        'recent_messages_limit' => (int) env('TELEGRAM_RECENT_MESSAGES_LIMIT', 50),
    ],

    'questions' => [
        'enabled' => (bool) env('TELEGRAM_QUESTION_ANSWERS_ENABLED', true),
        'triggers' => [
            'почему',
            'а как',
            'бот',
            'bot',
            'понедельник',
            'monday',
        ],
    ],

    'quick_reactions' => [
        'php' => [
            'Опять PHP. Ну хоть не руками XML парсите, уже прогресс.',
            'PHP замечен. Кто-то снова выбрал страдание, но с автокомплитом.',
        ],
        'laravel' => [
            'Laravel на месте. Теперь попробуйте не превратить это в god controller.',
        ],
        'срочно' => [
            'Если это срочно, почему звучит так, будто вы знали об этом вчера?',
        ],
    ],
];
