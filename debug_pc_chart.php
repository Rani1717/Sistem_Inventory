<?php
require_once 'app/config/database.php';
$pdo = Database::getConnection();
$stmt = $pdo->query('SELECT division_label, division_code, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
$divs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Total divisions: ' . count($divs) . "\n";
foreach ($divs as $div) {
    $db = $div['inventory_db_name'];
    echo '--- ' . $div['division_label'] . ' [' . $db . ']' . "\n";
    try {
        $sql = "SELECT COUNT(*) AS total_pc,
            SUM(CASE WHEN UPPER(TRIM(COALESCE(status,''))) = 'AKTIF' THEN 1 ELSE 0 END) AS jml_aktif,
            SUM(CASE WHEN UPPER(TRIM(COALESCE(status,''))) = 'RUSAK' THEN 1 ELSE 0 END) AS jml_rusak
         FROM `$db`.pc";
        $r = $pdo->query($sql);
        $c = $r->fetch(PDO::FETCH_ASSOC);
        echo "  total={$c['total_pc']} aktif={$c['jml_aktif']} rusak={$c['jml_rusak']}\n";
    } catch (Throwable $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}
