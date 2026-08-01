<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Dedicated channel for Shopify import events (bonus requirement:
        // "Log all import events" + used by in-dashboard log viewer)
        'shopify_import' => [
            'driver' => 'single',
            'path' => storage_path('logs/shopify_import.log'),
            'level' => 'debug',
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],
];
