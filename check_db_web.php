<?php
header('Content-Type: text/plain');
require_once 'app/models/Database.php';

try {
    $pdo = Database::getConnection();
    
    // Get connection info
    $stmtDb = $pdo->query("SELECT DATABASE() as db, @@global.time_zone as tz, NOW() as now");
    $dbInfo = $stmtDb->fetch(PDO::FETCH_ASSOC);
    echo "=== Web Server Database Info ===\n";
    print_r($dbInfo);
    
    echo "\n=== Environment variables ===\n";
    echo "SPMT_DB_NAME: " . (getenv('SPMT_DB_NAME') ?: 'NOT SET') . "\n";
    echo "SPMT_DB_HOST: " . (getenv('SPMT_DB_HOST') ?: 'NOT SET') . "\n";
    
    echo "\n=== Server Utama Records for June 2026 ===\n";
    $stmt = $pdo->query("SELECT * FROM routine_monitoring WHERE item_id = 5 AND monitor_date BETWEEN '2026-06-01' AND '2026-06-30' ORDER BY monitor_date ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        print_r($row);
        echo "---------------------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
