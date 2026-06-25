<?php
$files = [
    'app/Http/Controllers/SetupWizardController.php',
    'app/Http/Controllers/AdminController.php',
    'app/Providers/AppServiceProvider.php',
    'routes/api.php',
];

foreach ($files as $file) {
    $c = file_get_contents($file);
    $hex = bin2hex(substr($c, 0, 3));
    echo "$file: $hex\n";
}