<?php

require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(App\Http\Controllers\SetupWizardController::class);
$request = Illuminate\Http\Request::create("/api/setup/status", "GET");
$response = $controller->status();
echo "Response: " . $response->getContent() . PHP_EOL;
echo "Status: " . $response->getStatusCode() . PHP_EOL;