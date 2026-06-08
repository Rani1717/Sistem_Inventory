<?php
/**
 * CCTV Data Migration Script
 * Mengganti data lama dashboard_cctv dengan data baru,
 * dan mengisi cctv_inventaris dengan data kamera individual.
 * 
 * Jalankan sekali saja di: http://localhost/Sistem_Inventory/migrate_cctv.php
 */

define('BASEPATH', true);
require_once __DIR__ . '/app/config/Database.php';

$pdo = Database::getConnection();
if (!$pdo instanceof PDO) {
    die('Koneksi database gagal.');
}

$pdo->beginTransaction();

try {
    // ── 1. Buat tabel jika belum ada ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS cctv_inventaris (
        id INT NOT NULL AUTO_INCREMENT,
        nama_cctv VARCHAR(200) NOT NULL,
        kode_cctv VARCHAR(100) NOT NULL DEFAULT '',
        lokasi VARCHAR(150) NOT NULL DEFAULT '',
        status ENUM('AKTIF','RUSAK','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
        keterangan TEXT NULL,
        gambar VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_cctv_lokasi (lokasi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 2. Hapus data lama ──
    $pdo->exec("DELETE FROM dashboard_cctv");
    $pdo->exec("DELETE FROM cctv_inventaris");

    // ── 3. Data lokasi baru (dashboard_cctv) ──
    $locations = [
        ['CDC-CCC',              4,  '#5B8DEF'],
        ['AMPENAN',              5,  '#6FCF97'],
        ['PLB',                  15, '#F2A541'],
        ['CY DELI',              1,  '#34B3D8'],
        ['DERMAGA BEST',         1,  '#F58B82'],
        ['CURAH CAIR',           3,  '#7D72F8'],
        ['KEPANDUAN',            2,  '#3AA0FF'],
        ['NUSANTARA',            6,  '#6D5BD0'],
        ['GATE NUSANTARA',       5,  '#41B8D5'],
        ['PELDAM',               8,  '#F3A43B'],
        ['SAMUDERA',             7,  '#4C7BE8'],
        ['TURNSTILE',            1,  '#E879A0'],
        ['POS 1',                2,  '#2DD4BF'],
        ['POS 4',                5,  '#A78BFA'],
        ['JALAN COASTER',        6,  '#FB923C'],
        ['JALAN DELI-BEST',      3,  '#4ADE80'],
        ['JALAN KBT',            4,  '#F472B6'],
        ['JALAN MPARDI',         3,  '#60A5FA'],
        ['OVERBRANGEN',          9,  '#FBBF24'],
        ['P&C',                  5,  '#34D399'],
        ['PELRA',                4,  '#A855F7'],
        ['PLP',                  4,  '#EF4444'],
        ['POMPA',                16, '#06B6D4'],
        ['RTK',                  5,  '#14B8A6'],
        ['JT NUSANTARA & RORO',  7,  '#8B5CF6'],
        ['TERMINAL PENUMPANG',   20, '#EC4899'],
    ];

    $stmtLoc = $pdo->prepare("INSERT INTO dashboard_cctv (lokasi, jumlah, color) VALUES (:lokasi, :jumlah, :color)");
    foreach ($locations as $loc) {
        $stmtLoc->execute(['lokasi' => $loc[0], 'jumlah' => $loc[1], 'color' => $loc[2]]);
    }

    // ── 4. Data kamera individual (cctv_inventaris) ──
    $cameras = [
        // CDC-CCC (4)
        ['C049_CDC-CCC_01',         'C049', 'CDC-CCC'],
        ['C050_CDC-CCC_02',         'C050', 'CDC-CCC'],
        ['C051_CDC-CCC_03',         'C051', 'CDC-CCC'],
        ['C052_CDC-CCC_04',         'C052', 'CDC-CCC'],

        // AMPENAN (5)
        ['C053_CY_AMPENAN_01',      'C053', 'AMPENAN'],
        ['C054_CY_AMPENAN_02',      'C054', 'AMPENAN'],
        ['C055_CY_AMPENAN_03',      'C055', 'AMPENAN'],
        ['C056_CY_AMPENAN_04',      'C056', 'AMPENAN'],
        ['C082_CY_AMPENAN_05',      'C082', 'AMPENAN'],

        // PLB (15)
        ['C077_PLB_GATE',           'C077', 'PLB'],
        ['C078_PLB_TRIANGLE',       'C078', 'PLB'],
        ['C079_PLB_LAPANGAN_1',     'C079', 'PLB'],
        ['C080_PLB_LAPANGAN_2',     'C080', 'PLB'],
        ['C071_PLB_Selatan_Timur_Luar',  'C071', 'PLB'],
        ['C067_PLB_Utara_Timur_Luar',    'C067', 'PLB'],
        ['C070_PLB_Selatan_Barat_Luar',  'C070', 'PLB'],
        ['C072_PLB_PTZ_Luar_1',     'C072', 'PLB'],
        ['C073_PLB_PTZ_Luar_2',     'C073', 'PLB'],
        ['C074_PLB_Buffer_1',       'C074', 'PLB'],
        ['C075_PLB_Buffer_2',       'C075', 'PLB'],
        ['C069_PLB_Selatan_Barat_Dalam', 'C069', 'PLB'],
        ['C068_PLB_Utara_Timur_Dalam',   'C068', 'PLB'],
        ['C066_PLB_PTZ_Tengah_Dalam',    'C066', 'PLB'],
        ['C076_PLB_Utara_Barat_Dalam',   'C076', 'PLB'],

        // CY DELI (1)
        ['C134_CY_DELI',            'C134', 'CY DELI'],

        // DERMAGA BEST (1)
        ['C030_C.CAIR_BEST',        'C030', 'DERMAGA BEST'],

        // CURAH CAIR (3)
        ['C031_DC.CAIR_DELI_01',    'C031', 'CURAH CAIR'],
        ['C032_DC.CAIR_DELI_02',    'C032', 'CURAH CAIR'],
        ['C033_DC.CAIR_DELI_03',    'C033', 'CURAH CAIR'],

        // KEPANDUAN (2)
        ['C046_KEPANDUAN',          'C046', 'KEPANDUAN'],
        ['C047_KEPANDUAN_BARU',     'C047', 'KEPANDUAN'],

        // NUSANTARA (6)
        ['C008_NUSANTARA_01',       'C008', 'NUSANTARA'],
        ['C009_NUSANTARA_02',       'C009', 'NUSANTARA'],
        ['C010_NUSANTARA_03',       'C010', 'NUSANTARA'],
        ['C005_NUSANTARA_04',       'C005', 'NUSANTARA'],
        ['C006_NUSANTARA_05',       'C006', 'NUSANTARA'],
        ['C143_NUSANTARA_06',       'C143', 'NUSANTARA'],

        // GATE NUSANTARA (5)
        ['GATEIN_PLAT_NUSANTARA',   'GATEIN_PLAT', 'GATE NUSANTARA'],
        ['GATEOUT_PLAT_NUSANTARA',  'GATEOUT_PLAT','GATE NUSANTARA'],
        ['GATEIN_MUAT',             'GATEIN_MUAT', 'GATE NUSANTARA'],
        ['GATEOUT_MUAT',            'GATEOUT_MUAT','GATE NUSANTARA'],
        ['GATE_SUMBU',              'GATE_SUMBU',  'GATE NUSANTARA'],

        // PELDAM (8)
        ['C036_PELDAM_01',          'C036', 'PELDAM'],
        ['C042_PELDAM_02',          'C042', 'PELDAM'],
        ['C035_PELDAM_03',          'C035', 'PELDAM'],
        ['C034_PELDAM_04',          'C034', 'PELDAM'],
        ['C033_PELDAM_05',          'C033', 'PELDAM'],
        ['C136_PELDAM_6',           'C136', 'PELDAM'],
        ['C137_PELDAM_7',           'C137', 'PELDAM'],
        ['C138_PELDAM_8',           'C138', 'PELDAM'],

        // SAMUDERA (7)
        ['C002_SAMUDERA_01',        'C002', 'SAMUDERA'],
        ['C003_SAMUDERA_03',        'C003', 'SAMUDERA'],
        ['C004_SAMUDERA_04',        'C004', 'SAMUDERA'],
        ['C007_SAMUDERA_02',        'C007', 'SAMUDERA'],
        ['C140_SAMUDERA_05',        'C140', 'SAMUDERA'],
        ['C141_SAMUDERA_06',        'C141', 'SAMUDERA'],
        ['C142_SAMUDERA_07',        'C142', 'SAMUDERA'],

        // TURNSTILE (1)
        ['C145_TRUNSTILE_PELDAM',   'C145', 'TURNSTILE'],

        // POS 1 (2)
        ['C120_GATE_POS1_01',       'C120', 'POS 1'],
        ['C121_GATE_POS1_02',       'C121', 'POS 1'],

        // POS 4 (5)
        ['C104_POS4-GATEIN_01',     'C104', 'POS 4'],
        ['C105_POS4-GATEIN_02',     'C105', 'POS 4'],
        ['C106_POS4-GATEIN_03',     'C106', 'POS 4'],
        ['C107_POS4-GATEIN_04',     'C107', 'POS 4'],
        ['C103_POS4-GATEOUT',       'C103', 'POS 4'],

        // JALAN COASTER (6)
        ['C110_COASTER01',          'C110', 'JALAN COASTER'],
        ['C001_COASTER04',          'C001', 'JALAN COASTER'],
        ['C124_COASTER02',          'C124', 'JALAN COASTER'],
        ['C125_COASTER05',          'C125', 'JALAN COASTER'],
        ['C126_COASTER03',          'C126', 'JALAN COASTER'],
        ['C108_COASTER6',           'C108', 'JALAN COASTER'],

        // JALAN DELI-BEST (3)
        ['C115_GATE-DELI',          'C115', 'JALAN DELI-BEST'],
        ['C087_JALAN_DELI_01',      'C087', 'JALAN DELI-BEST'],
        ['C114_JL_C.CAIR_BEST',     'C114', 'JALAN DELI-BEST'],

        // JALAN KBT (4)
        ['C119_POS1_01',            'C119', 'JALAN KBT'],
        ['C123_POS1_02',            'C123', 'JALAN KBT'],
        ['C118_POS1_03',            'C118', 'JALAN KBT'],
        ['C122_ARAH_KEPANDUAN',     'C122', 'JALAN KBT'],

        // JALAN MPARDI (3)
        ['C113_MPARDI_01',          'C113', 'JALAN MPARDI'],
        ['C127_MPARDI02',           'C127', 'JALAN MPARDI'],
        ['C114_PENUMPUKAN_PIPA_M.PARDI', 'C114B', 'JALAN MPARDI'],

        // OVERBRANGEN (9)
        ['C057_OVB_GATE',           'C057', 'OVERBRANGEN'],
        ['C058_OVB_01',             'C058', 'OVERBRANGEN'],
        ['C059_OVB_DARAT1',         'C059', 'OVERBRANGEN'],
        ['C060_OVB_DARAT2',         'C060', 'OVERBRANGEN'],
        ['C061_OVB_DARAT3',         'C061', 'OVERBRANGEN'],
        ['C062_OVB_DARAT4',         'C062', 'OVERBRANGEN'],
        ['C063_OVB_LAUT1',          'C063', 'OVERBRANGEN'],
        ['C064_OVB_LAUT2',          'C064', 'OVERBRANGEN'],
        ['C065_OVB_LAUT3',          'C065', 'OVERBRANGEN'],

        // P&C (5)
        ['C130_PDC01',              'C130', 'P&C'],
        ['C131_PDC02',              'C131', 'P&C'],
        ['C132_PDC03',              'C132', 'P&C'],
        ['C129_PDC_RACK_NETWORK',   'C129', 'P&C'],
        ['C027_PDC_REST',           'C027', 'P&C'],

        // PELRA (4)
        ['C037_PELRA_01',           'C037', 'PELRA'],
        ['C038_PELRA_02',           'C038', 'PELRA'],
        ['C039_PELRA_03',           'C039', 'PELRA'],
        ['C040_PELRA_04',           'C040', 'PELRA'],

        // PLP (4)
        ['C083_PLP_GATE',           'C083', 'PLP'],
        ['C084_PLP_01',             'C084', 'PLP'],
        ['C085_PLP_02',             'C085', 'PLP'],
        ['C086_PLP_03',             'C086', 'PLP'],

        // POMPA (16)
        ['C098_POMPA_COASTER',      'C098', 'POMPA'],
        ['C099_POMPA_CY6_TPSM',     'C099', 'POMPA'],
        ['C091_POMPA_CABANG',       'C091', 'POMPA'],
        ['C088_POMPA_TEPZ',         'C088', 'POMPA'],
        ['C090_POMPA_DELI',         'C090', 'POMPA'],
        ['C094_POMPA_CLUSTER3_INDOOR1', 'C094', 'POMPA'],
        ['C095_POMPA_CLUSTER3_INDOOR2', 'C095', 'POMPA'],
        ['C092_POMPA_AMPENAN',      'C092', 'POMPA'],
        ['C096_POMPA_KEPANDUAN',    'C096', 'POMPA'],
        ['C100_POMPA_POS1',         'C100', 'POMPA'],
        ['C139_POMPA_KBB1',         'C139', 'POMPA'],
        ['C135_POMPA_KBB2',         'C135', 'POMPA'],
        ['C093_POMPA_KBB3',         'C093', 'POMPA'],
        ['C089_POMPA_RTK_TIMUR',    'C089', 'POMPA'],
        ['C097_POMPA_PRASASTI_S02', 'C097', 'POMPA'],
        ['Pompa_samudra_01',        'POMPA_SAM', 'POMPA'],

        // RTK (5)
        ['C111_RTK_BARAT_IN',       'C111', 'RTK'],
        ['C112_RTK_BARAT_OUT',      'C112', 'RTK'],
        ['C109_RTK-TIMUR_02',       'C109', 'RTK'],
        ['C117_RTK_PMK_02',         'C117', 'RTK'],
        ['C116_RTK_PMK_01',         'C116', 'RTK'],

        // JT NUSANTARA & RORO (7)
        ['galva_JT_Nusantara_1',    'GALVA_JT1',    'JT NUSANTARA & RORO'],
        ['galva_JT_Nusantara_2',    'GALVA_JT2',    'JT NUSANTARA & RORO'],
        ['galva_JT_Nusantara_3',    'GALVA_JT3',    'JT NUSANTARA & RORO'],
        ['galva_roro_in_mobil',     'GALVA_RIN_M',  'JT NUSANTARA & RORO'],
        ['galva_roro_out_mobil',    'GALVA_ROUT_M', 'JT NUSANTARA & RORO'],
        ['galva_roro_in_motor',     'GALVA_RIN_MT', 'JT NUSANTARA & RORO'],
        ['galva_roro_out_motor',    'GALVA_ROUT_MT','JT NUSANTARA & RORO'],

        // TERMINAL PENUMPANG (20)
        ['galva_BoardingTP',        'GALVA_BTP',    'TERMINAL PENUMPANG'],
        ['C012_PKL-TP_01',          'C012',         'TERMINAL PENUMPANG'],
        ['C015_PKL-TP_04',          'C015',         'TERMINAL PENUMPANG'],
        ['C013_PKL-TP_02',          'C013',         'TERMINAL PENUMPANG'],
        ['C016_PKL-TP_05',          'C016',         'TERMINAL PENUMPANG'],
        ['C014_PKL-TP_03',          'C014',         'TERMINAL PENUMPANG'],
        ['C017_PKL-TP_06',          'C017',         'TERMINAL PENUMPANG'],
        ['C011_PENUMPANG01',        'C011',         'TERMINAL PENUMPANG'],
        ['C019_PENUMPANG02',        'C019',         'TERMINAL PENUMPANG'],
        ['C020_TP_TERAS_DOM1',      'C020',         'TERMINAL PENUMPANG'],
        ['C133_TP_TERAS_DOM02',     'C133',         'TERMINAL PENUMPANG'],
        ['C023_TP_TERAS_INTL',      'C023',         'TERMINAL PENUMPANG'],
        ['C021_KEDATANGAN_DOM',     'C021',         'TERMINAL PENUMPANG'],
        ['C025_TP_LT1_DOM',         'C025',         'TERMINAL PENUMPANG'],
        ['C026_TP_LT2_DOM',         'C026',         'TERMINAL PENUMPANG'],
        ['C024_TP_LT1_INTL',        'C024',         'TERMINAL PENUMPANG'],
        ['C028_TP_LT2_INTL',        'C028',         'TERMINAL PENUMPANG'],
        ['C029_GATE_BOARDING',      'C029',         'TERMINAL PENUMPANG'],
        ['C018_TP_BC',              'C018',         'TERMINAL PENUMPANG'],
        ['C128_TP_PARKIRAN',        'C128',         'TERMINAL PENUMPANG'],
    ];

    $stmtCam = $pdo->prepare("INSERT INTO cctv_inventaris (nama_cctv, kode_cctv, lokasi, status) VALUES (:nama, :kode, :lokasi, 'AKTIF')");
    foreach ($cameras as $cam) {
        $stmtCam->execute(['nama' => $cam[0], 'kode' => $cam[1], 'lokasi' => $cam[2]]);
    }

    $pdo->commit();

    $totalCam = count($cameras);
    $totalLoc = count($locations);
    echo "✅ Migrasi berhasil!<br>";
    echo "📍 Lokasi: <strong>{$totalLoc}</strong> lokasi<br>";
    echo "📷 Kamera: <strong>{$totalCam}</strong> kamera<br>";
    echo "<br><a href='index.php?page=dashboard'>→ Kembali ke Dashboard</a>";

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "❌ Migrasi gagal: " . htmlspecialchars($e->getMessage());
}
