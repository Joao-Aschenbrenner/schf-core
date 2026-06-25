<?php
require "vendor/autoload.php";

echo "--- Testing full DotEnv flow ---\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ".env");
$dotenv->load();

echo "After load():\n";
echo "DB_PASSWORD in \$_ENV: " . ($_ENV["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in \$_SERVER: " . ($_SERVER["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in getenv: " . getenv("DB_PASSWORD") . "\n";

echo "\n--- Checking repository ---\n";
$reflection = new ReflectionClass($dotenv);
$repoProp = $reflection->getProperty('repository');
$repoProp->setAccessible(true);
$repo = $repoProp->getValue($dotenv);

echo "Repository type: " . get_class($repo) . "\n";

// Check if values are in repository
$readerProp = new ReflectionProperty($repo, 'reader');
$readerProp->setAccessible(true);
$reader = $readerProp->getValue($repo);
echo "Reader type: " . get_class($reader) . "\n";

// Try to read from repository
try {
    $dbPass = $repo->get("DB_PASSWORD");
    echo "DB_PASSWORD from repo: " . $dbPass . "\n";
} catch (Exception $e) {
    echo "Error reading from repo: " . $e->getMessage() . "\n";
}