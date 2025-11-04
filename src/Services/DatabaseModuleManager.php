<?php

namespace faysal0x1\Modulas\Services;

use faysal0x1\Modulas\Models\ModuleSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DatabaseModuleManager
{
    private static array $loadedModules = [];
    private static bool $initialized = false;

    /**
     * Initialize the module manager with database settings.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Sync modules from config to database if needed
        self::syncModulesFromConfig();

        self::$initialized = true;
    }

    /**
     * Register all enabled modules from database.
     */
    public static function registerAll(): void
    {
        self::initialize();

        $enabledModules = ModuleSettings::getEnabledModules();

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
                Log::error("Failed to register module {$moduleName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Boot all loaded modules.
     */
    public static function bootAll(): void
    {
        foreach (self::$loadedModules as $moduleName => $provider) {
            try {
                if (method_exists($provider, 'boot')) {
                    app($provider)->boot();
                }
            } catch (Throwable $e) {
                Log::error("Failed to boot module {$moduleName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Register a specific module by name.
     */
    public static function register(string $moduleName): void
    {
        self::initialize();

        $module = ModuleSettings::getModuleConfig($moduleName);

        if (!$module) {
            throw new RuntimeException("Module '{$moduleName}' not found in database");
        }

        if (!$module['enabled']) {
            throw new RuntimeException("Module '{$moduleName}' is disabled");
        }

        $provider = $module['provider'] ?? null;
        if (!$provider || !class_exists($provider)) {
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
        if (!isset(self::$loadedModules[$moduleName])) {
            throw new InvalidArgumentException("Module '{$moduleName}' not loaded");
        }

        $provider = self::$loadedModules[$moduleName];

        if (method_exists($provider, 'boot')) {
            app($provider)->boot();
        }
    }

    /**
     * Enable a module.
     */
    public static function enableModule(string $moduleKey): bool
    {
        self::initialize();

        $module = ModuleSettings::where('module_key', $moduleKey)->first();

        if (!$module) {
            throw new InvalidArgumentException("Module '{$moduleKey}' not found");
        }

        if ($module->is_core) {
            throw new RuntimeException("Core module '{$moduleKey}' cannot be disabled/enabled");
        }

        // Check dependencies
        if ($module->hasUnmetDependencies()) {
            $unmet = $module->getUnmetDependencies();
            throw new RuntimeException("Module '{$moduleKey}' has unmet dependencies: " . implode(', ', $unmet));
        }

        $result = $module->enable();

        if ($result) {
        }

        return $result;
    }

    /**
     * Disable a module.
     */
    public static function disableModule(string $moduleKey): bool
    {
        self::initialize();

        $module = ModuleSettings::where('module_key', $moduleKey)->first();

        if (!$module) {
            throw new InvalidArgumentException("Module '{$moduleKey}' not found");
        }

        if ($module->is_core) {
            throw new RuntimeException("Core module '{$moduleKey}' cannot be disabled/enabled");
        }

        // Check if other modules depend on this one
        $dependents = ModuleSettings::whereJsonContains('dependencies', $moduleKey)
            ->where('is_enabled', true)
            ->pluck('module_key')
            ->toArray();

        if (!empty($dependents)) {
            throw new RuntimeException("Cannot disable module '{$moduleKey}' because other modules depend on it: " . implode(', ', $dependents));
        }

        $result = $module->disable();


        return $result;
    }

    /**
     * Update module settings.
     */
    public static function updateModuleSettings(string $moduleKey, array $settings): bool
    {
        self::initialize();

        $module = ModuleSettings::where('module_key', $moduleKey)->first();

        if (!$module) {
            throw new InvalidArgumentException("Module '{$moduleKey}' not found");
        }

        $result = $module->updateSettings($settings);


        return $result;
    }

    /**
     * Get all available modules from database.
     */
    public static function getAvailableModules(): array
    {
        self::initialize();

        return ModuleSettings::getStatusOverview();
    }

    /**
     * Get enabled modules.
     */
    public static function getEnabledModules(): array
    {
        self::initialize();

        return ModuleSettings::getEnabledModules();
    }

    /**
     * Get module configuration.
     */
    public static function getModuleConfig(string $moduleKey): ?array
    {
        self::initialize();

        return ModuleSettings::getModuleConfig($moduleKey);
    }

    /**
     * Check if module is enabled.
     */
    public static function isModuleEnabled(string $moduleKey): bool
    {
        $config = self::getModuleConfig($moduleKey);
        return $config ? $config['enabled'] : false;
    }

    /**
     * Check if module is loaded.
     */
    public static function isModuleLoaded(string $moduleName): bool
    {
        return isset(self::$loadedModules[$moduleName]);
    }

    /**
     * Get loaded modules.
     */
    public static function getLoadedModules(): array
    {
        return self::$loadedModules;
    }

    /**
     * Get module status overview.
     */
    public static function getStatus(): array
    {
        self::initialize();

        $modules = ModuleSettings::getStatusOverview();

        foreach ($modules as &$module) {
            $module['loaded'] = self::isModuleLoaded($module['module_key']);
            $module['can_be_disabled'] = $module['is_core'] ? false : true;
            $module['has_unmet_dependencies'] = false;
            $module['unmet_dependencies'] = [];

            if (!$module['is_core']) {
                $moduleObj = ModuleSettings::where('module_key', $module['module_key'])->first();
                if ($moduleObj) {
                    $module['has_unmet_dependencies'] = $moduleObj->hasUnmetDependencies();
                    $module['unmet_dependencies'] = $moduleObj->getUnmetDependencies();
                }
            }
        }

        return $modules;
    }

    /**
     * Sync modules from config file to database.
     */
    public static function syncModulesFromConfig(): void
    {
        try {
            ModuleSettings::syncFromConfig();
        } catch (Throwable $e) {
            Log::error("Failed to sync modules from config: " . $e->getMessage());
        }
    }

    /**
     * Clear all module caches.
     */
    public static function clearAllCache(): void
    {
        ModuleSettings::clearAllCache();
    }

    /**
     * Get module dependencies.
     */
    public static function getModuleDependencies(string $moduleKey): array
    {
        $config = self::getModuleConfig($moduleKey);
        return $config ? ($config['dependencies'] ?? []) : [];
    }

    /**
     * Get modules that depend on a specific module.
     */
    public static function getDependentModules(string $moduleKey): array
    {
        return ModuleSettings::whereJsonContains('dependencies', $moduleKey)
            ->where('is_enabled', true)
            ->pluck('module_key')
            ->toArray();
    }

    /**
     * Install a new module.
     */
    public static function installModule(array $moduleData): bool
    {
        self::initialize();

        try {
            $module = ModuleSettings::create([
                'module_key' => $moduleData['module_key'],
                'module_name' => $moduleData['module_name'],
                'description' => $moduleData['description'] ?? null,
                'is_enabled' => $moduleData['is_enabled'] ?? false,
                'auto_register' => $moduleData['auto_register'] ?? true,
                'provider_class' => $moduleData['provider_class'] ?? null,
                'settings' => $moduleData['settings'] ?? [],
                'dependencies' => $moduleData['dependencies'] ?? [],
                'version' => $moduleData['version'] ?? '1.0.0',
                'author' => $moduleData['author'] ?? null,
                'is_core' => $moduleData['is_core'] ?? false,
                'sort_order' => $moduleData['sort_order'] ?? 0,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to install module: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Uninstall a module.
     */
    public static function uninstallModule(string $moduleKey): bool
    {
        self::initialize();

        $module = ModuleSettings::where('module_key', $moduleKey)->first();

        if (!$module) {
            throw new InvalidArgumentException("Module '{$moduleKey}' not found");
        }

        if ($module->is_core) {
            throw new RuntimeException("Core module '{$moduleKey}' cannot be uninstalled");
        }

        // Check if other modules depend on this one
        $dependents = self::getDependentModules($moduleKey);
        if (!empty($dependents)) {
            throw new RuntimeException("Cannot uninstall module '{$moduleKey}' because other modules depend on it: " . implode(', ', $dependents));
        }

        try {
            $module->delete();
            return true;
        } catch (Throwable $e) {
            Log::error("Failed to uninstall module: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get module statistics.
     */
    public static function getStatistics(): array
    {
        self::initialize();

        $total = ModuleSettings::count();
        $enabled = ModuleSettings::where('is_enabled', true)->count();
        $disabled = ModuleSettings::where('is_enabled', false)->count();
        $core = ModuleSettings::where('is_core', true)->count();
        $custom = ModuleSettings::where('is_core', false)->count();
        $loaded = count(self::$loadedModules);

        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $disabled,
            'core' => $core,
            'custom' => $custom,
            'loaded' => $loaded,
            'enabled_percentage' => $total > 0 ? round(($enabled / $total) * 100, 2) : 0,
        ];
    }
}
