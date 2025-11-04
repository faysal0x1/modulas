<?php

use faysal0x1\modulas\Models\ModuleSettings;

beforeEach(function () {
    ModuleSettings::truncate();
});

it('can list modules', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'provider_class' => 'TestProvider',
        'version' => '1.0.0',
        'is_core' => false,
    ]);

    $this->artisan('module:manage', ['action' => 'list'])
        ->assertSuccessful();
});

it('can enable a module', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => false,
        'is_core' => false,
    ]);

    $this->artisan('module:manage', [
        'action' => 'enable',
        'module' => 'test_module',
    ])
        ->assertSuccessful();

    expect(ModuleSettings::where('module_key', 'test_module')->first()->is_enabled)
        ->toBeTrue();
});

it('can disable a module', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
        'is_core' => false,
    ]);

    $this->artisan('module:manage', [
        'action' => 'disable',
        'module' => 'test_module',
    ])
        ->assertSuccessful();

    expect(ModuleSettings::where('module_key', 'test_module')->first()->is_enabled)
        ->toBeFalse();
});

it('can show status', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_enabled' => true,
    ]);

    $this->artisan('module:manage', ['action' => 'status'])
        ->assertSuccessful();
});

it('can sync from config', function () {
    config(['modules.modules' => [
        'config_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => 'ConfigProvider',
        ],
    ]]);

    $this->artisan('module:manage', ['action' => 'sync'])
        ->assertSuccessful();

    expect(ModuleSettings::where('module_key', 'config_module')->first())
        ->not->toBeNull();
});

it('can clear cache', function () {
    $this->artisan('module:manage', ['action' => 'clear-cache'])
        ->assertSuccessful();
});

it('can install a module', function () {
    $this->artisan('module:manage', [
        'action' => 'install',
        'module' => 'new_module',
        '--name' => 'New Module',
        '--description' => 'A new module',
        '--provider' => 'NewProvider',
        '--module-version' => '1.0.0',
        '--author' => 'Test Author',
    ])
        ->assertSuccessful();

    expect(ModuleSettings::where('module_key', 'new_module')->first())
        ->not->toBeNull();
});

it('can uninstall a module', function () {
    ModuleSettings::create([
        'module_key' => 'test_module',
        'module_name' => 'Test Module',
        'is_core' => false,
    ]);

    $this->artisan('module:manage', [
        'action' => 'uninstall',
        'module' => 'test_module',
    ])
        ->expectsConfirmation('Are you sure you want to uninstall module \'test_module\'?', 'yes')
        ->assertSuccessful();

    expect(ModuleSettings::where('module_key', 'test_module')->first())
        ->toBeNull();
});
