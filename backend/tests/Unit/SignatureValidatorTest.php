<?php

namespace Tests\Unit;

use App\Services\SignatureValidator;
use Tests\TestCase;

class SignatureValidatorTest extends TestCase
{
    protected SignatureValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(SignatureValidator::class);
    }

    public function test_compute_sha256_returns_string(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'hello world');

        $hash = $this->validator->computeSha256($tempFile);

        $this->assertIsString($hash);
        $this->assertEquals('b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9', $hash);

        @unlink($tempFile);
    }

    public function test_compute_sha256_nonexistent_file_returns_null(): void
    {
        $result = $this->validator->computeSha256('/nonexistent/file.txt');
        $this->assertNull($result);
    }

    public function test_verify_sha256_correct_hash(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test data');

        $hash = $this->validator->computeSha256($tempFile);
        $this->assertTrue($this->validator->verifySha256($tempFile, $hash));

        @unlink($tempFile);
    }

    public function test_verify_sha256_incorrect_hash(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test data');

        $this->assertFalse($this->validator->verifySha256($tempFile, 'wrong_hash'));

        @unlink($tempFile);
    }

    public function test_has_signing_key_returns_bool(): void
    {
        $this->assertIsBool($this->validator->hasSigningKey());
    }
}