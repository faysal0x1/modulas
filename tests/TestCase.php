<?php

namespace faysal0x1\modulas\Tests;

use faysal0x1\Modulas\ModulesServiceProvider;
use faysal0x1\modulas\ModulesServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'faysal0x1\\Modulas\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ModulesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load migrations
        $migration = include __DIR__.'/../database/migrations/2025_10_23_151204_create_module_settings_table.php';
        $migration->up();
    }
}
