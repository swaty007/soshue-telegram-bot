<?php

use App\Telegram\Support\QuickReactionSender;

return [
    [
        'triggers' => ['php', 'пхп'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Text,
                'text' => 'Опять PHP. Ну хоть не руками XML парсите, уже прогресс.',
            ],
            [
                'type' => QuickReactionSender::Text,
                'text' => 'PHP замечен. Кто-то снова выбрал страдание, но с автокомплитом.',
            ],
        ],
    ],

    [
        'triggers' => ['laravel'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Text,
                'text' => 'Laravel на месте. Теперь попробуйте не превратить это в god controller.',
            ],
        ],
    ],

    [
        'triggers' => ['срочно'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Text,
                'text' => 'Если это срочно, почему звучит так, будто вы знали об этом вчера?',
            ],
        ],
    ],
    [
        'triggers' => ['WOT', 'world of tanks', 'танки'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Text,
                'text' => 'World of Tanks в чате. Значит, стратегическое планирование снова проиграло арте.',
            ],
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/to-the-club-buddy-gachi.mp4',
            ],
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/WOT.mp4',
            ],
        ],
    ],
    [
        'triggers' => ['50 лоукик', '50 lowkick', 'зачем ты врешь', 'помошник хуесоса', '50 лоу кик'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Audio,
                'path' => 'audio/50-lowkick-eng.mp3',
            ],
            [
                'type' => QuickReactionSender::Audio,
                'path' => 'audio/зачемтыврешь.ogg',
            ],
            [
                'type' => QuickReactionSender::Audio,
                'path' => 'audio/50-лоукиков.ogg',
            ],
        ],
    ],
    [
        'triggers' => ['марио кард', 'луинджи'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Text,
                'text' => 'https://www.youtube.com/watch?v=iO0ZXo3ORqc',
            ],
            [
                'type' => QuickReactionSender::Text,
                'text' => 'https://www.youtube.com/watch?v=fshUDHY1Ycs',
            ],
            [
                'type' => QuickReactionSender::Text,
                'text' => 'https://www.youtube.com/watch?v=_A6LTWTMJOQ',
            ],
            [
                'type' => QuickReactionSender::Photo,
                'path' => 'images/photo/марио-кард-зачем.jpg',
            ],
        ],
    ],
    [
        'triggers' => ['300 бакс', '300$', '300 доллар', 'триста баксов', 'триста доллар'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/300-баксов.mp4',
            ],
        ],
    ],
    [
        'triggers' => [
            'я ей верю', 'я ей доверяю', 'я ей доверюсь',
            'она бы так не сделала', 'она бы так не поступила', 'она бы так не поступит',
            'я ему доверяю',
        ],
        'reactions' => [
            [
                'type' => QuickReactionSender::Photo,
                'path' => 'images/photo/не-может-она.webp',
            ],
            [
                'type' => QuickReactionSender::Photo,
                'path' => 'images/photo/я-ему-верю.webp',
            ],
        ],
    ],
    [
        'triggers' => ['балистика летит', 'воздушная тревога', 'повітряна тривога', 'шахед летит'],
        'reactions' => [
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/300-баксов.mp4',
            ],
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/good-morning-vietnam.mp4',
            ],
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/воздушная-тревога.mp4',
            ],
            [
                'type' => QuickReactionSender::Video,
                'path' => 'images/gif/тривога-повітряна.mp4',
            ],
            [
                'type' => QuickReactionSender::Photo,
                'path' => 'images/photo/балистика.webp',
            ],
        ],
    ],
];
