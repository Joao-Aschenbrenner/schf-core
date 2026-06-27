<?php

namespace Tests\Unit;

use App\Services\AuditService;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    protected AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audit = app(AuditService::class);
    }

    public function test_log_system_event_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $this->audit->logSystemEvent('test_event', ['key' => 'value']);
    }
}