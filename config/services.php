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
        'shop_id' => env('GHN_SHOP_ID') ?: 219637,
        'verify_ssl' => filter_var(env('GHN_VERIFY_SSL', false), FILTER_VALIDATE_BOOL),
        'from_name' => env('GHN_FROM_NAME'),
        'from_phone' => env('GHN_FROM_PHONE'),
        'from_address' => env('GHN_FROM_ADDRESS'),
        'from_province_name' => env('GHN_FROM_PROVINCE_NAME', 'Hà Nội'),
        'from_district_name' => env('GHN_FROM_DISTRICT_NAME', 'Quận Nam Từ Liêm'),
        'from_ward_name' => env('GHN_FROM_WARD_NAME', 'Phường Mỹ Đình 1'),
        'from_district_id' => (int) env('GHN_FROM_DISTRICT_ID', 3440),
        'from_ward_code' => env('GHN_FROM_WARD_CODE', '13004'),
        'default_weight' => (int) env('GHN_DEFAULT_WEIGHT', 200),
        'timeout' => (int) env('GHN_TIMEOUT', 15),
        'service_type_id' => (int) env('GHN_SERVICE_TYPE_ID', 2),
        'required_note' => env('GHN_REQUIRED_NOTE', 'KHONGCHOXEMHANG'),
    ],

    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'redirect_url' => env('MOMO_REDIRECT_URL'),
        'ipn_url' => env('MOMO_IPN_URL'),
        'request_type' => env('MOMO_REQUEST_TYPE', 'payWithCC'),
        'simulator_enabled' => filter_var(env('MOMO_SIMULATOR_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],

    'payos' => [
        'base_url' => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),
        'client_id' => env('PAYOS_CLIENT_ID'),
        'api_key' => env('PAYOS_API_KEY'),
        'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
        'return_url' => env('PAYOS_RETURN_URL'),
        'cancel_url' => env('PAYOS_CANCEL_URL'),
    ],

];
