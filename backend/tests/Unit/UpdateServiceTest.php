<?php

namespace Tests\Unit;

use App\Services\UpdateService;
use Tests\TestCase;

class UpdateServiceTest extends TestCase
{
    protected UpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UpdateService::class);
    }

    public function test_check_returns_array(): void
    {
        $result = $this->service->check();
        $this->assertIsArray($result);
    }

    public function test_versions_returns_array(): void
    {
        $result = $this->service->versions();
        $this->assertIsArray($result);
    }

    public function test_get_changelog_returns_string(): void
    {
        $result = $this->service->getChangelog('1.0.0');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}