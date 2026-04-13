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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paddle_ocr' => [
        'python' => env(
            'PADDLE_OCR_PYTHON',
            PHP_OS_FAMILY === 'Windows'
                ? base_path('.venv\\Scripts\\python.exe')
                : base_path('.venv/bin/python')
        ),
        'entrypoint' => env('PADDLE_OCR_ENTRYPOINT', base_path('ocr_paddle_module/blank_sheet_ocr/cli.py')),
        'timeout' => env('PADDLE_OCR_TIMEOUT', 60),
        'fill_threshold' => env('PADDLE_OCR_FILL_THRESHOLD', 0.38),
        'uncertain_margin' => env('PADDLE_OCR_UNCERTAIN_MARGIN', 0.06),
    ],

];
