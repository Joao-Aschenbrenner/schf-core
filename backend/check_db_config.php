<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "DB Password: " . config('database.connections.mysql.password') . PHP_EOL;
echo "DB Host: " . config('database.connections.mysql.host') . PHP_EOL;
echo "DB Database: " . config('database.connections.mysql.database') . PHP_EOL;
echo "DB Username: " . config('database.connections.mysql.username') . PHP_EOL;