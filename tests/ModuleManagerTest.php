<?php

use faysal0x1\Modulas\Models\ModuleSettings;
use faysal0x1\Modulas\ModuleManager;

beforeEach(function () {
    // Clear any existing modules
    ModuleSettings::truncate();
    ModuleManager::clearAllCache();
});

it('can get available modules from config', function () {
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => null,
        ],
    ]]);

    $modules = ModuleManager::getAvailableModules();

    expect($modules)->toBeArray();
});

it('can check if module is enabled from config', function () {
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'auto_register' => true,
        ],
    ]]);

    expect(ModuleManager::isModuleEnabled('test_module'))->toBeTrue();
    expect(ModuleManager::isModuleEnabled('non_existent'))->toBeFalse();
});

it('can convert key to studly case', function () {
    expect(ModuleManager::studlyFromKey('test_module'))->toBe('TestModule');
    expect(ModuleManager::studlyFromKey('test-module'))->toBe('TestModule');
    expect(ModuleManager::studlyFromKey('testModule'))->toBe('Testmodule');
});

it('can get module config from config file', function () {
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => 'TestProvider',
        ],
    ]]);

    $config = ModuleManager::getModuleConfig('test_module');

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeTrue();
});

it('can get statistics', function () {
    $stats = ModuleManager::getStatistics();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys(['total', 'enabled', 'disabled', 'core', 'custom', 'loaded', 'enabled_percentage']);
});

it('can clear all cache', function () {
    expect(ModuleManager::clearAllCache())->toBeNull();
});

it('can get module dependencies from config', function () {
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'dependencies' => ['dependency1', 'dependency2'],
        ],
    ]]);

    $dependencies = ModuleManager::getModuleDependencies('test_module');

    expect($dependencies)->toBe(['dependency1', 'dependency2']);
});

it('can switch between database and config mode', function () {
    ModuleManager::useDatabase(false);
    // Should not throw
    expect(true)->toBeTrue();

    ModuleManager::useDatabase(true);
    expect(true)->toBeTrue();
});
