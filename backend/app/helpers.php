<?php

use App\Services\FeatureFlagService;

if (!function_exists('feature')) {
    function feature(?string $flag = null): mixed
    {
        $service = app(FeatureFlagService::class);

        if ($flag === null) {
            return $service;
        }

        return $service->enabled($flag);
    }
}
