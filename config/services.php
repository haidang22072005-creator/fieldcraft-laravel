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

    'ghn' => [
        'base_url' => env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => env('GHN_SHOP_ID'),
        'verify_ssl' => filter_var(env('GHN_VERIFY_SSL', false), FILTER_VALIDATE_BOOL),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
        'default_weight' => (int) env('GHN_DEFAULT_WEIGHT', 200),
        'timeout' => (int) env('GHN_TIMEOUT', 15),
        'service_type_id' => (int) env('GHN_SERVICE_TYPE_ID', 2),
        'required_note' => env('GHN_REQUIRED_NOTE', 'KHONGCHOXEMHANG'),
    ],

];
