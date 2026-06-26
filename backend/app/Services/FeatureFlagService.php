<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class FeatureFlagService
{
    public function legacyModule(): bool
    {
        return Config::get('features.legacy_module', false);
    }

    public function multiOrganization(): bool
    {
        return Config::get('features.multi_organization', true);
    }

    public function setupWizard(): bool
    {
        return Config::get('features.setup_wizard', true);
    }

    public function autoUpdates(): bool
    {
        return Config::get('features.auto_updates', false);
    }

    public function desktopApp(): bool
    {
        return Config::get('features.desktop_app', false);
    }

    public function developerMode(): bool
    {
        return Config::get('features.developer_mode', false);
    }

    public function enabled(string $flag): bool
    {
        return (bool) Config::get("features.{$flag}", false);
    }

    public function all(): array
    {
        return Config::get('features', []);
    }

    public function isConfigured(): bool
    {
        return Config::get('app.is_configured', false);
    }
}
