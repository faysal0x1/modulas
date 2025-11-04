# Modulas

[![Latest Version on Packagist](https://img.shields.io/packagist/v/faysal0x1/modulas.svg?style=flat-square)](https://packagist.org/packages/faysal0x1/modulas)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/faysal0x1/modulas/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/faysal0x1/modulas/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/faysal0x1/modulas/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/faysal0x1/modulas/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/faysal0x1/modulas.svg?style=flat-square)](https://packagist.org/packages/faysal0x1/modulas)

A powerful Laravel package for managing modules with both config-file and database-driven modes. Modulas provides a flexible and extensible module management system that allows you to easily enable, disable, and configure modules in your Laravel application.

## Features

- 🎯 **Dual Mode Support**: Manage modules via config files or database
- 🔌 **Auto-Registration**: Automatically register and boot module service providers
- 📦 **Convention-Based**: Convention-based resource loading for modules without custom providers
- 🗄️ **Database Management**: Full CRUD operations for modules via database
- 🔒 **Dependency Management**: Handle module dependencies and prevent conflicts
- ⚡ **Caching**: Built-in caching for improved performance
- 🎨 **Core Modules**: Protect core modules from being disabled/uninstalled
- 📊 **Statistics**: Get detailed statistics about your modules
- 🛠️ **Artisan Commands**: Powerful CLI commands for module management

## Requirements

- PHP ^8.3
- Laravel ^11.0 || ^12.0
- Illuminate/Contracts ^11.0 || ^12.0

## Installation

You can install the package via Composer:

```bash
composer require faysal0x1/modulas
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="modulas-config"
```

This will create a `config/modules.php` file in your application.

### Publish Migrations

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="modulas-migrations"
php artisan migrate
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
MODULES_ENABLED=true
MODULES_USE_DATABASE=true
MODULE_CACHE_ENABLED=true
MODULE_CACHE_TTL=3600
MODULE_ALLOW_INSTALL=true
MODULE_ALLOW_UNINSTALL=true
MODULE_ALLOW_CORE_DISABLE=false
MODULE_AUTO_SYNC_CONFIG=true
```

### Config File Structure

The `config/modules.php` file contains the following structure:

```php
return [
    'enabled' => env('MODULES_ENABLED', true),
    'use_database' => env('MODULES_USE_DATABASE', true),
    
    'modules' => [
        'example_module' => [
            'enabled' => env('MODULE_EXAMPLE_ENABLED', false),
            'auto_register' => true,
            'provider' => \App\Modules\Example\Providers\ExampleServiceProvider::class,
            'description' => 'Example module description',
            'version' => '1.0.0',
            'author' => 'Your Name',
            'is_core' => false,
            'sort_order' => 0,
            'settings' => [],
            'dependencies' => [],
        ],
    ],
    
    'cache' => [
        'enabled' => env('MODULE_CACHE_ENABLED', true),
        'ttl' => env('MODULE_CACHE_TTL', 3600),
        'prefix' => env('MODULE_CACHE_PREFIX', 'module_settings'),
    ],
    
    'management' => [
        'allow_install' => env('MODULE_ALLOW_INSTALL', true),
        'allow_uninstall' => env('MODULE_ALLOW_UNINSTALL', true),
        'allow_core_disable' => env('MODULE_ALLOW_CORE_DISABLE', false),
        'auto_sync_config' => env('MODULE_AUTO_SYNC_CONFIG', true),
    ],
];
```

## Usage

### Basic Usage

#### Using the Facade

```php
use faysal0x1\modulas\Facades\ModuleManager;

// Get all available modules
$modules = ModuleManager::getAvailableModules();

// Check if a module is enabled
if (ModuleManager::isModuleEnabled('example_module')) {
    // Module is enabled
}

// Get module configuration
$config = ModuleManager::getModuleConfig('example_module');

// Get module status
$status = ModuleManager::getStatus();

// Get statistics
$stats = ModuleManager::getStatistics();
```

#### Using the Class Directly

```php
use faysal0x1\modulas\ModuleManager;

// Register all modules
ModuleManager::registerAll();

// Boot all modules
ModuleManager::bootAll();

// Register a specific module
ModuleManager::register('example_module');

// Boot a specific module
ModuleManager::boot('example_module');
```

### Module Management

#### Enable/Disable Modules (Database Mode)

```php
use faysal0x1\modulas\Facades\ModuleManager;

// Enable a module
ModuleManager::enableModule('example_module');

// Disable a module
ModuleManager::disableModule('example_module');

// Update module settings
ModuleManager::updateModuleSettings('example_module', [
    'setting_key' => 'setting_value',
]);
```

#### Install/Uninstall Modules (Database Mode)

```php
use faysal0x1\modulas\Services\DatabaseModuleManager;

// Install a module
DatabaseModuleManager::installModule([
    'module_key' => 'new_module',
    'module_name' => 'New Module',
    'description' => 'A new module',
    'provider_class' => \App\Modules\NewModule\Providers\NewModuleServiceProvider::class,
    'version' => '1.0.0',
    'author' => 'Your Name',
    'is_enabled' => false,
    'auto_register' => true,
    'settings' => [],
    'dependencies' => [],
    'is_core' => false,
    'sort_order' => 0,
]);

// Uninstall a module
DatabaseModuleManager::uninstallModule('new_module');
```

### Module Discovery

#### Discover Module Seeders

```php
use faysal0x1\modulas\Facades\ModuleManager;

// Discover all module seeders
$seeders = ModuleManager::discoverModuleSeeders();

// Use in your DatabaseSeeder
foreach ($seeders as $seeder) {
    $this->call($seeder);
}
```

### Dependency Management

```php
use faysal0x1\modulas\Facades\ModuleManager;

// Get module dependencies
$dependencies = ModuleManager::getModuleDependencies('example_module');

// The package automatically checks dependencies when enabling/disabling modules
```

### Cache Management

```php
use faysal0x1\modulas\Facades\ModuleManager;

// Clear all module caches
ModuleManager::clearAllCache();
```

## Artisan Commands

### List All Modules

```bash
php artisan module:manage list
```

### Enable a Module

```bash
php artisan module:manage enable example_module
```

### Disable a Module

```bash
php artisan module:manage disable example_module
```

### Show Module Status

```bash
php artisan module:manage status
```

### Sync Modules from Config

```bash
php artisan module:manage sync
```

### Clear Module Caches

```bash
php artisan module:manage clear-cache
```

### Install a Module

```bash
php artisan module:manage install new_module \
    --name="New Module" \
    --description="Module description" \
    --provider="App\Modules\NewModule\Providers\NewModuleServiceProvider" \
    --module-version="1.0.0" \
    --author="Your Name" \
    --core
```

### Uninstall a Module

```bash
php artisan module:manage uninstall new_module
```

## Module Structure

Modulas follows a convention-based structure for modules. Here's the recommended structure:

```
app/
└── Modules/
    └── ExampleModule/          # StudlyCase from module key
        ├── Providers/
        │   └── ExampleModuleServiceProvider.php
        ├── Database/
        │   ├── Migrations/
        │   │   └── 2024_01_01_000000_create_example_table.php
        │   └── Seeders/
        │       └── ExampleModuleSeeder.php
        ├── Routes/
        │   ├── web.php
        │   └── api.php
        ├── Controllers/
        ├── Models/
        └── ...
```

### Creating a Module

1. **Create the Module Directory Structure**

```bash
mkdir -p app/Modules/ExampleModule/{Providers,Database/Migrations,Database/Seeders,Routes}
```

2. **Create the Service Provider**

```php
<?php

namespace App\Modules\ExampleModule\Providers;

use Illuminate\Support\ServiceProvider;

class ExampleModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings
    }

    public function boot(): void
    {
        // Boot module
    }
}
```

3. **Register in Config**

Add to `config/modules.php`:

```php
'example_module' => [
    'enabled' => true,
    'auto_register' => true,
    'provider' => \App\Modules\ExampleModule\Providers\ExampleModuleServiceProvider::class,
    'description' => 'Example module',
    'version' => '1.0.0',
    'author' => 'Your Name',
    'is_core' => false,
    'sort_order' => 0,
    'settings' => [],
    'dependencies' => [],
],
```

## Mode Switching

### Config File Mode

```php
use faysal0x1\modulas\ModuleManager;

// Use config file mode
ModuleManager::useDatabase(false);

// Register modules from config
ModuleManager::registerAll();
```

### Database Mode

```php
use faysal0x1\modulas\ModuleManager;

// Use database mode (default)
ModuleManager::useDatabase(true);

// Register modules from database
ModuleManager::registerAll();
```

## Module Interface

You can implement the `ModuleInterface` for better type safety:

```php
<?php

namespace App\Modules\ExampleModule;

use faysal0x1\modulas\Contracts\ModuleInterface;

class ExampleModule implements ModuleInterface
{
    public static function providerClass(): string
    {
        return \App\Modules\ExampleModule\Providers\ExampleModuleServiceProvider::class;
    }

    public static function key(): string
    {
        return 'example_module';
    }
}
```

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email the maintainer instead of using the issue tracker.

## Credits

- [faysal0x1](https://github.com/faysal0x1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
