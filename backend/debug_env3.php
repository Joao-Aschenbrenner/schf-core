<?php
require "vendor/autoload.php";

echo "Current dir: " . __DIR__ . "\n";
echo "Files in dir:\n";
foreach (scandir(__DIR__) as $f) {
    if (str_starts_with($f, ".env")) {
        echo "  $f\n";
    }
}

echo "\n--- Testing DotEnv with explicit path ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ".env");
echo "DotEnv created with explicit .env\n";
$loaded = $dotenv->load();
echo "load() returned: " . ($loaded ? "true" : "false") . "\n";
echo "APP_KEY from getenv: '" . getenv("APP_KEY") . "'\n";
echo "DB_PASSWORD from getenv: '" . getenv("DB_PASSWORD") . "'\n";
echo "APP_ENV from getenv: '" . getenv("APP_ENV") . "'\n";

echo "\n--- Checking if .env.example is being loaded ---\n";
if (file_exists(__DIR__ . "/.env.example")) {
    echo ".env.example exists\n";
    $c = file_get_contents(__DIR__ . "/.env.example");
    echo "First 100 chars of .env.example:\n" . substr($c, 0, 100) . "\n";
} else {
    echo ".env.example does not exist\n";
}