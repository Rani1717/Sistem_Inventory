<?php
require_once __DIR__ . '/app/models/Database.php';

try {
    $pdo = Database::getConnection();
    if (!$pdo instanceof PDO) {
        throw new Exception("Koneksi database gagal.");
    }
    
    $pdo->beginTransaction();
    
    // 1. Delete existing daily routine checklist logs associated with old GATE items
    echo "Cleaning up old GATE routine monitoring logs...\n";
    $pdo->exec("DELETE FROM routine_monitoring WHERE item_id IN (SELECT id FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ''), item_group)) = 'GATE')");
    
    // 2. Delete existing items from routine_monitoring_items
    echo "Cleaning up old GATE items...\n";
    $pdo->exec("DELETE FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ''), item_group)) = 'GATE'");
    
    // 3. Define the new items list
    $items = [
        // 1. Gate Peldam
        ['Gate Peldam - Koneksi Internet', 10],
        ['Gate Peldam - Tombol Manual Portal', 20],
        ['Gate Peldam - PC Gate Peldam', 30],
        ['Gate Peldam - Printer Gate Peldam', 40],
        ['Gate Peldam - Scanner Gate Peldam', 50],
        ['Gate Peldam - UPS Gate Peldam', 60],
        ['Gate Peldam - Aplikasi Ptos-M', 70],
        ['Gate Peldam - Sensor Gate In', 80],
        ['Gate Peldam - Portal Gate In', 90],
        ['Gate Peldam - Printer Gate In', 100],
        ['Gate Peldam - Kios K Gate In', 110],
        ['Gate Peldam - CCTV Gate In', 120],
        ['Gate Peldam - Scanner Gate In', 130],
        ['Gate Peldam - Sensor Gate Out', 140],
        ['Gate Peldam - Portal Gate Out', 150],
        ['Gate Peldam - Printer Gate Out', 160],
        ['Gate Peldam - Kios K Gate Out', 170],
        ['Gate Peldam - Scanner Gate Out', 180],
        ['Gate Peldam - CCTV Gate Out', 190],

        // 2. Gate Nusantara
        ['Gate Nusantara - Koneksi Internet', 200],
        ['Gate Nusantara - Tombol Manual Portal', 210],
        ['Gate Nusantara - PC Gate Nusantara', 220],
        ['Gate Nusantara - Printer Gate Nusantara', 230],
        ['Gate Nusantara - Scanner Gate Nusantara', 240],
        ['Gate Nusantara - UPS Gate Nusantara', 250],
        ['Gate Nusantara - Aplikasi Ptos-M', 260],
        ['Gate Nusantara - Sensor Gate In', 270],
        ['Gate Nusantara - Portal Gate In', 280],
        ['Gate Nusantara - Printer Gate In', 290],
        ['Gate Nusantara - Kios K Gate In', 300],
        ['Gate Nusantara - CCTV Gate In', 310],
        ['Gate Nusantara - Scanner Gate In', 320],
        ['Gate Nusantara - Sensor Gate Out', 330],
        ['Gate Nusantara - Portal Gate Out', 340],
        ['Gate Nusantara - Printer Gate Out', 350],
        ['Gate Nusantara - Kios K Gate Out', 360],
        ['Gate Nusantara - Scanner Gate Out', 370],
        ['Gate Nusantara - CCTV Gate Out', 380],

        // 3. Gate S01
        ['Gate S01 - Koneksi Internet', 400],
        ['Gate S01 - Tombol Manual Portal', 410],
        ['Gate S01 - PC Gate S01', 420],
        ['Gate S01 - Printer Gate S01', 430],
        ['Gate S01 - Scanner Gate S01', 440],
        ['Gate S01 - UPS Gate S01', 450],
        ['Gate S01 - Aplikasi Ptos-M', 460],
        ['Gate S01 - Sensor Gate In', 470],
        ['Gate S01 - Portal Gate In', 480],
        ['Gate S01 - Printer Gate In', 490],
        ['Gate S01 - Kios K Gate In', 500],
        ['Gate S01 - CCTV Gate In', 510],
        ['Gate S01 - Scanner Gate In', 520],
        ['Gate S01 - Sensor Gate Out', 530],
        ['Gate S01 - Portal Gate Out', 540],
        ['Gate S01 - Printer Gate Out', 550],
        ['Gate S01 - Kios K Gate Out', 560],
        ['Gate S01 - Scanner Gate Out', 570],
        ['Gate S01 - CCTV Gate Out', 580],

        // 4. Gate Roro
        ['Gate Roro - Running Text Pos-4', 600],
        ['Gate Roro - Koneksi Internet', 610],
        ['Gate Roro - Tombol Manual Portal', 620],
        ['Gate Roro - PC Gate Roro', 630],
        ['Gate Roro - Printer Gate Roro', 640],
        ['Gate Roro - Scanner Gate Roro', 650],
        ['Gate Roro - UPS Gate Roro', 660],
        ['Gate Roro - Aplikasi Ptos-R', 670],
        ['Gate Roro - Sensor Gate In Mobil', 680],
        ['Gate Roro - Portal Gate In Mobil', 690],
        ['Gate Roro - Printer Gate In Mobil', 700],
        ['Gate Roro - Kios K Gate In Mobil', 710],
        ['Gate Roro - CCTV Gate In Mobil', 720],
        ['Gate Roro - Scanner Gate In Mobil', 730],
        ['Gate Roro - Sensor Gate Out Mobil', 740],
        ['Gate Roro - Portal Gate Out Mobil', 750],
        ['Gate Roro - Printer Gate Out Mobil', 760],
        ['Gate Roro - Kios K Gate Out Mobil', 770],
        ['Gate Roro - Scanner Gate Out Mobil', 780],
        ['Gate Roro - CCTV Gate Out Mobil', 790],
        ['Gate Roro - Sensor Gate In Motor', 800],
        ['Gate Roro - Portal Gate In Motor', 810],
        ['Gate Roro - Printer Gate In Motor', 820],
        ['Gate Roro - Kios K Gate In Motor', 830],
        ['Gate Roro - CCTV Gate In Motor', 840],
        ['Gate Roro - Scanner Gate In Motor', 850],
        ['Gate Roro - Sensor Gate Out Motor', 860],
        ['Gate Roro - Portal Gate Out Motor', 870],
        ['Gate Roro - Printer Gate Out Motor', 880],
        ['Gate Roro - Kios K Gate Out Motor', 890],
        ['Gate Roro - Scanner Gate Out Motor', 900],
        ['Gate Roro - CCTV Gate Out Motor', 910],

        // 5. Gate JT Nusantara
        ['Gate JT Nusantara - Koneksi Internet', 1000],
        ['Gate JT Nusantara - Tombol Manual Portal', 1010],
        ['Gate JT Nusantara - PC JT Nusantara', 1020],
        ['Gate JT Nusantara - Printer JT Nusantara', 1030],
        ['Gate JT Nusantara - Scanner JT Nusantara', 1040],
        ['Gate JT Nusantara - UPS JT Nusantara', 1050],
        ['Gate JT Nusantara - Aplikasi Ptos-M', 1060],
        ['Gate JT Nusantara - Sensor Gate', 1070],
        ['Gate JT Nusantara - Portal Gate', 1080],
        ['Gate JT Nusantara - Printer Gate', 1090],
        ['Gate JT Nusantara - Kios K Gate', 1100],

        // 6. Gate JT Samudera
        ['Gate JT Samudera - Koneksi Internet', 1200],
        ['Gate JT Samudera - PC JT Samudera', 1210],
        ['Gate JT Samudera - Printer JT Samudera', 1220],
        ['Gate JT Samudera - Scanner JT Samudera', 1230],
        ['Gate JT Samudera - UPS JT Samudera', 1240],
        ['Gate JT Samudera - Aplikasi Ptos-M', 1250],
    ];
    
    echo "Inserting new GATE items...\n";
    $stmt = $pdo->prepare("INSERT INTO routine_monitoring_items (item_group, category_field, item_name, sort_order, is_active) VALUES ('GATE', 'GATE', :name, :sort_order, 1)");
    
    foreach ($items as $item) {
        $stmt->execute([
            'name' => $item[0],
            'sort_order' => $item[1]
        ]);
    }
    
    $pdo->commit();
    echo "Successfully populated " . count($items) . " new GATE items!\n";
    
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error migrating: " . $e->getMessage() . "\n";
    exit(1);
}
