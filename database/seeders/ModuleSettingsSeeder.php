<?php

namespace Database\Seeders;

use App\Models\ModuleSettings;
use Illuminate\Database\Seeder;

class ModuleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'module_key' => 'supercache',
                'module_name' => 'Super Cache',
                'description' => 'Advanced caching system for improved performance',
                'is_enabled' => env('MODULE_SUPERCACHE_ENABLED', true),
                'auto_register' => true,
                'provider_class' => 'App\Modules\SuperCache\Providers\SuperCacheServiceProvider',
                'settings' => [
                    'cache_driver' => 'redis',
                    'cache_ttl' => 3600,
                    'cache_prefix' => 'supercache_',
                ],
                'dependencies' => [],
                'version' => '1.0.0',
                'author' => 'Pencilbox Team',
                'is_core' => true,
                'sort_order' => 1,
            ],
            [
                'module_key' => 'payment_gateway',
                'module_name' => 'Payment Gateway',
                'description' => 'Payment processing and gateway management system',
                'is_enabled' => env('PAYMENT_GATEWAY_MODULE_ENABLED', true),
                'auto_register' => env('PAYMENT_GATEWAY_MODULE_AUTO_REGISTER', true),
                'provider_class' => 'App\Modules\PaymentGateway\Providers\PaymentGatewayModuleServiceProvider',
                'settings' => [
                    'default_gateway' => 'sslcommerz',
                    'test_mode' => env('PAYMENT_TEST_MODE', true),
                    'currency' => 'BDT',
                ],
                'dependencies' => [],
                'version' => '1.0.0',
                'author' => 'Pencilbox Team',
                'is_core' => true,
                'sort_order' => 2,
            ],
            [
                'module_key' => 'coupon',
                'module_name' => 'Coupon System',
                'description' => 'Coupon and discount management system',
                'is_enabled' => env('COUPON_MODULE_ENABLED', true),
                'auto_register' => env('COUPON_MODULE_AUTO_REGISTER', true),
                'provider_class' => null, // Convention-based loading
                'settings' => [
                    'max_usage_per_coupon' => 100,
                    'coupon_expiry_days' => 30,
                    'auto_generate_codes' => true,
                ],
                'dependencies' => ['payment_gateway'],
                'version' => '1.0.0',
                'author' => 'Pencilbox Team',
                'is_core' => false,
                'sort_order' => 3,
            ],
            [
                'module_key' => 'cart',
                'module_name' => 'Shopping Cart',
                'description' => 'Shopping cart and checkout management system',
                'is_enabled' => env('CART_MODULE_ENABLED', true),
                'auto_register' => env('CART_MODULE_AUTO_REGISTER', true),
                'provider_class' => 'App\Modules\Cart\Providers\CartModuleServiceProvider',
                'settings' => [
                    'session_timeout' => 7200, // 2 hours
                    'max_items' => 50,
                    'auto_save' => true,
                ],
                'dependencies' => ['payment_gateway'],
                'version' => '1.0.0',
                'author' => 'Pencilbox Team',
                'is_core' => false,
                'sort_order' => 4,
            ],
            [
                'module_key' => 'healthmonitor',
                'module_name' => 'Health Monitor',
                'description' => 'System health monitoring and error tracking',
                'is_enabled' => env('HEALTH_MONITOR_MODULE_ENABLED', true),
                'auto_register' => env('HEALTH_MONITOR_MODULE_AUTO_REGISTER', true),
                'provider_class' => 'App\Modules\HealthMonitor\Providers\HealthMonitorServiceProvider',
                'settings' => [
                    'monitor_errors' => true,
                    'monitor_performance' => true,
                    'alert_threshold' => 5,
                    'notification_channels' => ['discord', 'telegram'],
                ],
                'dependencies' => [],
                'version' => '1.0.0',
                'author' => 'Pencilbox Team',
                'is_core' => false,
                'sort_order' => 5,
            ],
        ];

        foreach ($modules as $moduleData) {
            ModuleSettings::updateOrCreate(
                ['module_key' => $moduleData['module_key']],
                $moduleData
            );
        }

        $this->command->info('Module settings seeded successfully!');
    }
}
