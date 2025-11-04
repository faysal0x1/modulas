<?php

namespace faysal0x1\Modulas\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void useDatabase(bool $useDatabase = true)
 * @method static void registerAll()
 * @method static void bootAll()
 * @method static void register(string $moduleName)
 * @method static void boot(string $moduleName)
 * @method static array getAvailableModules()
 * @method static string studlyFromKey(string $key)
 * @method static array discoverModuleSeeders()
 * @method static array getLoadedModules()
 * @method static bool isLoaded(string $moduleName)
 * @method static array getStatus()
 * @method static bool enableModule(string $moduleKey)
 * @method static bool disableModule(string $moduleKey)
 * @method static bool updateModuleSettings(string $moduleKey, array $settings)
 * @method static array|null getModuleConfig(string $moduleKey)
 * @method static bool isModuleEnabled(string $moduleKey)
 * @method static array getStatistics()
 * @method static void clearAllCache()
 * @method static array getModuleDependencies(string $moduleKey)
 *
 * @see \faysal0x1\Modulas\ModuleManager
 */
class ModuleManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \faysal0x1\Modulas\ModuleManager::class;
    }
}
