<?php
echo "php://input: ";
var_dump(file_get_contents('php://input'));
echo "\nCONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT SET') . "\n";
echo "HTTP_CONTENT_TYPE: " . ($_SERVER['HTTP_CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT SET') . "\n";