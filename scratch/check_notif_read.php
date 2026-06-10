<?php
require 'app/models/Database.php';
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, ticket_no, nama_pelapor, status, notification_read_at FROM it_support_request WHERE id >= 20 ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
?>
