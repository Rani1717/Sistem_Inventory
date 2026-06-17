<?php
/**
 * Script Diagnostik Koneksi Google Sheets CSV
 * Jalankan file ini di browser: http://localhost/Sistem_Inventory/test_gsheet.php
 * Atau lewat terminal: php test_gsheet.php
 */
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/app/models/Database.php';

echo "=== DIAGNOSTIK KONEKSI GOOGLE SHEETS CSV ===\n\n";

try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    die("ERROR: Gagal terhubung ke database. " . $e->getMessage() . "\n");
}

// 1. Ambil URL dari database
$stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = "google_sheet_csv_url" LIMIT 1');
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$url = $row ? $row['setting_value'] : '';

if (empty($url)) {
    die("ERROR: URL Google Sheet belum dikonfigurasi di database/pengaturan.\n");
}

echo "1. URL yang terdaftar di database:\n";
echo "   \"" . $url . "\"\n\n";

// 2. Cek karakter tersembunyi / spasi
echo "2. Pengecekan Karakter URL:\n";
$len = strlen($url);
$hasHidden = false;
$hexRepresentation = '';
for ($i = 0; $i < $len; $i++) {
    $char = $url[$i];
    $ord = ord($char);
    $hexRepresentation .= sprintf("%02X ", $ord);
    if ($ord < 32 || $ord > 126) {
        $hasHidden = true;
    }
}

echo "   - Panjang string: " . $len . " karakter\n";
if ($hasHidden) {
    echo "   - [PERINGATAN] Ditemukan karakter non-printable / tersembunyi di dalam URL!\n";
    echo "     Silakan hapus seluruh isi textbox URL di menu pengaturan dan ketik ulang secara manual.\n";
} else {
    echo "   - Karakter URL bersih (tidak ada karakter tersembunyi).\n";
}
echo "   - Hex URL: " . trim($hexRepresentation) . "\n\n";

// 3. Test Koneksi via cURL
echo "3. Mencoba mengambil data via cURL:\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // Track headers to see redirect destination
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $errNum = curl_errno($ch);
    $errMsg = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if ($errNum !== 0) {
        echo "   - [GAGAL] cURL error (code $errNum): $errMsg\n";
    } else {
        echo "   - HTTP Status Code: $httpCode\n";
        if ($redirectUrl) {
            echo "   - Redirected to: $redirectUrl\n";
        }
        
        // Cek apakah response berisi HTML (kemungkinan dialihkan ke captive portal / halaman login)
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        
        if (stripos($body, '<!DOCTYPE html>') !== false || stripos($body, '<html') !== false) {
            echo "   - [PERINGATAN] Respons yang didapat adalah halaman HTML/Web, bukan data CSV!\n";
            echo "     Kemungkinan besar request Anda dialihkan oleh proxy jaringan/firewall ke halaman login/blokir.\n";
            
            // Tampilkan sedikit snippet HTML title untuk info
            if (preg_match('/<title>(.*?)<\/title>/is', $body, $titleMatches)) {
                echo "     Judul halaman pengalihan: \"" . trim($titleMatches[1]) . "\"\n";
            }
        } else {
            echo "   - Berhasil mendapatkan data (tipe non-HTML).\n";
            echo "     Snippet data (50 karakter pertama): \"" . substr(trim($body), 0, 50) . "...\"\n";
        }
    }
} else {
    echo "   - Extension cURL tidak aktif di PHP.\n";
}
echo "\n";

// 4. Test Koneksi via file_get_contents
echo "4. Mencoba mengambil data via file_get_contents:\n";
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        "timeout" => 15,
        "ignore_errors" => true
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
];
$context = stream_context_create($opts);

if (function_exists('error_clear_last')) {
    @error_clear_last();
}

$data = @file_get_contents($url, false, $context);

if ($data !== false) {
    if (isset($http_response_header) && count($http_response_header) > 0) {
        echo "   - HTTP Response Headers:\n";
        foreach ($http_response_header as $headerLine) {
            echo "     * " . $headerLine . "\n";
        }
    } else {
        echo "   - Berhasil mengambil data, tetapi header HTTP tidak ditemukan.\n";
    }
} else {
    $lastError = error_get_last();
    $msg = $lastError ? $lastError['message'] : 'Error tidak diketahui';
    echo "   - [GAGAL] file_get_contents error: $msg\n";
}

echo "\n=== DIAGNOSTIK SELESAI ===\n";
