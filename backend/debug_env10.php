<?php
require "vendor/autoload.php";

echo "--- Parsing .env with DotEnv parser ---\n";
$parser = new Dotenv\Parser\Parser();
$parsed = $parser->parse(file_get_contents(".env"));
echo "Number of parsed entries: " . count($parsed) . "\n";
foreach ($parsed as $entry) {
    $name = $entry->getName();
    $value = $entry->getValue();
    echo "  Entry: $name = ";
    var_dump($value);
}