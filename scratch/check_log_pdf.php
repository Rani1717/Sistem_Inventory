<?php
require_once __DIR__ . '/../app/models/Database.php';
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT id, log_no, no_po, dokumen_po FROM log_barang");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
