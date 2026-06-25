<?php
require "vendor/autoload.php";

echo "--- Testing Loader with repository ---\n";
$parser = new Dotenv\Parser\Parser();
$parsed = $parser->parse(file_get_contents(".env"));

$repository = new Dotenv\Repository\AdapterRepository(
    new Dotenv\Repository\Adapter\MultiReader([
        new Dotenv\Repository\Adapter\ServerConstAdapter(),
        new Dotenv\Repository\Adapter\EnvConstAdapter(),
    ]),
    new Dotenv\Repository\Adapter\ImmutableWriter(
        new Dotenv\Repository\Adapter\MultiWriter([
            new Dotenv\Repository\Adapter\ServerConstAdapter(),
            new Dotenv\Repository\Adapter\EnvConstAdapter(),
        ])
    )
);

$loader = new Dotenv\Loader\Loader();
foreach ($parsed as $entry) {
    $name = $entry->getName();
    $value = $entry->getValue();
    if ($value instanceof \PhpOption\Some) {
        $value = $value->get();
    }
    if ($value instanceof \Dotenv\Parser\Value) {
        $value = $value->getChars();
    }
    $loader->load($repository, $name, $value);
}

echo "After loading:\n";
echo "DB_PASSWORD in \$_ENV: " . ($_ENV["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in \$_SERVER: " . ($_SERVER["DB_PASSWORD"] ?? "NOT SET") . "\n";
echo "DB_PASSWORD in getenv: " . getenv("DB_PASSWORD") . "\n";