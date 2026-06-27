<?php

namespace Tests\Unit;

use App\Services\FeatureFlagService;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    protected FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);
    }

    public function test_legacy_module_returns_bool(): void
    {
        $result = $this->service->legacyModule();
        $this->assertIsBool($result);
    }

    public function test_multi_organization_returns_bool(): void
    {
        $result = $this->service->multiOrganization();
        $this->assertIsBool($result);
    }

    public function test_setup_wizard_returns_bool(): void
    {
        $result = $this->service->setupWizard();
        $this->assertIsBool($result);
    }

    public function test_auto_updates_returns_bool(): void
    {
        $result = $this->service->autoUpdates();
        $this->assertIsBool($result);
    }

    public function test_desktop_app_returns_bool(): void
    {
        $result = $this->service->desktopApp();
        $this->assertIsBool($result);
    }

    public function test_developer_mode_returns_bool(): void
    {
        $result = $this->service->developerMode();
        $this->assertIsBool($result);
    }

    public function test_enabled_returns_bool_for_any_flag(): void
    {
        $this->assertIsBool($this->service->enabled('legacy_module'));
        $this->assertIsBool($this->service->enabled('nonexistent_flag'));
    }

    public function test_all_returns_array(): void
    {
        $result = $this->service->all();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('multi_organization', $result);
    }

    public function test_is_configured_returns_bool(): void
    {
        $result = $this->service->isConfigured();
        $this->assertIsBool($result);
    }
}