<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$json = json_encode(['name' => 'Test', 'cnpj' => '12.345.678/0001-90', 'city' => 'Sao Paulo', 'state' => 'SP', 'email' => 'contato@hospital.com', 'phone' => '(11) 99999-9999', 'address' => 'Rua Teste, 123']);

$server = [
    'REQUEST_METHOD' => 'POST',
    'REQUEST_URI' => '/api/setup/organization',
    'CONTENT_TYPE' => 'application/json',
    'CONTENT_LENGTH' => strlen($json),
    'HTTP_CONTENT_TYPE' => 'application/json',
    'HTTP_CONTENT_LENGTH' => strlen($json),
];

$request = Illuminate\Http\Request::create('/api/setup/organization', 'POST', [], [], [], $server, $json);

echo "Request content: " . $request->getContent() . "\n";
echo "Request input: " . json_encode($request->all()) . "\n";
echo "Request method: " . $request->method() . "\n";
echo "Request header Content-Type: " . $request->header('Content-Type') . "\n";
echo "Request isJson: " . ($request->isJson() ? 'true' : 'false') . "\n";

$controller = app(App\Http\Controllers\SetupWizardController::class);
$response = $controller->createOrganization($request);
echo "\nController response: " . $response->getContent() . "\n";
echo "Controller status: " . $response->getStatusCode() . "\n";