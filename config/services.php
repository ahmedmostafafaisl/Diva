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
    'firebase' => [
        'credentials' => '/serviceAccount.json', // Or use the path from .env
    ],

    'wp' => [
        // old procontent API (single-product-v2, etc.)
        'base' => env('WP_API_BASE', ''),

        // new diva-commerce API (products listing)
        'commerce_base' => env('WP_COMMERCE_API_BASE', 'https://diva.sa/wp-json/diva-commerce/v1'),
        'commerce_category_param' => env('WP_COMMERCE_CATEGORY_PARAM', 'category'),
        'commerce_cache_ttl' => (int) env('WP_COMMERCE_CACHE_TTL', 300),
    ],
    'tabby' => [
        'base_url' => env('TABBY_BASE_URL', 'https://api.tabby.ai/api/v2/'),
        'secret_key' => env('TABBY_SECRET_KEY'),
        // Base URL Tabby redirects to: {base}cancel, {base}failure, {base}success
        'merchant_urls_base' => env('TABBY_MERCHANT_URLS_BASE'),
    ],
    'payments' => [
        // Shown to Tabby / Tamara as the merchant platform name
        'platform' => env('PAYMENT_PLATFORM_NAME', 'Diva'),
    ],
    'tamara' => [
        'base_url' => env('TAMARA_BASE_URL'),
        'token' => env('TAMARA_TOKEN'),
    ],
];
