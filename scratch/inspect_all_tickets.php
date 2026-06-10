<?php
require 'app/models/Database.php';
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, ticket_no, tanggal FROM it_support_request ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
?>
