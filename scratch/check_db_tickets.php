<?php
require 'app/models/Database.php';
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, ticket_no, nama_pelapor, tanggal, jam, created_at FROM it_support_request ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
?>
