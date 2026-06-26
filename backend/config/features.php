<?php

return [
    'legacy_module' => env('FEATURE_LEGACY_MODULE', false),
    'multi_organization' => env('FEATURE_MULTI_ORGANIZATION', true),
    'setup_wizard' => env('FEATURE_SETUP_WIZARD', true),
    'auto_updates' => env('FEATURE_AUTO_UPDATES', false),
    'desktop_app' => env('FEATURE_DESKTOP_APP', false),
    'developer_mode' => env('FEATURE_DEVELOPER_MODE', false),
];