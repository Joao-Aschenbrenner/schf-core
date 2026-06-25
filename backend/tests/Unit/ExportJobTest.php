<?php

use App\Models\Operacional\ExportJob;

test('factory creates valid export job', function () {
    $job = ExportJob::factory()->create();

    expect($job)->toBeInstanceOf(ExportJob::class);
    expect($job->status)->toBe('pending');
    expect($job->type)->toBeIn(['csv', 'xlsx', 'pdf']);
});

test('export job scope pending', function () {
    ExportJob::factory()->count(2)->create(['status' => 'pending']);
    ExportJob::factory()->count(1)->create(['status' => 'completed']);

    expect(ExportJob::pending()->count())->toBe(2);
});

test('export job scope completed', function () {
    ExportJob::factory()->count(3)->create(['status' => 'completed']);
    ExportJob::factory()->count(1)->create(['status' => 'pending']);

    expect(ExportJob::completed()->count())->toBe(3);
});

test('export job scope failed', function () {
    ExportJob::factory()->count(1)->create(['status' => 'failed']);
    ExportJob::factory()->count(2)->create(['status' => 'completed']);

    expect(ExportJob::failed()->count())->toBe(1);
});

test('export job casts parameters as array', function () {
    $job = ExportJob::factory()->create(['parameters' => ['date_from' => '2025-01-01']]);

    expect($job->parameters)->toBeArray();
    expect($job->parameters['date_from'])->toBe('2025-01-01');
});
