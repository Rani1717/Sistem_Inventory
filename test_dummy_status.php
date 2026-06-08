<?php
define('BASEPATH', true);
require_once __DIR__ . '/app/models/Database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($argv[1] ?? '');
$pdo = Database::getConnection();

if ($action === 'set') {
    if (file_exists(__DIR__ . '/dummy_pc_state.json')) {
        // Remove existing state first
        @unlink(__DIR__ . '/dummy_pc_state.json');
    }

    // Get active divisions
    $stmt = $pdo->query('SELECT division_label, division_code, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($divisions as $division) {
        $db = $division['inventory_db_name'];
        try {
            // Find a PC with AKTIF status and non-empty computer_name
            $pcStmt = $pdo->query("SELECT * FROM `$db`.pc WHERE UPPER(TRIM(status)) = 'AKTIF' AND computer_name IS NOT NULL AND TRIM(computer_name) <> '' LIMIT 1");
            $pcRow = $pcStmt ? $pcStmt->fetch(PDO::FETCH_ASSOC) : null;

            if ($pcRow) {
                $keyCol = 'computer_name';
                $keyValue = $pcRow['computer_name'];

                // Save state
                $state = [
                    'db' => $db,
                    'key_col' => $keyCol,
                    'key_val' => $keyValue,
                    'division_label' => $division['division_label'],
                    'pc_name' => $pcRow['nama_user'] ?? $pcRow['user'] ?? ($pcRow['nama_komputer'] ?? 'PC-Unknown'),
                    'original_status' => $pcRow['status']
                ];

                file_put_contents(__DIR__ . '/dummy_pc_state.json', json_encode($state));

                // Update to RUSAK
                $updateStmt = $pdo->prepare("UPDATE `$db`.pc SET status = 'RUSAK' WHERE `$keyCol` = :val");
                $updateStmt->execute(['val' => $keyValue]);

                echo json_encode([
                    'status' => 'success',
                    'message' => "PC dummy berhasil diset ke RUSAK!",
                    'detail' => [
                        'division' => $state['division_label'],
                        'pc_name' => $state['pc_name'],
                        'computer_name' => $keyValue,
                        'original_status' => $state['original_status']
                    ]
                ], JSON_PRETTY_PRINT);
                exit;
            }
        } catch (Throwable $e) {
            // Skip database errors and try next
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'No active PC found in any division to modify.']);
    exit;

} elseif ($action === 'revert') {
    $stateFile = __DIR__ . '/dummy_pc_state.json';
    if (!file_exists($stateFile)) {
        echo json_encode(['status' => 'error', 'message' => 'No dummy state found. Nothing to revert.']);
        exit;
    }

    $state = json_decode(file_get_contents($stateFile), true);
    $db = $state['db'];
    $keyCol = $state['key_col'];
    $keyValue = $state['key_val'];
    $originalStatus = $state['original_status'];

    try {
        $updateStmt = $pdo->prepare("UPDATE `$db`.pc SET status = :status WHERE `$keyCol` = :val");
        $updateStmt->execute(['status' => $originalStatus, 'val' => $keyValue]);

        if (file_exists($stateFile)) {
            unlink($stateFile);
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Status PC dummy berhasil dikembalikan ke {$originalStatus}!",
            'detail' => [
                'division' => $state['division_label'],
                'pc_name' => $state['pc_name'],
                'computer_name' => $keyValue
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengembalikan status PC: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'info', 'message' => 'Gunakan ?action=set untuk membuat data dummy rusak, atau ?action=revert untuk mengembalikannya.']);
}
