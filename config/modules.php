<?php

return [
    'enabled' => env('MODULES_ENABLED', true),

    // Use database for module management
    'use_database' => env('MODULES_USE_DATABASE', true),

    'modules' => [
        // Example module configuration:
        // 'example_module' => [
        //     'enabled' => env('MODULE_EXAMPLE_ENABLED', false),
        //     'auto_register' => true,
        //     'provider' => \App\Modules\Example\Providers\ExampleServiceProvider::class,
        //     'description' => 'Example module description',
        //     'version' => '1.0.0',
        //     'author' => 'Your Name',
        //     'is_core' => false,
        //     'sort_order' => 0,
        //     'settings' => [],
        //     'dependencies' => [],
        // ],
    ],

    // Database-driven module configuration
    'database_modules' => [],

    // Cache settings for module management
    'cache' => [
        'enabled' => env('MODULE_CACHE_ENABLED', true),
        'ttl' => env('MODULE_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('MODULE_CACHE_PREFIX', 'module_settings'),
    ],

    // Module management settings
    'management' => [
        'allow_install' => env('MODULE_ALLOW_INSTALL', true),
        'allow_uninstall' => env('MODULE_ALLOW_UNINSTALL', true),
        'allow_core_disable' => env('MODULE_ALLOW_CORE_DISABLE', false),
        'auto_sync_config' => env('MODULE_AUTO_SYNC_CONFIG', true),
    ],
];
