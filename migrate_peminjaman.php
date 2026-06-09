<?php
/**
 * Migration Script - Peminjaman Laptop
 * File ini digunakan untuk membuat tabel peminjaman_laptop di database utama SPMT
 * dan melakukan migrasi 82 data peminjaman historis dari sub-project lama.
 */
require_once __DIR__ . '/app/models/Database.php';

$title = 'Database Migration - Peminjaman Laptop';
$status = 'info';
$message = '';
$details = '';

try {
    $pdo = Database::getConnection();
    if (!$pdo instanceof PDO) {
        throw new Exception("Koneksi database utama SPMT gagal didapatkan.");
    }
    
    // 1. Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS `peminjaman_laptop` (
        `id`                   INT(11) NOT NULL AUTO_INCREMENT,
        `nama_barang`          VARCHAR(100) NOT NULL,
        `merk_barang`          VARCHAR(100) NOT NULL,
        `nama_peminjam`        VARCHAR(100) NOT NULL,
        `tanggal_peminjaman`   DATE NOT NULL,
        `bukti_peminjaman`     VARCHAR(255) DEFAULT NULL,
        `tanggal_pengembalian` DATE DEFAULT NULL,
        `bukti_pengembalian`   VARCHAR(255) DEFAULT NULL,
        `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 2. Cek apakah sudah ada data
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `peminjaman_laptop`")->fetchColumn();
    
    if ($count === 0) {
        // Data 82 record histori
        $insertQuery = "INSERT INTO `peminjaman_laptop` (`id`, `nama_barang`, `merk_barang`, `nama_peminjam`, `tanggal_peminjaman`, `bukti_peminjaman`, `tanggal_pengembalian`, `bukti_pengembalian`) VALUES
        (30, 'Laptop', 'ASUS', 'Guntur', '2025-02-26', '67beb79b51957.png', '2025-02-26', '67beee6e13c33.png'),
        (31, 'Laptop', 'lenovo', 'ninda', '2025-02-26', '67bec8ade2cff.png', '2025-02-26', '67beeaa7f0e88.png'),
        (32, 'Laptop', 'ASUS', 'Guntur', '2025-02-27', '67bfef9312ecd.png', '2025-03-05', '67c7f7a55f7b1.png'),
        (33, 'Laptop', 'lenovo', 'dina paramita', '2025-03-05', '67c7c61523e0e.png', '2025-03-06', '67c8f5a4b05f3.png'),
        (34, 'Laptop', 'ASUS', 'ninda', '2025-03-06', '67c8fc3d0fbd1.png', '2025-03-06', '67c91dd2e127c.png'),
        (35, 'Laptop', 'ASUS', 'Rifqi', '2025-03-06', '67c974f1650b2.png', '2025-04-21', '6805f31694789.png'),
        (36, 'Laptop', 'lenovo', 'Guntur', '2025-03-24', '67e0b8c4ca22a.png', '2025-03-24', '67e108753fa11.png'),
        (37, 'Laptop', 'ASUS', 'Raras', '2025-03-26', '67e36bd140ebf.png', '2025-03-27', '67e512a70703f.png'),
        (38, 'Laptop', 'lenovo', 'dina paramita', '2025-03-27', '67e4f4e691d9b.png', '2025-04-08', '67f4a2f8b38e5.png'),
        (39, 'desi', 'lenovo', 'Desi', '2025-04-14', '67fcafd08e631.png', '2025-04-14', '67fcd43f97f5c.png'),
        (40, 'laptop', 'lenovo', 'ninda', '2025-04-15', '67fe1c124ab20.png', '2025-04-15', '67fe347a13965.png'),
        (41, 'laptop', 'asus vivobook', 'Guntur', '2025-04-17', '680059fbd7a61.png', '2025-04-21', '6805eff814daa.png'),
        (42, 'Laptop', 'Asus', 'Asep', '2025-04-21', '6805a111588ec.png', '2025-04-21', '6805f00697398.png'),
        (43, 'laptop', 'asus vivobook', 'Asep', '2025-04-21', '6805f345a3d28.png', '2025-05-16', '68269bf83d15a.png'),
        (44, 'Microphone', 'Saramonic', 'Raras', '2025-04-22', '6806fed866f3a.png', '2025-04-22', '68076b3cd48b7.png'),
        (45, 'laptop', 'ASUS', 'Rifqi', '2025-04-25', '680b66d3e27c1.png', '2025-04-28', '680f58152613e.png'),
        (46, 'laptop', 'ASUS zenbook 01', 'Guntur', '2025-04-29', '681071aca85ca.png', '2025-04-30', '68119d775484f.png'),
        (47, 'Laptop', 'ASUS zenbook 01', 'Guntur', '2025-04-30', '6811dab317638.png', '2025-04-30', '6811e1a4d78c4.png'),
        (48, 'laptop', 'lenovo', 'Rifqi', '2025-05-02', '681493cc235f5.png', '2025-05-05', '6818164ab48f0.png'),
        (49, 'Microphone', 'Saramonic', 'Bagus ', '2025-05-09', '681d65da79e8b.png', '2025-05-09', '681d779117059.png'),
        (50, 'laptop', 'ASUS', 'RICHARD', '2025-05-09', '681d7c0fcd678.png', NULL, NULL),
        (51, 'laptop', 'ASUS', 'INKE ', '2025-05-09', '681daa682a5e0.png', '2025-05-14', '68246ac7c28cd.png'),
        (52, 'laptop', 'ASUS', 'Rifqi', '2025-05-09', '681dc81979707.png', '2025-06-02', '683cfcfea8d7b.png'),
        (53, 'Laptop', 'lenovo', 'Liya Widiyanti', '2025-05-15', '6825b46a392d9.png', '2025-05-16', '6827061fb3687.png'),
        (54, 'laptop', 'zenbook 01', 'ninda', '2025-05-16', '682690365e810.png', '2025-05-16', NULL),
        (55, 'laptop', 'lenovo', 'dina paramita', '2025-05-19', '682a862e8c544.png', '2025-05-19', '682a946baeaea.png'),
        (56, 'laptop', 'ASUS', 'desiana', '2025-05-19', '682ad20abd82e.png', '2025-05-19', '682af32289815.png'),
        (57, 'Laptop', 'ASUS', 'michellle', '2025-05-21', '682d27f5ef276.png', '2025-05-21', '682d7e767e43e.png'),
        (58, 'Laptop', 'ASUS', 'Guntur', '2025-06-02', '683cfced7a496.png', '2025-06-02', '683d72921ef03.png'),
        (59, 'Laptop', 'dell', 'bunga', '2025-06-02', '683d3ebb04b68.png', '2025-06-02', '683d72d4349ff.png'),
        (60, 'camera', 'Logi', 'bunga', '2025-06-02', '683d3edfb777b.png', '2025-06-02', '683d72c08bd45.png'),
        (61, 'Mouse', 'dell', 'Raras', '2025-06-03', '683e6720cef83.png', NULL, NULL),
        (62, 'Laptop', 'asus vivobook', 'Rifqi', '2025-06-04', '68400c96e649b.png', '2025-06-30', '686269f579525.png'),
        (63, 'Laptop', 'ASUS', 'mbak bunga', '2025-06-19', '685364d02400b.png', '2025-06-19', '6853841fa9670.png'),
        (64, 'proyektor', 'epson', 'mbak bunga', '2025-06-19', '685364f51175a.png', '2025-06-19', '6853840a66773.png'),
        (65, 'laptop', 'asus', 'Guntur', '2025-07-01', '68633a8aea582.png', '2025-07-01', '686390f96a46e.png'),
        (66, 'laptop', 'asus', 'rifqi', '2025-07-01', '6863c0cf8253f.png', '2025-07-21', '687e17e2cbfd9.png'),
        (67, 'laptop', 'lenovo', 'Guntur', '2025-07-02', '6864b578f179c.png', '2025-07-02', '68650217b37d4.png'),
        (68, 'laptop', 'lenovo', 'Guntur', '2025-07-03', '6866215314ccd.png', '2025-07-03', '686655ff0ec45.png'),
        (69, 'laptop', 'asus', 'Guntur', '2025-07-04', '686746dcb984f.png', '2025-07-04', '6867a33408dbc.png'),
        (70, 'SSD exernal', 'WD', 'Rifa', '2025-07-14', '6874b8cfb28fc.png', NULL, NULL),
        (71, 'laptop', 'asus zenbook', 'guntur', '2025-07-14', '6874c16dbd6a9.png', '2025-07-15', '687620c792bca.png'),
        (72, 'laptop', 'asus 02', 'guntur', '2025-07-16', '687776cf2b0f3.png', '2025-07-18', '687a1b8dea022.png'),
        (73, 'laptop', 'lenovo subreg', 'INKE (Icang)', '2025-07-18', '687a19580379a.png', '2025-07-21', '687d8e0e22eaa.png'),
        (74, 'laptop', 'ASUS 02', 'Ninda', '2025-07-21', '687d9cb8118d4.png', '2025-07-31', '688aede282fc7.png'),
        (75, 'laptop ', 'asus vivobook03', 'Rifa', '2025-07-23', '68809038e92e9.png', '2025-07-23', '6880b34fe9c09.png'),
        (76, 'laptop', 'DELL', 'INKE', '2025-07-25', '6883568f878ce.png', '2025-07-29', '68882bb253100.png'),
        (77, 'laptop', 'asus 02', 'Ninda', '2025-07-28', '6886e1ec427b7.png', '2025-07-31', '688aedf1cf02a.png'),
        (78, 'laptop', 'lenovo subreg', 'guntur', '2025-07-28', '6886e419bd879.png', '2025-07-28', '68874aac66cde.png'),
        (79, 'laptop', 'asus vivobook03', 'Ninda', '2025-07-31', '688adccb306d8.png', '2025-07-31', '688aee00e40d3.png'),
        (80, 'laptop', 'asus vivobook03', 'Ninda', '2025-07-31', '688aee98578d5.png', '2025-08-07', '6893f93e43e0f.png'),
        (81, 'laptop', 'DELL', 'rifki', '2025-08-01', '688c984253b2d.png', '0000-00-00', NULL),
        (82, 'laptop ', 'DELL', 'guntur', '2025-08-07', '6893fdfab120b.png', '2025-08-07', NULL),
        (84, 'laptop ', 'asus vivobook03', 'guntur', '2025-08-07', '68947bb492ed4.png', '2025-08-13', '689c450cae6da.png'),
        (85, 'laptop Vivobook 03 Proyektor EPSON Layer Rol Kabel', 'ASUS EPSON', 'Sumantri', '2025-08-12', '689a99a5d8c3b.png', '2025-08-12', '689b1b104abb1.png'),
        (86, 'laptop ', 'asus vivobook03', 'guntur', '2025-08-13', '689be17f54299.png', '2025-09-04', '68b965174b656.png'),
        (88, 'laptop ', 'DELL 02', 'INKE', '2025-08-21', '68a6d1ca6ea9b.png', '2025-08-27', '68aecfb0951bb.png'),
        (89, 'laptop ', 'hp subreg', 'rifki', '2025-08-27', '68aee03643d62.png', NULL, NULL),
        (90, 'laptop ', 'lenovo subreg', 'dina', '2025-08-29', '68b16b3f04261.png', '2025-09-02', '68b6808293805.png'),
        (91, 'laptop ', 'lenovo subreg', 'shirni', '2025-09-03', '68b79552ac9fb.png', '2025-09-03', '68b7b47574440.png'),
        (93, 'laptop ', 'asus vivobook03', 'Ninda', '2025-09-08', '68be35278cee5.png', '2025-09-08', '68be4714f052b.png'),
        (94, 'laptop ', 'lenovo subreg', 'liya', '2025-09-10', '68c0d29d715ce.png', '2025-09-11', '68c2267e55dbd.png'),
        (95, 'laptop', 'asus vivobook03', 'guntur', '2025-09-16', '68c92450c8611.png', '2025-09-16', '68c926ee45235.png'),
        (96, 'laptop asus vivobook', 'asus vivobook03', 'guntur', '2025-09-16', '68c934b232bfb.png', '2025-10-24', '68fb3a7cdc4f2.png'),
        (97, 'laptop ', 'lenovo subreg', 'abidin', '2025-09-17', '68ca80c21b62a.png', '2025-09-24', '68d35acb9dac1.png'),
        (98, 'laptop ', 'lenovo subreg', 'abidin', '2025-09-17', '68ca80c2405e2.png', '2025-09-24', '68d35ada19f1b.png'),
        (99, 'laptop ', 'lenovo subreg', 'dina', '2025-09-25', '68d4fe4d66678.png', '2025-09-29', '68d9dc1308c09.png'),
        (100, 'laptop ', 'lenovo subreg', 'Ninda', '2025-10-17', '68f1bcddc73c9.png', '2025-10-27', '68feca736b2de.png'),
        (101, 'laptop ', 'lenovo subreg', 'dina', '2025-10-28', '69007db317b2a.png', '2025-12-10', NULL),
        (102, 'laptop ', 'asus vivobook03', 'rifki', '2025-10-31', '690419efec2c4.png', NULL, NULL),
        (103, 'ipad', 'iphone', 'jupri', '2025-11-03', '6908615c3dbf2.png', '2025-11-11', '6915b1d630a40.png'),
        (104, 'laptop ', 'DELL', 'Ninda', '2025-11-24', '6924185b569d1.png', '2025-12-10', NULL),
        (105, 'laptop', 'asus vivobook01', 'guntur', '2025-11-27', '6927a9fed9a95.png', '2025-11-27', '6927faf1b4f87.png'),
        (106, 'laptop ', 'lenovo subreg', 'dina', '2025-11-27', '6927f330ce645.png', '2025-12-01', '692d15ddcdb68.png'),
        (107, 'laptop ', 'DELL 04', 'agus tri', '2025-11-28', '69293ca51f8ab.png', '2025-11-28', '692961a12829f.png'),
        (108, 'proyektor', 'epson', 'adnan', '2025-12-04', '6930e2925c1d0.png', '2026-01-02', '69571b6c5a3c0.png'),
        (109, 'laptop ', 'lenovo subreg', 'liya', '2025-12-04', '69311b705c8b4.png', '2025-12-08', '69362d0824542.png'),
        (110, 'laptop DELL 02', 'DELL 02', 'ANI S', '2025-12-08', '6936321a1e20c.png', NULL, NULL),
        (111, 'charger 100 watt type C', 'Asus', 'dinda', '2025-12-18', '69439ff953ff8.png', '2025-12-19', '694512cab2e19.png'),
        (112, 'laptop ', 'lenovo subreg', 'dina', '2025-12-31', '695496a1be469.png', '0000-00-00', NULL),
        (113, 'laser pointer', 'laser pointer logitech', 'ananta', '2026-01-05', '695b4f9b11a9d.png', '2026-01-05', '695b7f9c7af70.png'),
        (114, 'ssd external', 'ssd', 'wachida', '2026-01-05', '695b604aeef03.png', NULL, NULL)";
        
        $pdo->exec($insertQuery);
        $status = 'success';
        $message = 'Migrasi Berhasil!';
        $details = 'Tabel <strong>peminjaman_laptop</strong> telah dibuat dan <strong>82 data historis</strong> berhasil disalin ke database utama.';
    } else {
        $status = 'warning';
        $message = 'Tabel Sudah Berisi Data';
        $details = 'Tabel <strong>peminjaman_laptop</strong> sudah memiliki <strong>' . $count . ' data</strong> di dalamnya. Migrasi tidak dijalankan ulang untuk menghindari data duplikat.';
    }
} catch (Throwable $e) {
    $status = 'danger';
    $message = 'Migrasi Gagal';
    $details = 'Terjadi kesalahan saat menjalankan migrasi: <br><code style="color: #c92a2a;">' . htmlspecialchars($e->getMessage()) . '</code>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            text-align: center;
            border: 1px solid rgba(27, 62, 111, 0.08);
        }
        .logo {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #1b3e6f;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }
        .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }
        .status-success { background: #dcfce7; color: #15803d; }
        .status-warning { background: #fef9c3; color: #a16207; }
        .status-danger { background: #fee2e2; color: #b91c1c; }
        .status-info { background: #e0f2fe; color: #0369a1; }
        
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #0f172a;
        }
        p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background: #1b3e6f;
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #153056;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">SPMT IT ASSET MANAGEMENT</div>
        
        <div class="icon-box status-<?= $status; ?>">
            <?php if ($status === 'success'): ?>
                ✓
            <?php elseif ($status === 'warning'): ?>
                !
            <?php else: ?>
                ✗
            <?php endif; ?>
        </div>
        
        <h1><?= htmlspecialchars($message); ?></h1>
        <p><?= $details; ?></p>
        
        <a href="index.php?page=peminjaman-laptop" class="btn">Kembali ke Peminjaman Laptop</a>
    </div>
</body>
</html>
