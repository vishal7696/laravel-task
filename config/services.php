<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shopify
    |--------------------------------------------------------------------------
    | Credentials for the Shopify Admin API (both REST and GraphQL use the
    | same store domain + access token, only the endpoint differs).
    */
    'shopify' => [
        'store_domain' => env('SHOPIFY_STORE_DOMAIN'),
        'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
        'api_version' => env('SHOPIFY_API_VERSION', '2025-01'),
        // "rest" or "graphql" - lets you switch integration style without code changes
        'api_mode' => env('SHOPIFY_API_MODE', 'rest'),
    ],
];
