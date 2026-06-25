<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/setup/organization', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test']));
echo "Request content: " . $request->getContent() . "\n";
echo "Request input: " . json_encode($request->all()) . "\n";
echo "Request method: " . $request->method() . "\n";
echo "Request content type: " . $request->getContentType() . "\n";