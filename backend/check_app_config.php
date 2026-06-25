<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "APP_KEY: " . config("app.key") . PHP_EOL;
echo "APP_DEBUG: " . (config("app.debug") ? "true" : "false") . PHP_EOL;
echo "APP_ENV: " . config("app.env") . PHP_EOL;