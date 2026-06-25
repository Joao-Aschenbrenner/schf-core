<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('access-admin', function (User $user) {
            return $user->is_master === true || $user->hasRole('super_admin');
        });

        Gate::define('critical-actions', function (User $user) {
            return $user->is_master === true;
        });

        // Feature flags disponíveis globalmente - only run if database is configured
        $this->configureFeatureFlags();
    }

    protected function configureFeatureFlags(): void
    {
        // Skip if running in console (migrations, etc.) or if database not configured
        if ($this->app->runningInConsole()) {
            config(['app.is_configured' => true]);
            return;
        }

        // Check if database connection is configured
        try {
            $dbConfig = Config::get('database.connections.' . Config::get('database.default'));
            if (!$dbConfig || empty($dbConfig['database'])) {
                config(['app.is_configured' => true]);
                return;
            }

            // Test database connection
            DB::connection()->getPdo();

            if (Schema::hasTable('organizations')) {
                try {
                    $configured = Organization::count() > 0;
                    config(['app.is_configured' => $configured]);
                } catch (\Exception) {
                    config(['app.is_configured' => true]);
                }
            } else {
                config(['app.is_configured' => false]);
            }
        } catch (\Exception) {
            // Database not ready, default to configured
            config(['app.is_configured' => true]);
        }
    }
}