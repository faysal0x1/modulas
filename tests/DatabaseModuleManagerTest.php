<?php

use faysal0x1\Modulas\Models\ModuleSettings;
use faysal0x1\Modulas\Services\DatabaseModuleManager;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    ModuleSettings::truncate();
    Cache::flush();
});

it('can initialize database module manager', function () {
    expect(fn () => DatabaseModuleManager::initialize())->not->toThrow(Exception::class);
});

it('can enable a module', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => false,
        'is_core' => false,
    ]);

    $result = DatabaseModuleManager::enableModule('test_module');

    expect($result)->toBeTrue();
    expect($module->fresh()->is_enabled)->toBeTrue();
});

it('cannot enable non-existent module', function () {
    expect(fn () => DatabaseModuleManager::enableModule('non_existent'))
        ->toThrow(InvalidArgumentException::class);
});

it('cannot enable core modules', function () {
    ModuleSettings::create([
        'module_key' => 'core_module',
        'module_name' => 'Core Module',
        'is_core' => true,
    ]);

    expect(fn () => DatabaseModuleManager::enableModule('core_module'))
        ->toThrow(RuntimeException::class);
});

it('cannot enable module with unmet dependencies', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'dependencies' => ['missing_dep'],
        'is_core' => false,
    ]);

    expect(fn () => DatabaseModuleManager::enableModule('test_module'))
        ->toThrow(RuntimeException::class);
});

it('can disable a module', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'is_core' => false,
    ]);

    $result = DatabaseModuleManager::disableModule('test_module');

    expect($result)->toBeTrue();
    expect($module->fresh()->is_enabled)->toBeFalse();
});

it('cannot disable module if other modules depend on it', function () {
    $dependency = ModuleSettings::create([
        'module_key' => 'dependency',
        'module_name' => 'Dependency',
        'is_enabled' => true,
        'is_core' => false,
    ]);

    ModuleSettings::create([
        'module_key' => 'dependent',
        'module_name' => 'Dependent',
        'is_enabled' => true,
        'dependencies' => ['dependency'],
    ]);

    expect(fn () => DatabaseModuleManager::disableModule('dependency'))
        ->toThrow(RuntimeException::class);
});

it('can update module settings', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'settings' => ['key1' => 'value1'],
    ]);

    $result = DatabaseModuleManager::updateModuleSettings('test_module', ['key2' => 'value2']);

    expect($result)->toBeTrue();
    expect($module->fresh()->settings)->toHaveKey('key2');
});

it('can get module config', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'provider_class' => 'TestProvider',
    ]);

    $config = DatabaseModuleManager::getModuleConfig('test_module');

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeTrue();
    expect($config['provider'])->toBe('TestProvider');
});

it('can check if module is enabled', function () {
    ModuleSettings::create([
        'module_key' => 'enabled_module',
        'module_name' => 'Enabled Module',
        'is_enabled' => true,
    ]);

    ModuleSettings::create([
        'module_key' => 'disabled_module',
        'module_name' => 'Disabled Module',
        'is_enabled' => false,
    ]);

    expect(DatabaseModuleManager::isModuleEnabled('enabled_module'))->toBeTrue();
    expect(DatabaseModuleManager::isModuleEnabled('disabled_module'))->toBeFalse();
});

it('can install a module', function () {
    $moduleData = [
        'module_key' => 'new_module',
        'module_name' => 'New Module',
        'description' => 'A new module',
        'provider_class' => 'NewProvider',
        'version' => '1.0.0',
        'is_enabled' => false,
        'auto_register' => true,
        'settings' => [],
        'dependencies' => [],
        'is_core' => false,
        'sort_order' => 0,
    ];

    $result = DatabaseModuleManager::installModule($moduleData);

    expect($result)->toBeTrue();

    $module = ModuleSettings::where('module_key', 'new_module')->first();
    expect($module)->not->toBeNull();
    expect($module->module_name)->toBe('New Module');
});

it('can uninstall a module', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_core' => false,
    ]);

    $result = DatabaseModuleManager::uninstallModule('test_module');

    expect($result)->toBeTrue();
    expect(ModuleSettings::where('module_key', 'test_module')->first())->toBeNull();
});

it('cannot uninstall core modules', function () {
    ModuleSettings::create([
        'module_key' => 'core_module',
        'module_name' => 'Core Module',
        'is_core' => true,
    ]);

    expect(fn () => DatabaseModuleManager::uninstallModule('core_module'))
        ->toThrow(RuntimeException::class);
});

it('cannot uninstall module if other modules depend on it', function () {
    $dependency = ModuleSettings::create([
        'module_key' => 'dependency',
        'module_name' => 'Dependency',
        'is_core' => false,
    ]);

    ModuleSettings::create([
        'module_key' => 'dependent',
        'module_name' => 'Dependent',
        'is_enabled' => true,
        'dependencies' => ['dependency'],
    ]);

    expect(fn () => DatabaseModuleManager::uninstallModule('dependency'))
        ->toThrow(RuntimeException::class);
});

it('can get statistics', function () {
    ModuleSettings::create([
        'module_key' => 'enabled_module',
        'module_name' => 'Enabled',
        'is_enabled' => true,
        'is_core' => false,
    ]);

    ModuleSettings::create([
        'module_key' => 'disabled_module',
        'module_name' => 'Disabled',
        'is_enabled' => false,
        'is_core' => false,
    ]);

    ModuleSettings::create([
        'module_key' => 'core_module',
        'module_name' => 'Core',
        'is_enabled' => true,
        'is_core' => true,
    ]);

    $stats = DatabaseModuleManager::getStatistics();

    expect($stats['total'])->toBe(3);
    expect($stats['enabled'])->toBe(2);
    expect($stats['disabled'])->toBe(1);
    expect($stats['core'])->toBe(1);
    expect($stats['custom'])->toBe(2);
});

it('can get module dependencies', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'dependencies' => ['dep1', 'dep2'],
    ]);

    $dependencies = DatabaseModuleManager::getModuleDependencies('test_module');

    expect($dependencies)->toBe(['dep1', 'dep2']);
});

it('can get dependent modules', function () {
    ModuleSettings::create([
        'module_key' => 'dependency',
        'module_name' => 'Dependency',
    ]);

    ModuleSettings::create([
        'module_key' => 'dependent1',
        'module_name' => 'Dependent 1',
        'is_enabled' => true,
        'dependencies' => ['dependency'],
    ]);

    ModuleSettings::create([
        'module_key' => 'dependent2',
        'module_name' => 'Dependent 2',
        'is_enabled' => true,
        'dependencies' => ['dependency'],
    ]);

    $dependents = DatabaseModuleManager::getDependentModules('dependency');

    expect($dependents)->toContain('dependent1');
    expect($dependents)->toContain('dependent2');
});

it('can sync modules from config', function () {
    config(['modules.modules' => [
        'config_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => 'ConfigProvider',
        ],
    ]]);

    DatabaseModuleManager::syncModulesFromConfig();

    $module = ModuleSettings::where('module_key', 'config_module')->first();

    expect($module)->not->toBeNull();
});

it('can clear all cache', function () {
    expect(fn () => DatabaseModuleManager::clearAllCache())->not->toThrow(Exception::class);
});
