<?php

return [
    'default_disk' => env('MEDIA_DEFAULT_DISK', 'public'),

    'directories' => [
        'avatars' => env('MEDIA_AVATAR_DIRECTORY', 'avatars'),
        'catalog' => env('MEDIA_CATALOG_DIRECTORY', 'categories'),
    ],

    'processing' => [
        'enabled' => (bool) env('MEDIA_PROCESSING_ENABLED', true),
        'queue' => env('MEDIA_PROCESSING_QUEUE', env('PERF_MEDIA_QUEUE', 'media')),
    ],
];
