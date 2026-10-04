<?php

return [
    'default' => env('SMS_DRIVER', 'arkesel'),

    'drivers' => [
        'arkesel' => [
            'api_key' => env('ARKESEL_API_KEY'),
            'sender'  => env('ARKESEL_SENDER'),
        ],
        'mnotify' => [
            'api_key' => env('MNOTIFY_API_KEY'),
            'sender'  => env('MNOTIFY_SENDER'),
        ],
        // 'hubtel'  => [...],   // coming soon
    ],
];
