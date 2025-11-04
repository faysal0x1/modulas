<?php

namespace faysal0x1\modulas\Commands;

use faysal0x1\modulas\ModuleManager;
use faysal0x1\modulas\Services\DatabaseModuleManager;
use Illuminate\Console\Command;

class ModuleManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:manage
                            {action : The action to perform (list|enable|disable|status|sync|clear-cache|install|uninstall)}
                            {module? : The module key (required for enable/disable/install/uninstall)}
                            {--settings= : JSON string of settings to update}
                            {--name= : Module name for installation}
                            {--description= : Module description for installation}
                            {--provider= : Provider class for installation}
                            {--module-version=1.0.0 : Module version for installation}
                            {--author= : Module author for installation}
                            {--core : Mark as core module for installation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage modules through the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $module = $this->argument('module');

        try {
            switch ($action) {
                case 'list':
                    return $this->listModules();
                case 'enable':
                    return $this->enableModule($module);
                case 'disable':
                    return $this->disableModule($module);
                case 'status':
                    return $this->showStatus();
                case 'sync':
                    return $this->syncFromConfig();
                case 'clear-cache':
                    return $this->clearCache();
                case 'install':
                    return $this->installModule($module);
                case 'uninstall':
                    return $this->uninstallModule($module);
                default:
                    $this->error("Unknown action: {$action}");

                    return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * List all modules.
     */
    private function listModules(): int
    {
        $modules = ModuleManager::getStatus();

        if (empty($modules)) {
            $this->info('No modules found.');

            return 0;
        }

        $headers = ['Module Key', 'Name', 'Enabled', 'Loaded', 'Provider', 'Version', 'Core'];
        $rows = [];

        foreach ($modules as $module) {
            $rows[] = [
                $module['module_key'],
                $module['module_name'] ?? 'N/A',
                $module['enabled'] ? 'Yes' : 'No',
                $module['loaded'] ? 'Yes' : 'No',
                $module['provider'] ?? 'N/A',
                $module['version'] ?? 'N/A',
                $module['is_core'] ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        $stats = ModuleManager::getStatistics();
        $this->newLine();
        $this->info('Statistics:');
        $this->line("Total: {$stats['total']}");
        $this->line("Enabled: {$stats['enabled']}");
        $this->line("Disabled: {$stats['disabled']}");
        $this->line("Core: {$stats['core']}");
        $this->line("Custom: {$stats['custom']}");
        $this->line("Loaded: {$stats['loaded']}");

        return 0;
    }

    /**
     * Enable a module.
     */
    private function enableModule(?string $module): int
    {
        if (! $module) {
            $this->error('Module key is required for enable action.');

            return 1;
        }

        try {
            $result = ModuleManager::enableModule($module);

            if ($result) {
                $this->info("Module '{$module}' enabled successfully.");

                return 0;
            } else {
                $this->error("Failed to enable module '{$module}'.");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error enabling module '{$module}': ".$e->getMessage());

            return 1;
        }
    }

    /**
     * Disable a module.
     */
    private function disableModule(?string $module): int
    {
        if (! $module) {
            $this->error('Module key is required for disable action.');

            return 1;
        }

        try {
            $result = ModuleManager::disableModule($module);

            if ($result) {
                $this->info("Module '{$module}' disabled successfully.");

                return 0;
            } else {
                $this->error("Failed to disable module '{$module}'.");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error disabling module '{$module}': ".$e->getMessage());

            return 1;
        }
    }

    /**
     * Show detailed status.
     */
    private function showStatus(): int
    {
        $modules = ModuleManager::getStatus();

        foreach ($modules as $module) {
            $this->line("Module: {$module['module_key']}");
            $this->line('  Name: '.($module['module_name'] ?? 'N/A'));
            $this->line('  Enabled: '.($module['enabled'] ? 'Yes' : 'No'));
            $this->line('  Loaded: '.($module['loaded'] ? 'Yes' : 'No'));
            $this->line('  Provider: '.($module['provider'] ?? 'N/A'));
            $this->line('  Version: '.($module['version'] ?? 'N/A'));
            $this->line('  Core: '.($module['is_core'] ? 'Yes' : 'No'));

            if (! empty($module['dependencies'])) {
                $this->line('  Dependencies: '.implode(', ', $module['dependencies']));
            }

            if ($module['has_unmet_dependencies']) {
                $this->line('  Unmet Dependencies: '.implode(', ', $module['unmet_dependencies']));
            }

            $this->newLine();
        }

        return 0;
    }

    /**
     * Sync modules from config.
     */
    private function syncFromConfig(): int
    {
        try {
            DatabaseModuleManager::syncModulesFromConfig();
            $this->info('Modules synced from config successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error syncing modules: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Clear module caches.
     */
    private function clearCache(): int
    {
        try {
            ModuleManager::clearAllCache();
            $this->info('Module caches cleared successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error clearing caches: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Install a module.
     */
    private function installModule(?string $module): int
    {
        if (! $module) {
            $this->error('Module key is required for install action.');

            return 1;
        }

        $name = $this->option('name') ?: ucwords(str_replace(['_', '-'], ' ', $module));
        $description = $this->option('description');
        $provider = $this->option('provider');
        $version = $this->option('module-version');
        $author = $this->option('author');
        $isCore = $this->option('core');
        $settings = [];

        if ($this->option('settings')) {
            $settings = json_decode($this->option('settings'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in settings option.');

                return 1;
            }
        }

        $moduleData = [
            'module_key' => $module,
            'module_name' => $name,
            'description' => $description,
            'provider_class' => $provider,
            'version' => $version,
            'author' => $author,
            'is_core' => $isCore,
            'settings' => $settings,
            'is_enabled' => false,
            'auto_register' => true,
            'dependencies' => [],
            'sort_order' => 0,
        ];

        try {
            $result = DatabaseModuleManager::installModule($moduleData);

            if ($result) {
                $this->info("Module '{$module}' installed successfully.");

                return 0;
            } else {
                $this->error("Failed to install module '{$module}'.");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error installing module '{$module}': ".$e->getMessage());

            return 1;
        }
    }

    /**
     * Uninstall a module.
     */
    private function uninstallModule(?string $module): int
    {
        if (! $module) {
            $this->error('Module key is required for uninstall action.');

            return 1;
        }

        if (! $this->confirm("Are you sure you want to uninstall module '{$module}'?")) {
            $this->info('Uninstall cancelled.');

            return 0;
        }

        try {
            $result = DatabaseModuleManager::uninstallModule($module);

            if ($result) {
                $this->info("Module '{$module}' uninstalled successfully.");

                return 0;
            } else {
                $this->error("Failed to uninstall module '{$module}'.");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error uninstalling module '{$module}': ".$e->getMessage());

            return 1;
        }
    }
}
