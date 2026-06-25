<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
echo "basePath: " . $app->basePath() . PHP_EOL;
echo "runningInConsole: " . ($app->runningInConsole() ? "true" : "false") . PHP_EOL;