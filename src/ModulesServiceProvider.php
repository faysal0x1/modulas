<?php

namespace faysal0x1\modulas;

use faysal0x1\modulas\Commands\ModuleManagementCommand;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModulesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('modulas')
            ->hasConfigFile('modules')
            ->hasMigration('2025_10_23_151204_create_module_settings_table')
            ->hasCommand(ModuleManagementCommand::class);
    }

    public function register(): void
    {
        parent::register();

        $rootEnabled = (bool) (config('modules.enabled', true));
        if (! $rootEnabled) {
            return;
        }

        $useDatabase = (bool) (config('modules.use_database', true));
        ModuleManager::useDatabase($useDatabase);

        if ($useDatabase) {
            // Initialize database mode
            try {
                \faysal0x1\modulas\Services\DatabaseModuleManager::initialize();
            } catch (\Exception $e) {
                // Database might not be available during installation
            }
        }

        $modulesConfig = config('modules.modules', []);

        foreach ($modulesConfig as $moduleKey => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $provider = $module['provider'] ?? null;
            if (is_string($provider) && class_exists($provider)) {
                $this->app->register($provider);
            }
        }

        // Register modules based on mode
        if ($useDatabase) {
            ModuleManager::registerAll();
        } else {
            ModuleManager::registerAll();
        }
    }

    public function boot(): void
    {
        parent::boot();

        $rootEnabled = (bool) (config('modules.enabled', true));
        if (! $rootEnabled) {
            return;
        }

        $modulesConfig = config('modules.modules', []);

        foreach ($modulesConfig as $moduleKey => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $studly = self::studlyFromKey($moduleKey);
            $basePath = app_path('Modules/'.$studly);

            // Convention-based resource loading for modules without a custom provider
            $hasCustomProvider = is_string($module['provider'] ?? null) && class_exists($module['provider']);

            // Load migrations for all enabled modules (safe to call even if provider also loads them)
            $migrationsPath = $basePath.'/Database/Migrations';
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }

            // Load routes if no custom provider is present
            if (! $hasCustomProvider) {
                $webRoutes = $basePath.'/Routes/web.php';
                if (file_exists($webRoutes)) {
                    Route::middleware('web')->group($webRoutes);
                }

                $apiRoutes = $basePath.'/Routes/api.php';
                if (file_exists($apiRoutes)) {
                    Route::middleware('api')->prefix('api')->group($apiRoutes);
                }
            }
        }

        // Boot all modules
        ModuleManager::bootAll();
    }

    private static function studlyFromKey(string $key): string
    {
        $parts = preg_split('/[_-]+/', $key) ?: [$key];
        $parts = array_map(static function ($p) {
            return ucfirst(strtolower($p));
        }, $parts);

        return implode('', $parts);
    }
}
