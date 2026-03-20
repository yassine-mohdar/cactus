<?php

return [
    'active' => env('ADMIN_THEME', 'nino-v1'),

    'themes' => [
        'nino-v1' => [
            'label' => 'Nino v1',
            'view_path' => 'themes/nino-v1',
            'vite' => [
                'resources/themes/nino-v1/app.css',
                'resources/js/app.js',
            ],
        ],
        'nino-v2' => [
            'label' => 'Nino v2',
            'view_path' => 'themes/nino-v2',
            'vite' => [
                'resources/themes/nino-v2/app.css',
                'resources/js/app.js',
            ],
        ],
    ],
];
