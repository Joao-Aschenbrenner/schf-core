<?php
require "vendor/autoload.php";
$c = file_get_contents(".env");
echo "Length: " . strlen($c) . "\n";
echo "First 200 chars:\n" . substr($c, 0, 200) . "\n";
echo "\n--- Testing DotEnv directly ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo "APP_KEY from getenv: " . getenv("APP_KEY") . "\n";
echo "DB_PASSWORD from getenv: " . getenv("DB_PASSWORD") . "\n";