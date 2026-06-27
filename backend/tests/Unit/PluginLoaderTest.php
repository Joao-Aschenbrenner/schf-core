<?php

namespace Tests\Unit;

use App\Plugins\PluginLoader;
use Tests\TestCase;

class PluginLoaderTest extends TestCase
{
    public function test_get_available_plugins_returns_array(): void
    {
        $loader = new PluginLoader();
        $result = $loader->getAvailablePlugins();
        $this->assertIsArray($result);
    }

    public function test_load_plugin_nonexistent_returns_null(): void
    {
        $loader = new PluginLoader();
        $result = $loader->loadPlugin('Nonexistent\\Plugin\\Class');
        $this->assertNull($result);
    }

    public function test_load_all_enabled_returns_array(): void
    {
        $loader = new PluginLoader();
        $result = $loader->loadAllEnabled();
        $this->assertIsArray($result);
    }

    public function test_load_connector_nonexistent_returns_null(): void
    {
        $loader = new PluginLoader();
        $result = $loader->loadConnector('nonexistent_source');
        $this->assertNull($result);
    }
}