<?php
require 'app/models/Database.php';
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, request_id, ticket_no FROM it_support_request_history WHERE ticket_no LIKE 'TK%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "History records matching TK%:\n";
print_r($rows);
?>
