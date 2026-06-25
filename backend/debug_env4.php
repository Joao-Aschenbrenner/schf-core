<?php
require "vendor/autoload.php";

echo "--- Testing DotEnv with explicit path ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ".env");
$loaded = $dotenv->load();
echo "load() returned: " . ($loaded ? "true" : "false") . "\n";

echo "\n--- Checking $_ENV ---\n";
echo "APP_KEY in \$_ENV: " . ($_ENV["APP_KEY"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in \$_ENV: " . ($_ENV["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "APP_ENV in \$_ENV: " . ($_ENV["APP_ENV"] ?? "NOT SET") . "\n";

echo "\n--- Checking \$_SERVER ---\n";
echo "APP_KEY in \$_SERVER: " . ($_SERVER["APP_KEY"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in \$_SERVER: " . ($_SERVER["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "APP_ENV in \$_SERVER: " . ($_SERVER["APP_ENV"] ?? "NOT SET") . "\n";

echo "\n--- Checking getenv() ---\n";
echo "APP_KEY from getenv: '" . getenv("APP_KEY") . "'\n";
echo "DB_PASSWORD from getenv: '" . getenv("DB_PASSWORD") . "'\n";
echo "APP_ENV from getenv: '" . getenv("APP_ENV") . "'\n";

echo "\n--- Testing DotEnv->required() ---\n";
try {
    $dotenv->required(["APP_KEY", "DB_PASSWORD", "APP_ENV"]);
    echo "Required vars present\n";
} catch (Exception $e) {
    echo "Required vars missing: " . $e->getMessage() . "\n";
}