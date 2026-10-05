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
        'hubtel' => [
            'client_id'     => env('HUBTEL_CLIENT_ID'),
            'client_secret' => env('HUBTEL_CLIENT_SECRET'),
            'sender'        => env('HUBTEL_SENDER'),
            // 'base_url'   => env('HUBTEL_BASE_URL'), // override if Hubtel changes the host
        ],
    ],
];
