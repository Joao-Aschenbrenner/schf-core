<?php
// Test if .env is loaded
echo "Testing .env loading...\n";
echo "File exists: " . (file_exists(".env") ? "yes" : "no") . "\n";
echo "File readable: " . (is_readable(".env") ? "yes" : "no") . "\n";

$content = file_get_contents(".env");
echo "First 100 chars:\n" . substr($content, 0, 100) . "\n";

// Test Laravel's env() helper
echo "\nTesting Laravel env() helper...\n";
require "vendor/autoload.php";

$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "APP_KEY from config: " . config("app.key") . "\n";
echo "APP_KEY from env(): " . env("APP_KEY") . "\n";
echo "DB_PASSWORD from env(): " . env("DB_PASSWORD") . "\n";
echo "DB_PASSWORD from config: " . config("database.connections.mysql.password") . "\n";