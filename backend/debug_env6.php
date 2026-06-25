<?php
require "vendor/autoload.php";

echo "--- Testing DotEnv with explicit path and debugging ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ".env");

// Use reflection to check all properties
$reflection = new ReflectionClass($dotenv);
$properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
foreach ($properties as $prop) {
    $prop->setAccessible(true);
    $value = $prop->getValue($dotenv);
    echo "Property: {$prop->getName()} = ";
    var_dump($value);
}

$loaded = $dotenv->load();
echo "load() returned: " . ($loaded ? "true" : "false") . "\n";

echo "\n--- Checking \$_ENV after load ---\n";
echo "APP_KEY: " . ($_ENV["APP_KEY"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD: " . ($_ENV["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "APP_ENV: " . ($_ENV["APP_ENV"] ?? "NOT SET") . "\n";