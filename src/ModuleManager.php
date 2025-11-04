<?php

namespace faysal0x1\modulas;

use faysal0x1\modulas\Services\DatabaseModuleManager;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ModuleManager
{
    private static array $loadedModules = [];

    private static bool $useDatabase = true;

    /**
     * Set whether to use database-driven module management.
     */
    public static function useDatabase(bool $useDatabase = true): void
    {
        self::$useDatabase = $useDatabase;
    }

    /**
     * Register all enabled modules.
     */
    public static function registerAll(): void
    {
        if (self::$useDatabase) {
            DatabaseModuleManager::registerAll();
            self::$loadedModules = DatabaseModuleManager::getLoadedModules();

            return;
        }

        $enabledModules = self::getEnabledModules();

        foreach ($enabledModules as $moduleName => $module) {
            try {
                $provider = $module['provider'] ?? null;

                if ($provider && class_exists($provider)) {
                    app()->register($provider);
                    self::$loadedModules[$moduleName] = $provider;
                } else {
                    Log::warning("Provider not found or undefined for module: {$moduleName}");
                }
            } catch (Throwable $e) {
                Log::error("Failed to register module {$moduleName}: ".$e->getMessage());
            }
        }
    }

    /**
     * Boot all loaded modules.
     */
    public static function bootAll(): void
    {
        if (self::$useDatabase) {
            DatabaseModuleManager::bootAll();

            return;
        }

        foreach (self::$loadedModules as $moduleName => $provider) {
            try {
                if (method_exists($provider, 'boot')) {
                    app($provider)->boot();
                }
            } catch (Throwable $e) {
                Log::error("Failed to boot module {$moduleName}: ".$e->getMessage());
            }
        }
    }

    /**
     * Register a specific module by name.
     */
    public static function register(string $moduleName): void
    {
        $module = self::getConfigModuleConfig($moduleName);

        $provider = $module['provider'] ?? null;
        if (! $provider || ! class_exists($provider)) {
            throw new RuntimeException("Provider not found or invalid for module '{$moduleName}'");
        }

        app()->register($provider);
        self::$loadedModules[$moduleName] = $provider;
    }

    /**
     * Boot a specific module by name.
     */
    public static function boot(string $moduleName): void
    {
        if (! isset(self::$loadedModules[$moduleName])) {
            throw new InvalidArgumentException("Module '{$moduleName}' not loaded");
        }

        $provider = self::$loadedModules[$moduleName];

        if (method_exists($provider, 'boot')) {
            app($provider)->boot();
        }
    }

    /**
     * Get all enabled modules from config.
     */
    private static function getEnabledModules(): array
    {
        $config = config('modules', []);
        $globalEnabled = $config['enabled'] ?? true;
        $modules = $config['modules'] ?? [];

        if (! $globalEnabled) {
            return [];
        }

        $enabled = [];

        foreach ($modules as $name => $settings) {
            $isEnabled = $settings['enabled'] ?? false;
            $autoRegister = $settings['auto_register'] ?? true;

            if ($isEnabled && $autoRegister) {
                $enabled[$name] = $settings;
            }
        }

        return $enabled;
    }

    /**
     * Get configuration for a single module from config file.
     */
    private static function getConfigModuleConfig(string $moduleName): array
    {
        $config = config("modules.modules.{$moduleName}");

        if (! $config) {
            throw new InvalidArgumentException("Module '{$moduleName}' not found in configuration");
        }

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException("Module '{$moduleName}' is disabled");
        }

        return $config;
    }

    /**
     * Get all available modules.
     */
    public static function getAvailableModules(): array
    {
        return config('modules.modules', []);
    }

    /**
     * Returns StudlyCase directory name from config key like `supercache` or `payment_gateway`.
     */
    public static function studlyFromKey(string $key): string
    {
        $parts = preg_split('/[_-]+/', $key) ?: [$key];
        $parts = array_map(static function ($p) {
            return ucfirst(strtolower($p));
        }, $parts);

        return implode('', $parts);
    }

    /**
     * Discover fully-qualified seeder class names for all enabled modules.
     * It checks convention App\\Modules\\<Studly>\\Database\\Seeders\\*Seeder classes.
     */
    public static function discoverModuleSeeders(): array
    {
        $seeders = [];
        $modules = config('modules.modules', []);

        foreach ($modules as $key => $settings) {
            if (! ($settings['enabled'] ?? false)) {
                continue;
            }

            $studly = self::studlyFromKey($key);
            $seedersNamespace = "App\\Modules\\{$studly}\\Database\\Seeders";
            $seedersPath = app_path("Modules/{$studly}/Database/Seeders");

            if (! is_dir($seedersPath)) {
                continue;
            }

            $files = scandir($seedersPath) ?: [];
            foreach ($files as $file) {
                if (substr($file, -11) !== 'Seeder.php') {
                    continue;
                }

                $classBase = substr($file, 0, -4); // remove .php
                $fqcn = $seedersNamespace.'\\'.$classBase;
                if (class_exists($fqcn)) {
                    $seeders[] = $fqcn;
                }
            }
        }

        return $seeders;
    }

    /**
     * Get loaded modules.
     */
    public static function getLoadedModules(): array
    {
        return self::$loadedModules;
    }

    /**
     * Check if a module is loaded.
     */
    public static function isLoaded(string $moduleName): bool
    {
        return isset(self::$loadedModules[$moduleName]);
    }

    /**
     * Get module status overview.
     */
    public static function getStatus(): array
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::getStatus();
        }

        $config = config('modules', []);
        $modules = $config['modules'] ?? [];

        $status = [];

        foreach ($modules as $name => $settings) {
            $provider = $settings['provider'] ?? null;
            $status[$name] = [
                'provider' => $provider,
                'enabled' => $settings['enabled'] ?? false,
                'auto_register' => $settings['auto_register'] ?? true,
                'loaded' => self::isLoaded($name),
                'exists' => $provider ? class_exists($provider) : false,
            ];
        }

        return $status;
    }

    /**
     * Enable a module.
     */
    public static function enableModule(string $moduleKey): bool
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::enableModule($moduleKey);
        }

        throw new RuntimeException('Module enabling is only available with database mode');
    }

    /**
     * Disable a module.
     */
    public static function disableModule(string $moduleKey): bool
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::disableModule($moduleKey);
        }

        throw new RuntimeException('Module disabling is only available with database mode');
    }

    /**
     * Update module settings.
     */
    public static function updateModuleSettings(string $moduleKey, array $settings): bool
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::updateModuleSettings($moduleKey, $settings);
        }

        throw new RuntimeException('Module settings update is only available with database mode');
    }

    /**
     * Get module configuration.
     */
    public static function getModuleConfig(string $moduleKey): ?array
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::getModuleConfig($moduleKey);
        }

        return config("modules.modules.{$moduleKey}");
    }

    /**
     * Check if module is enabled.
     */
    public static function isModuleEnabled(string $moduleKey): bool
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::isModuleEnabled($moduleKey);
        }

        $config = config("modules.modules.{$moduleKey}");

        return $config ? ($config['enabled'] ?? false) : false;
    }

    /**
     * Get module statistics.
     */
    public static function getStatistics(): array
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::getStatistics();
        }

        return [
            'total' => 0,
            'enabled' => 0,
            'disabled' => 0,
            'core' => 0,
            'custom' => 0,
            'loaded' => count(self::$loadedModules),
            'enabled_percentage' => 0,
        ];
    }

    /**
     * Clear all module caches.
     */
    public static function clearAllCache(): void
    {
        if (self::$useDatabase) {
            DatabaseModuleManager::clearAllCache();
        }
    }

    /**
     * Get module dependencies.
     */
    public static function getModuleDependencies(string $moduleKey): array
    {
        if (self::$useDatabase) {
            return DatabaseModuleManager::getModuleDependencies($moduleKey);
        }

        $config = config("modules.modules.{$moduleKey}");

        return $config ? ($config['dependencies'] ?? []) : [];
    }
}