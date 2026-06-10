<?php
/**
 * CLI Background Sync Script for Google Form IT Support Requests
 * Can be run via task scheduler or cron:
 *   php sync_gform.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

// Load configurations and classes
require_once __DIR__ . '/app/models/Database.php';
require_once __DIR__ . '/app/controllers/PageController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$pdo = Database::getConnection();
if (!$pdo instanceof PDO) {
    echo "[" . date('Y-m-d H:i:s') . "] [ERROR] Database connection failed.\n";
    exit(1);
}

$controller = new PageController();
$controller->syncGoogleFormSubmissions($pdo, false);
