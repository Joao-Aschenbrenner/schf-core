<?php

namespace Tests\Unit;

use App\Services\ObservabilityService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ObservabilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        $this->app['config']->set('database.redis.client', 'predis');
    }

    public function test_health_check_returns_array(): void
    {
        $observability = app(ObservabilityService::class);
        $result = $observability->healthCheck();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('checks', $result);
    }

    public function test_health_check_has_database_check(): void
    {
        $observability = app(ObservabilityService::class);
        $result = $observability->healthCheck();
        $this->assertArrayHasKey('database', $result['checks']);
    }

    public function test_health_check_database_has_healthy_key(): void
    {
        $observability = app(ObservabilityService::class);
        $result = $observability->healthCheck();
        $this->assertArrayHasKey('healthy', $result['checks']['database']);
    }
}