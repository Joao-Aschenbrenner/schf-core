<?php

namespace Tests\Unit;

use App\Services\Migration\ValidationEngine;
use Tests\TestCase;

class ValidationEngineTest extends TestCase
{
    protected ValidationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ValidationEngine::class);
    }

    public function test_validate_valid_data_returns_true(): void
    {
        $data = [
            ['name' => 'Test', 'value' => '123'],
        ];
        $rules = [
            'name' => ['required' => true, 'type' => 'string'],
            'value' => ['required' => true, 'type' => 'integer'],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertTrue($result['valid']);
        $this->assertEquals(1, $result['valid_rows']);
        $this->assertEquals(0, $result['invalid_rows']);
    }

    public function test_validate_missing_required_returns_error(): void
    {
        $data = [
            ['name' => '', 'value' => '123'],
        ];
        $rules = [
            'name' => ['required' => true],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertFalse($result['valid']);
        $this->assertEquals(1, $result['invalid_rows']);
    }

    public function test_validate_wrong_type_returns_error(): void
    {
        $data = [
            ['value' => 'not_a_number'],
        ];
        $rules = [
            'value' => ['type' => 'integer'],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }

    public function test_validate_max_length_truncates(): void
    {
        $data = [
            ['name' => 'A very long name that exceeds the limit'],
        ];
        $rules = [
            'name' => ['max_length' => 10],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertArrayHasKey(0, $result['warnings']);
        $this->assertEquals('A very lon', $result['validated'][0]['name']);
    }

    public function test_validate_pattern_rejects_invalid(): void
    {
        $data = [
            ['email' => 'not-an-email'],
        ];
        $rules = [
            'email' => ['pattern' => '/^[^@]+@[^@]+\.[^@]+$/'],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }

    public function test_validate_enum_rejects_invalid_value(): void
    {
        $data = [
            ['status' => 'invalid'],
        ];
        $rules = [
            'status' => ['enum' => ['active', 'inactive']],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }

    public function test_validate_valid_cnpj(): void
    {
        $data = [
            ['cnpj' => '11222333000181'],
        ];
        $rules = [
            'cnpj' => ['type' => 'cnpj'],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertTrue($result['valid']);
    }

    public function test_validate_invalid_cnpj(): void
    {
        $data = [
            ['cnpj' => '12345678000100'],
        ];
        $rules = [
            'cnpj' => ['type' => 'cnpj'],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }

    public function test_validate_valid_cpf(): void
    {
        $data = [
            ['cpf' => '52998224725'],
        ];
        $rules = [
            ['cpf' => ['type' => 'cpf']],
        ];

        $result = $this->engine->validate($data, $rules);
        $this->assertTrue($result['valid']);
    }

    public function test_validate_file_csv(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, "name,value\nTest,123\n");

        $rules = [
            'name' => ['required' => true],
            'value' => ['required' => true],
        ];

        $result = $this->engine->validateFile($tempFile, $rules, 'csv');
        $this->assertTrue($result['valid']);

        @unlink($tempFile);
    }

    public function test_validate_file_nonexistent_returns_error(): void
    {
        $result = $this->engine->validateFile('/nonexistent/file.csv', [], 'csv');
        $this->assertFalse($result['valid']);
    }
}