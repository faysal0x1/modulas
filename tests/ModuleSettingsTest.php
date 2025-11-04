<?php

use faysal0x1\modulas\Models\ModuleSettings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    ModuleSettings::truncate();
    Cache::flush();
});

it('can create a module setting', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'description' => 'Test description',
        'is_enabled' => true,
        'auto_register' => true,
        'provider_class' => 'TestProvider',
        'version' => '1.0.0',
        'is_core' => false,
    ]);

    expect($module->module_key)->toBe('test_module');
    expect($module->is_enabled)->toBeTrue();
});

it('can enable a module', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => false,
        'is_core' => false,
    ]);

    $result = $module->enable();

    expect($result)->toBeTrue();
    expect($module->fresh()->is_enabled)->toBeTrue();
});

it('can disable a module', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'is_core' => false,
    ]);

    $result = $module->disable();

    expect($result)->toBeTrue();
    expect($module->fresh()->is_enabled)->toBeFalse();
});

it('cannot enable or disable core modules', function () {
    $module = ModuleSettings::create([
        'module_key' => 'core_module',
        'module_name' => 'Core Module',
        'is_enabled' => true,
        'is_core' => true,
    ]);

    expect($module->enable())->toBeFalse();
    expect($module->disable())->toBeFalse();
});

it('can update module settings', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'settings' => ['key1' => 'value1'],
    ]);

    $result = $module->updateSettings(['key2' => 'value2']);

    expect($result)->toBeTrue();
    expect($module->fresh()->settings)->toHaveKey('key1');
    expect($module->fresh()->settings)->toHaveKey('key2');
});

it('can get enabled modules', function () {
    ModuleSettings::create([
        'module_key' => 'enabled_module',
        'module_name' => 'Enabled Module',
        'is_enabled' => true,
        'auto_register' => true,
    ]);

    ModuleSettings::create([
        'module_key' => 'disabled_module',
        'module_name' => 'Disabled Module',
        'is_enabled' => false,
        'auto_register' => true,
    ]);

    $enabled = ModuleSettings::getEnabledModules();

    expect($enabled)->toHaveKey('enabled_module');
    expect($enabled)->not->toHaveKey('disabled_module');
});

it('can get module config by key', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'provider_class' => 'TestProvider',
    ]);

    $config = ModuleSettings::getModuleConfig('test_module');

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeTrue();
    expect($config['provider'])->toBe('TestProvider');
});

it('can clear cache', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
    ]);

    $module->clearCache();

    // Should not throw
    expect(true)->toBeTrue();
});

it('can clear all cache', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
    ]);

    ModuleSettings::clearAllCache();

    // Should not throw
    expect(true)->toBeTrue();
});

it('can sync modules from config', function () {
    config(['modules.modules' => [
        'config_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => 'ConfigProvider',
            'description' => 'Config module',
            'version' => '1.0.0',
        ],
    ]]);

    ModuleSettings::syncFromConfig();

    $module = ModuleSettings::where('module_key', 'config_module')->first();

    expect($module)->not->toBeNull();
    expect($module->module_key)->toBe('config_module');
});

it('can check if module can be disabled', function () {
    $coreModule = ModuleSettings::create([
        'module_key' => 'core_module',
        'module_name' => 'Core Module',
        'is_core' => true,
    ]);

    $regularModule = ModuleSettings::create([
        'module_key' => 'regular_module',
        'module_name' => 'Regular Module',
        'is_core' => false,
    ]);

    expect($coreModule->canBeDisabled())->toBeFalse();
    expect($regularModule->canBeDisabled())->toBeTrue();
});

it('can check for unmet dependencies', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'dependencies' => ['missing_module'],
    ]);

    expect($module->hasUnmetDependencies())->toBeTrue();

    ModuleSettings::create([
        'module_key' => 'missing_module',
        'module_name' => 'Missing Module',
        'is_enabled' => true,
    ]);

    expect($module->fresh()->hasUnmetDependencies())->toBeFalse();
});

it('can get unmet dependencies', function () {
    $module = ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'dependencies' => ['missing1', 'missing2'],
    ]);

    $unmet = $module->getUnmetDependencies();

    expect($unmet)->toContain('missing1');
    expect($unmet)->toContain('missing2');
});
