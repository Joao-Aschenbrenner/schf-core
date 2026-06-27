<?php

namespace Tests\Unit;

use App\Services\VersionChecker;
use Tests\TestCase;

class VersionCheckerTest extends TestCase
{
    protected VersionChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(VersionChecker::class);
    }

    public function test_get_current_version_returns_string(): void
    {
        $version = $this->checker->getCurrentVersion();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    public function test_is_newer_returns_bool(): void
    {
        $this->assertTrue($this->checker->isNewer('1.0.0', '2.0.0'));
        $this->assertFalse($this->checker->isNewer('2.0.0', '1.0.0'));
        $this->assertFalse($this->checker->isNewer('1.0.0', '1.0.0'));
    }

    public function test_is_compatible_same_major(): void
    {
        $this->assertTrue($this->checker->isCompatible('1.0.0', '1.5.0'));
        $this->assertTrue($this->checker->isCompatible('1.0.0', '1.99.99'));
    }

    public function test_is_compatible_different_major(): void
    {
        $this->assertFalse($this->checker->isCompatible('1.0.0', '2.0.0'));
        $this->assertFalse($this->checker->isCompatible('2.0.0', '3.0.0'));
    }

    public function test_is_valid_semver(): void
    {
        $this->assertTrue($this->checker->isValid('1.0.0'));
        $this->assertTrue($this->checker->isValid('10.20.30'));
        $this->assertTrue($this->checker->isValid('0.0.1'));
    }

    public function test_is_invalid_version(): void
    {
        $this->assertFalse($this->checker->isValid('1.0'));
        $this->assertFalse($this->checker->isValid('v1.0.0'));
        $this->assertFalse($this->checker->isValid('abc'));
        $this->assertFalse($this->checker->isValid(''));
    }
}