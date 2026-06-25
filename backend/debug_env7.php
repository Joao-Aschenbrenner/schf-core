<?php
require "vendor/autoload.php";

echo "--- Reading .env file directly ---\n";
$envContent = file_get_contents(".env");
echo "DB_PASSWORD line in .env:\n";
foreach (explode("\n", $envContent) as $line) {
    if (str_starts_with(trim($line), "DB_PASSWORD")) {
        echo "  $line\n";
    }
}

echo "\n--- Reading .env.example file directly ---\n";
$exampleContent = file_get_contents(".env.example");
echo "DB_PASSWORD line in .env.example:\n";
foreach (explode("\n", $exampleContent) as $line) {
    if (str_starts_with(trim($line), "DB_PASSWORD")) {
        echo "  $line\n";
    }
}

echo "\n--- Parsing .env with DotEnv parser ---\n";
$parser = new Dotenv\Parser\Parser();
$parsed = $parser->parse(file_get_contents(".env"));
echo "Parsed entries:\n";
foreach ($parsed as $entry) {
    if (str_starts_with($entry->getName(), "DB_") || str_starts_with($entry->getName(), "APP_")) {
        echo "  {$entry->getName()} = {$entry->getValue()}\n";
    }
}