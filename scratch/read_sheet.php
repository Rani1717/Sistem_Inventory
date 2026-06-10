<?php
require 'app/models/Database.php';
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'google_sheet_csv_url'");
$url = $stmt->fetchColumn();

echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$csvData = curl_exec($ch);
curl_close($ch);

if (!$csvData) {
    echo "FAILED to fetch CSV.\n";
    exit;
}

$lines = explode("\n", str_replace("\r", "", $csvData));
echo "CSV total lines: " . count($lines) . "\n\n";
foreach ($lines as $i => $line) {
    echo "Line $i: $line\n";
}
?>
