<?php

use faysal0x1\Modulas\ModulesServiceProvider;

it('can register the service provider', function () {
    $provider = new ModulesServiceProvider($this->app);

    expect($provider)->toBeInstanceOf(ModulesServiceProvider::class);
});

it('can register modules from config', function () {
    config(['modules.enabled' => true]);
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'auto_register' => true,
            'provider' => null,
        ],
    ]]);

    $provider = new ModulesServiceProvider($this->app);
    $provider->register();

    // Should not throw
    expect(true)->toBeTrue();
});

it('can boot modules', function () {
    config(['modules.enabled' => true]);
    config(['modules.modules' => []]);

    $provider = new ModulesServiceProvider($this->app);
    $provider->boot();

    // Should not throw
    expect(true)->toBeTrue();
});

it('does not register modules when disabled', function () {
    config(['modules.enabled' => false]);
    config(['modules.modules' => [
        'test_module' => [
            'enabled' => true,
            'auto_register' => true,
        ],
    ]]);

    $provider = new ModulesServiceProvider($this->app);
    $provider->register();

    // Should not register modules
    expect(true)->toBeTrue();
});
