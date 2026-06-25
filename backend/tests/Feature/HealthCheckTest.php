<?php

use function Pest\Laravel\get;

it('returns ok status on health endpoint', function () {
    get('/api/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'system' => 'SCHF',
        ]);
});

