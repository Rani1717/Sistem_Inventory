<?php
header('Content-Type: text/plain');
echo "GD loaded: " . (extension_loaded('gd') ? "YES" : "NO") . "\n";
echo "Zip loaded: " . (extension_loaded('zip') ? "YES" : "NO") . "\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . PHP_SAPI . "\n";
