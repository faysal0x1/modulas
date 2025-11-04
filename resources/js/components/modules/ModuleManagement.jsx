import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Play,
    Square,
    Settings,
    RefreshCw,
    Trash2,
    Plus,
    Info,
    AlertTriangle,
    CheckCircle,
    XCircle
} from 'lucide-react';

const ModuleManagement = () => {
    const [modules, setModules] = useState([]);
    const [statistics, setStatistics] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedModule, setSelectedModule] = useState(null);
    const [showSettings, setShowSettings] = useState(false);

    useEffect(() => {
        fetchModules();
    }, []);

    const fetchModules = async () => {
        try {
            setLoading(true);
            const response = await fetch('/admin/modules');
            const data = await response.json();

            if (data.success) {
                setModules(data.data.modules);
                setStatistics(data.data.statistics);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to fetch modules');
        } finally {
            setLoading(false);
        }
    };

    const toggleModule = async (moduleKey, enabled) => {
        try {
            const action = enabled ? 'disable' : 'enable';
            const response = await fetch(`/admin/modules/${moduleKey}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                setModules(modules.map(module =>
                    module.module_key === moduleKey
                        ? { ...module, enabled: !enabled }
                        : module
                ));
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to toggle module');
        }
    };

    const syncFromConfig = async () => {
        try {
            const response = await fetch('/admin/modules/sync-config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                fetchModules();
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to sync modules');
        }
    };

    const clearCache = async () => {
        try {
            const response = await fetch('/admin/modules/clear-cache', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                fetchModules();
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to clear cache');
        }
    };

    const getStatusIcon = (module) => {
        if (module.enabled && module.loaded) {
            return <CheckCircle className="h-4 w-4 text-green-500" />;
        } else if (module.enabled && !module.loaded) {
            return <AlertTriangle className="h-4 w-4 text-yellow-500" />;
        } else {
            return <XCircle className="h-4 w-4 text-red-500" />;
        }
    };

    const getStatusBadge = (module) => {
        if (module.enabled && module.loaded) {
            return <Badge variant="default" className="bg-green-500">Active</Badge>;
        } else if (module.enabled && !module.loaded) {
            return <Badge variant="secondary" className="bg-yellow-500">Enabled</Badge>;
        } else {
            return <Badge variant="outline">Disabled</Badge>;
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <RefreshCw className="h-8 w-8 animate-spin" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold">Module Management</h1>
                <div className="flex space-x-2">
                    <Button onClick={syncFromConfig} variant="outline">
                        <RefreshCw className="h-4 w-4 mr-2" />
                        Sync Config
                    </Button>
                    <Button onClick={clearCache} variant="outline">
                        <Settings className="h-4 w-4 mr-2" />
                        Clear Cache
                    </Button>
                </div>
            </div>

            {error && (
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="text-2xl font-bold">{statistics.total || 0}</div>
                            <div className="text-sm text-muted-foreground">Total Modules</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="text-2xl font-bold text-green-500">{statistics.enabled || 0}</div>
                            <div className="text-sm text-muted-foreground">Enabled</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="text-2xl font-bold text-red-500">{statistics.disabled || 0}</div>
                            <div className="text-sm text-muted-foreground">Disabled</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="text-2xl font-bold text-blue-500">{statistics.loaded || 0}</div>
                            <div className="text-sm text-muted-foreground">Loaded</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Modules List */}
            <Tabs defaultValue="all" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="all">All Modules</TabsTrigger>
                    <TabsTrigger value="enabled">Enabled</TabsTrigger>
                    <TabsTrigger value="disabled">Disabled</TabsTrigger>
                    <TabsTrigger value="core">Core Modules</TabsTrigger>
                </TabsList>

                <TabsContent value="all" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {modules.map((module) => (
                            <Card key={module.module_key} className="relative">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            {getStatusIcon(module)}
                                            <CardTitle className="text-lg">{module.module_name}</CardTitle>
                                        </div>
                                        {getStatusBadge(module)}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {module.description || 'No description available'}
                                    </p>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex items-center justify-between text-sm">
                                        <span>Version: {module.version || 'N/A'}</span>
                                        <span>Author: {module.author || 'N/A'}</span>
                                    </div>

                                    {module.is_core && (
                                        <Badge variant="secondary" className="text-xs">Core Module</Badge>
                                    )}

                                    {module.has_unmet_dependencies && (
                                        <Alert variant="destructive" className="py-2">
                                            <AlertTriangle className="h-3 w-3" />
                                            <AlertDescription className="text-xs">
                                                Missing dependencies: {module.unmet_dependencies.join(', ')}
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <div className="flex space-x-2">
                                        <Switch
                                            checked={module.enabled}
                                            onCheckedChange={() => toggleModule(module.module_key, module.enabled)}
                                            disabled={module.is_core || module.has_unmet_dependencies}
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedModule(module);
                                                setShowSettings(true);
                                            }}
                                        >
                                            <Settings className="h-3 w-3 mr-1" />
                                            Settings
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </TabsContent>

                <TabsContent value="enabled" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {modules.filter(m => m.enabled).map((module) => (
                            <Card key={module.module_key} className="relative">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            {getStatusIcon(module)}
                                            <CardTitle className="text-lg">{module.module_name}</CardTitle>
                                        </div>
                                        {getStatusBadge(module)}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex space-x-2">
                                        <Switch
                                            checked={module.enabled}
                                            onCheckedChange={() => toggleModule(module.module_key, module.enabled)}
                                            disabled={module.is_core}
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedModule(module);
                                                setShowSettings(true);
                                            }}
                                        >
                                            <Settings className="h-3 w-3 mr-1" />
                                            Settings
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </TabsContent>

                <TabsContent value="disabled" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {modules.filter(m => !m.enabled).map((module) => (
                            <Card key={module.module_key} className="relative">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            {getStatusIcon(module)}
                                            <CardTitle className="text-lg">{module.module_name}</CardTitle>
                                        </div>
                                        {getStatusBadge(module)}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex space-x-2">
                                        <Switch
                                            checked={module.enabled}
                                            onCheckedChange={() => toggleModule(module.module_key, module.enabled)}
                                            disabled={module.is_core}
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedModule(module);
                                                setShowSettings(true);
                                            }}
                                        >
                                            <Settings className="h-3 w-3 mr-1" />
                                            Settings
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </TabsContent>

                <TabsContent value="core" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {modules.filter(m => m.is_core).map((module) => (
                            <Card key={module.module_key} className="relative">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            {getStatusIcon(module)}
                                            <CardTitle className="text-lg">{module.module_name}</CardTitle>
                                        </div>
                                        <Badge variant="secondary">Core Module</Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex space-x-2">
                                        <Switch
                                            checked={module.enabled}
                                            onCheckedChange={() => toggleModule(module.module_key, module.enabled)}
                                            disabled={true}
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setSelectedModule(module);
                                                setShowSettings(true);
                                            }}
                                        >
                                            <Settings className="h-3 w-3 mr-1" />
                                            Settings
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default ModuleManagement;
