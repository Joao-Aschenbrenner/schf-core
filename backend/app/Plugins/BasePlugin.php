<?php

namespace App\Plugins;

use Illuminate\Support\Facades\Log;

abstract class BasePlugin implements PluginInterface
{
    protected bool $enabled = false;
    protected string $pluginDir;

    public function __construct()
    {
        $this->pluginDir = $this->resolvePluginDir();
        $this->enabled = $this->loadState();
    }

    public function getMinCoreVersion(): string
    {
        return '1.0.0';
    }

    public function getMaxCoreVersion(): string
    {
        return '99.99.99';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function isCompatible(string $coreVersion): bool
    {
        if (version_compare($coreVersion, $this->getMinCoreVersion(), '<')) {
            return false;
        }
        if (version_compare($coreVersion, $this->getMaxCoreVersion(), '>')) {
            return false;
        }
        return true;
    }

    public function install(): bool
    {
        try {
            $this->onInstall();
            $this->saveState(true);
            Log::info("Plugin instalado", ['name' => $this->getName()]);
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao instalar plugin", ['name' => $this->getName(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function uninstall(): bool
    {
        try {
            $this->onUninstall();
            $this->saveState(false);
            Log::info("Plugin desinstalado", ['name' => $this->getName()]);
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao desinstalar plugin", ['name' => $this->getName(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function enable(): bool
    {
        $this->enabled = true;
        $this->saveState(true);
        Log::info("Plugin habilitado", ['name' => $this->getName()]);
        return true;
    }

    public function disable(): bool
    {
        $this->enabled = false;
        $this->saveState(false);
        Log::info("Plugin desabilitado", ['name' => $this->getName()]);
        return true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    abstract protected function onInstall(): void;

    abstract protected function onUninstall(): void;

    protected function resolvePluginDir(): string
    {
        return plugin_path($this->getName());
    }

    protected function loadState(): bool
    {
        $stateFile = $this->getStateFile();
        if (file_exists($stateFile)) {
            $state = json_decode(file_get_contents($stateFile), true);
            return $state['enabled'] ?? false;
        }
        return false;
    }

    protected function saveState(bool $enabled): void
    {
        $stateFile = $this->getStateFile();
        $dir = dirname($stateFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($stateFile, json_encode([
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'enabled' => $enabled,
            'updated_at' => now()->toISOString(),
        ], JSON_PRETTY_PRINT));
    }

    protected function getStateFile(): string
    {
        return storage_path("app/plugins/{$this->getName()}.json");
    }
}