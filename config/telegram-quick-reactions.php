<?php

return [
    [
        'triggers' => ['php', 'пхп'],
        'reactions' => [
            [
                'type' => 'text',
                'text' => 'Опять PHP. Ну хоть не руками XML парсите, уже прогресс.',
            ],
            [
                'type' => 'text',
                'text' => 'PHP замечен. Кто-то снова выбрал страдание, но с автокомплитом.',
            ],
        ],
    ],

    [
        'triggers' => ['laravel'],
        'reactions' => [
            [
                'type' => 'text',
                'text' => 'Laravel на месте. Теперь попробуйте не превратить это в god controller.',
            ],
        ],
    ],

    [
        'triggers' => ['срочно'],
        'reactions' => [
            [
                'type' => 'text',
                'text' => 'Если это срочно, почему звучит так, будто вы знали об этом вчера?',
            ],
        ],
    ],

    [
        'triggers' => ['WOT', 'world of tanks', 'танки'],
        'reactions' => [
            [
                'type' => 'text',
                'text' => 'World of Tanks в чате. Значит, стратегическое планирование снова проиграло арте.',
            ],
            [
                'type' => 'video',
                'path' => 'images/gif/WOT.mp4',
            ],
        ],
    ],
];
