<?php
require "vendor/autoload.php";
$c = file_get_contents(".env");
echo "Length: " . strlen($c) . "\n";
echo "First 200 chars:\n" . substr($c, 0, 200) . "\n";

echo "\n--- Testing DotEnv directly ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
echo "DotEnv created\n";
$dotenv->load();
echo "load() called\n";
echo "APP_KEY from getenv: '" . getenv("APP_KEY") . "'\n";
echo "DB_PASSWORD from getenv: '" . getenv("DB_PASSWORD") . "'\n";
echo "APP_ENV from getenv: '" . getenv("APP_ENV") . "'\n";