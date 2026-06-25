<?php
$c = file_get_contents(".env");
echo "First 3 bytes: " . bin2hex(substr($c, 0, 3)) . PHP_EOL;
echo "File size: " . strlen($c) . PHP_EOL;