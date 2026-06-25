<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/test', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Test']));
echo 'Content: ' . $request->getContent() . PHP_EOL;
echo 'Input name: ' . $request->input('name') . PHP_EOL;
echo 'All input: ' . json_encode($request->all()) . PHP_EOL;