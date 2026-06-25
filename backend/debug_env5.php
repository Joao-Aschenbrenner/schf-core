<?php
require "vendor/autoload.php";

echo "--- Testing DotEnv with explicit path and debugging ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ".env");

// Use reflection to check internal state
$reflection = new ReflectionClass($dotenv);
$property = $reflection->getProperty('file');
$property->setAccessible(true);
$file = $property->getValue($dotenv);
echo "DotEnv file property: " . $file . "\n";

$property2 = $reflection->getProperty('path');
$property2->setAccessible(true);
$path = $property2->getValue($dotenv);
echo "DotEnv path property: " . $path . "\n";

$loaded = $dotenv->load();
echo "load() returned: " . ($loaded ? "true" : "false") . "\n";

echo "\n--- Checking \$_ENV after load ---\n";
echo "APP_KEY: " . ($_ENV["APP_KEY"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD: " . ($_ENV["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "APP_ENV: " . ($_ENV["APP_ENV"] ?? "NOT SET") . "\n";

echo "\n--- Checking if .env.example values are in \$_ENV ---\n";
if (isset($_ENV["DB_PASSWORD"]) && $_ENV["DB_PASSWORD"] === "change_me_in_production") {
    echo "WARNING: DB_PASSWORD has .env.example value!\n";
}