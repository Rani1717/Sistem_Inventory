<?php
// app/config/config.php

$baseUrl = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);
    $dir = ($dir === '\\' || $dir === '/') ? '/' : '/' . trim($dir, '/\\') . '/';
    $baseUrl = $protocol . $domainName . $dir;
} else {
    $baseUrl = getenv('SPMT_BASE_URL') ?: 'http://localhost/Sistem_Inventory/';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('SPMT_BASE_URL') ?: $baseUrl);
}

return [
    'base_url' => BASE_URL
];
