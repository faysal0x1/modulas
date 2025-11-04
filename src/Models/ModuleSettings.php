<?php

namespace faysal0x1\modulas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property string $module_key
 * @property string $module_name
 * @property string|null $description
 * @property bool $is_enabled
 * @property bool $auto_register
 * @property string|null $provider_class
 * @property array|null $settings
 * @property array|null $dependencies
 * @property string|null $version
 * @property string|null $author
 * @property string|null $changelog
 * @property bool $is_core
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ModuleSettings extends Model
{
    protected $table = 'module_settings';

    protected $fillable = [
        'module_key',
        'module_name',
        'description',
        'is_enabled',
        'auto_register',
        'provider_class',
        'settings',
        'dependencies',
        'version',
        'author',
        'changelog',
        'is_core',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_register' => 'boolean',
        'is_core' => 'boolean',
        'settings' => 'array',
        'dependencies' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get the cache key for this module's settings.
     */
    public function getCacheKey(): string
    {
        return "module_settings_{$this->module_key}";
    }

    /**
     * Get the cache key for all module settings.
     */
    public static function getAllCacheKey(): string
    {
        return 'all_module_settings';
    }

    /**
     * Get all enabled modules with caching.
     */
    public static function getEnabledModules(): array
    {
        return Cache::remember(self::getAllCacheKey(), 3600, function () {
            return self::where('is_enabled', true)
                ->where('auto_register', true)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(function ($module) {
                    return [
                        $module->module_key => [
                            'enabled' => $module->is_enabled,
                            'auto_register' => $module->auto_register,
                            'provider' => $module->provider_class,
                            'settings' => $module->settings ?? [],
                            'dependencies' => $module->dependencies ?? [],
                            'version' => $module->version,
                            'author' => $module->author,
                            'is_core' => $module->is_core,
                        ],
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get module configuration by key.
     */
    public static function getModuleConfig(string $moduleKey): ?array
    {
        $module = Cache::remember("module_config_{$moduleKey}", 3600, function () use ($moduleKey) {
            return self::where('module_key', $moduleKey)->first();
        });

        if (! $module) {
            return null;
        }

        return [
            'enabled' => $module->is_enabled,
            'auto_register' => $module->auto_register,
            'provider' => $module->provider_class,
            'settings' => $module->settings ?? [],
            'dependencies' => $module->dependencies ?? [],
            'version' => $module->version,
            'author' => $module->author,
            'is_core' => $module->is_core,
        ];
    }

    /**
     * Enable a module.
     */
    public function enable(): bool
    {
        if ($this->is_core) {
            return false; // Core modules cannot be disabled/enabled
        }

        $this->is_enabled = true;
        $result = $this->save();

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Disable a module.
     */
    public function disable(): bool
    {
        if ($this->is_core) {
            return false; // Core modules cannot be disabled/enabled
        }

        $this->is_enabled = false;
        $result = $this->save();

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Update module settings.
     */
    public function updateSettings(array $settings): bool
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $result = $this->save();

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Clear module cache.
     */
    public function clearCache(): void
    {
        try {
            if (app()->bound('cache')) {
                Cache::forget($this->getCacheKey());
                Cache::forget(self::getAllCacheKey());
                Cache::forget("module_config_{$this->module_key}");
            }
        } catch (\Exception $e) {
            // Cache service might not be available
        }
    }

    /**
     * Clear all module caches.
     */
    public static function clearAllCache(): void
    {
        try {
            if (app()->bound('cache')) {
                Cache::forget(self::getAllCacheKey());

                // Clear individual module caches
                try {
                    self::all()->each(function ($module) {
                        Cache::forget($module->getCacheKey());
                        Cache::forget("module_config_{$module->module_key}");
                    });
                } catch (\Exception $e) {
                    // Database might not be available
                }
            }
        } catch (\Exception $e) {
            // Cache service might not be available
        }
    }

    /**
     * Sync modules from config file to database.
     */
    public static function syncFromConfig(): void
    {
        try {
            // Check if database connection is available
            if (! app()->bound('db')) {
                return;
            }

            // Check if table exists
            try {
                if (! Schema::hasTable('module_settings')) {
                    return;
                }
            } catch (\Exception $e) {
                return;
            }

            $configModules = config('modules.modules', []);

            foreach ($configModules as $key => $config) {
                self::updateOrCreate(
                    ['module_key' => $key],
                    [
                        'module_name' => ucwords(str_replace(['_', '-'], ' ', $key)),
                        'description' => $config['description'] ?? null,
                        'is_enabled' => $config['enabled'] ?? false,
                        'auto_register' => $config['auto_register'] ?? true,
                        'provider_class' => $config['provider'] ?? null,
                        'settings' => $config['settings'] ?? [],
                        'dependencies' => $config['dependencies'] ?? [],
                        'version' => $config['version'] ?? '1.0.0',
                        'author' => $config['author'] ?? null,
                        'is_core' => $config['is_core'] ?? false,
                        'sort_order' => $config['sort_order'] ?? 0,
                    ]
                );
            }

            self::clearAllCache();
        } catch (\Exception $e) {
            // Silently fail if database isn't available during package discovery
        }
    }

    /**
     * Get module status overview.
     */
    public static function getStatusOverview(): array
    {
        $modules = self::orderBy('sort_order')->get();

        return $modules->map(function ($module) {
            $provider = $module->provider_class;

            return [
                'id' => $module->id,
                'module_key' => $module->module_key,
                'module_name' => $module->module_name,
                'description' => $module->description,
                'provider' => $provider,
                'enabled' => $module->is_enabled,
                'auto_register' => $module->auto_register,
                'exists' => $provider ? class_exists($provider) : false,
                'version' => $module->version,
                'author' => $module->author,
                'is_core' => $module->is_core,
                'settings' => $module->settings ?? [],
                'dependencies' => $module->dependencies ?? [],
                'created_at' => $module->created_at,
                'updated_at' => $module->updated_at,
            ];
        })->toArray();
    }

    /**
     * Check if module can be disabled.
     */
    public function canBeDisabled(): bool
    {
        return ! $this->is_core;
    }

    /**
     * Check if module has unmet dependencies.
     */
    public function hasUnmetDependencies(): bool
    {
        if (empty($this->dependencies)) {
            return false;
        }

        foreach ($this->dependencies as $dependency) {
            $depModule = self::where('module_key', $dependency)
                ->where('is_enabled', true)
                ->first();

            if (! $depModule) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get unmet dependencies.
     */
    public function getUnmetDependencies(): array
    {
        $unmet = [];

        if (empty($this->dependencies)) {
            return $unmet;
        }

        foreach ($this->dependencies as $dependency) {
            $depModule = self::where('module_key', $dependency)
                ->where('is_enabled', true)
                ->first();

            if (! $depModule) {
                $unmet[] = $dependency;
            }
        }

        return $unmet;
    }
}
