<?php

return [
    'default' => env('FIREBASE_PROJECT', 'app'),

    'projects' => [
        'app' => [
            /*
             * Path ke Firebase Service Account JSON.
             * Download dari Firebase Console → Project Settings → Service Accounts → Generate new private key.
             * Upload JSON ke server lalu set env: FIREBASE_CREDENTIALS=/home/dszgofcr/ipl-backend/storage/app/firebase-credentials.json
             */
            'credentials' => env('FIREBASE_CREDENTIALS'),

            'project_id' => env('FIREBASE_PROJECT_ID'),

            'logging' => [
                'http_log_channel' => null,
                'http_debug_log_channel' => null,
            ],

            'http_client_options' => [
                'proxy' => null,
                // Timeout pendek supaya request tidak hang lama kalau Firebase lambat
                // (penting karena FCM dipanggil dari API request yang user tunggu).
                'timeout' => 5.0,
                'connect_timeout' => 3.0,
                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
