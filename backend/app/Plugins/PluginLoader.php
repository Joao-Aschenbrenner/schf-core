<?php

namespace App\Plugins;

use Illuminate\Support\Facades\Log;

class PluginLoader
{
    protected array $plugins = [];
    protected array $loaded = [];

    public function __construct()
    {
        $this->scanPlugins();
    }

    public function scanPlugins(): void
    {
        $pluginDirs = [
            app_path('Plugins/Connectors'),
        ];

        if (function_exists('plugin_path')) {
            $pluginDirs[] = plugin_path('');
        }

        foreach ($pluginDirs as $dir) {
            if (!is_dir($dir)) continue;

            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $pluginFile = $dir . '/' . $item;
                if (is_file($pluginFile) && str_ends_with($item, 'Plugin.php')) {
                    $this->registerPlugin($pluginFile);
                } elseif (is_dir($pluginFile)) {
                    $this->scanSubdirectory($pluginFile);
                }
            }
        }
    }

    protected function scanSubdirectory(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $pluginFile = $dir . '/' . $item;
            if (is_file($pluginFile) && str_ends_with($item, 'Plugin.php')) {
                $this->registerPlugin($pluginFile);
            }
        }
    }

    protected function registerPlugin(string $pluginFile): void
    {
        require_once $pluginFile;

        $content = file_get_contents($pluginFile);
        preg_match('/namespace\s+(.+?);/', $content, $nsMatch);
        preg_match('/class\s+(\w+)/', $content, $classMatch);

        if (!$nsMatch || !$classMatch) return;

        $namespace = $nsMatch[1];
        $className = $classMatch[1];
        $fullClass = $namespace . '\\' . $className;

        if (!class_exists($fullClass)) return;

        $reflection = new \ReflectionClass($fullClass);
        if ($reflection->isAbstract()) return;

        if ($reflection->implementsInterface(PluginInterface::class)) {
            $this->plugins[$fullClass] = [
                'class' => $fullClass,
                'file' => $pluginFile,
            ];
        }
    }

    public function getAvailablePlugins(): array
    {
        $available = [];
        foreach ($this->plugins as $class => $info) {
            try {
                $plugin = new $class();
                $available[] = [
                    'class' => $class,
                    'name' => $plugin->getName(),
                    'version' => $plugin->getVersion(),
                    'description' => $plugin->getDescription(),
                    'author' => $plugin->getAuthor(),
                    'enabled' => $plugin->isEnabled(),
                    'compatible' => $plugin->isCompatible($this->getCoreVersion()),
                ];
            } catch (\Exception $e) {
                Log::error("Erro ao instanciar plugin", ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
        return $available;
    }

    public function loadPlugin(string $className): ?PluginInterface
    {
        if (isset($this->loaded[$className])) {
            return $this->loaded[$className];
        }

        if (!isset($this->plugins[$className])) {
            Log::warning("Plugin não encontrado", ['class' => $className]);
            return null;
        }

        try {
            $plugin = new $className();
            $coreVersion = $this->getCoreVersion();

            if (!$plugin->isCompatible($coreVersion)) {
                Log::warning("Plugin incompatível", [
                    'name' => $plugin->getName(),
                    'core_version' => $coreVersion,
                ]);
                return null;
            }

            $this->loaded[$className] = $plugin;
            return $plugin;
        } catch (\Exception $e) {
            Log::error("Erro ao carregar plugin", ['class' => $className, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function loadAllEnabled(): array
    {
        $loaded = [];
        foreach ($this->plugins as $class => $info) {
            $plugin = $this->loadPlugin($class);
            if ($plugin && $plugin->isEnabled()) {
                $loaded[] = $plugin;
            }
        }
        return $loaded;
    }

    public function loadConnector(string $sourceType): ?ConnectorInterface
    {
        foreach ($this->plugins as $class => $info) {
            $plugin = $this->loadPlugin($class);
            if ($plugin instanceof ConnectorInterface && $plugin->getSourceType() === $sourceType) {
                return $plugin;
            }
        }
        return null;
    }

    protected function getCoreVersion(): string
    {
        return app(\App\Services\VersionChecker::class)->getCurrentVersion();
    }
}