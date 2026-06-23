<?php

use App\Services\QuickReactionService;

return [
    [
        'triggers' => ['php', 'пхп'],
        'reactions' => [
            [
                'type' => QuickReactionService::Text,
                'text' => 'Опять PHP. Ну хоть не руками XML парсите, уже прогресс.',
            ],
            [
                'type' => QuickReactionService::Text,
                'text' => 'PHP замечен. Кто-то снова выбрал страдание, но с автокомплитом.',
            ],
        ],
    ],

    [
        'triggers' => ['laravel'],
        'reactions' => [
            [
                'type' => QuickReactionService::Text,
                'text' => 'Laravel на месте. Теперь попробуйте не превратить это в god controller.',
            ],
        ],
    ],

    [
        'triggers' => ['срочно'],
        'reactions' => [
            [
                'type' => QuickReactionService::Text,
                'text' => 'Если это срочно, почему звучит так, будто вы знали об этом вчера?',
            ],
        ],
    ],
    [
        'triggers' => ['WOT', 'world of tanks', 'танки'],
        'reactions' => [
            [
                'type' => QuickReactionService::Text,
                'text' => 'World of Tanks в чате. Значит, стратегическое планирование снова проиграло арте.',
            ],
            [
                'type' => QuickReactionService::Video,
                'path' => 'images/gif/to-the-club-buddy-gachi.mp4',
            ],
            [
                'type' => QuickReactionService::Video,
                'path' => 'images/gif/WOT.mp4',
            ],
        ],
    ],
    [
        'triggers' => ['50 лоукиков', '50 lowkick', 'зачем ты врешь', 'помошник хуесоса', '50 лоу кик'],
        'reactions' => [
            [
                'type' => QuickReactionService::Audio,
                'path' => 'audio/50-lowkick-eng.mp3',
            ],
            [
                'type' => QuickReactionService::Audio,
                'path' => 'audio/зачемтыврешь.ogg',
            ],
            [
                'type' => QuickReactionService::Audio,
                'path' => 'audio/50-лоукиков.ogg',
            ],
        ],
    ],
];
