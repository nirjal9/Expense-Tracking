<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gmail' => [
        'credentials_path' => env('GMAIL_CREDENTIALS_PATH', storage_path('app/gmail-credentials.json')),
        'token_path' => env('GMAIL_TOKEN_PATH', storage_path('app/gmail-token.json')),
        'scopes' => [
            'https://www.googleapis.com/auth/gmail.readonly',
        ],
    ],

    'payment_notifications' => [
        'webhook_secret' => env('PAYMENT_NOTIFICATIONS_WEBHOOK_SECRET'),
        'auto_approve_threshold' => env('PAYMENT_NOTIFICATIONS_AUTO_APPROVE_THRESHOLD', 0.9),
        'duplicate_check_window' => env('PAYMENT_NOTIFICATIONS_DUPLICATE_CHECK_WINDOW', 24), // hours
        'max_emails_per_batch' => env('PAYMENT_NOTIFICATIONS_MAX_EMAILS_PER_BATCH', 10),
        'enable_learning' => env('PAYMENT_NOTIFICATIONS_ENABLE_LEARNING', true),
    ],

];