<?php
require "vendor/autoload.php";

echo "--- Parsing .env with DotEnv parser ---\n";
$parser = new Dotenv\Parser\Parser();
$parsed = $parser->parse(file_get_contents(".env"));
echo "Number of parsed entries: " . count($parsed) . "\n";
foreach ($parsed as $entry) {
    $name = $entry->getName();
    $value = $entry->getValue();
    if ($value instanceof \PhpOption\Some) {
        $value = $value->get();
    }
    if (str_starts_with($name, "DB_") || str_starts_with($name, "APP_")) {
        echo "  $name = $value\n";
    }
}

echo "\n--- All parsed entries ---\n";
foreach ($parsed as $entry) {
    $name = $entry->getName();
    $value = $entry->getValue();
    if ($value instanceof \PhpOption\Some) {
        $value = $value->get();
    }
    echo "  $name = $value\n";
}