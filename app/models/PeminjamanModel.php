<?php
/**
 * PeminjamanModel — mengelola data peminjaman laptop
 * Menggunakan koneksi PDO dari Database::getConnection() (database utama SPMT).
 * Tabel: peminjaman_laptop (dibuat otomatis jika belum ada)
 * Upload foto disimpan di: peminjaman_laptop/uploads/
 */
class PeminjamanModel
{
    private const TABLE = 'peminjaman_laptop';
    private const UPLOAD_DIR = 'peminjaman_laptop/uploads/';

    /** Pastikan tabel tersedia di database utama SPMT */
    public function ensureTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
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
    }

    /** Ambil semua data peminjaman, urut terbaru dulu */
    public function fetchAll(PDO $pdo, string $filter = '', string $search = ''): array
    {
        $where = [];
        $params = [];

        if ($filter === 'dipinjam') {
            $where[] = '(tanggal_pengembalian IS NULL OR tanggal_pengembalian = "0000-00-00" OR TRIM(tanggal_pengembalian) = "")';
        } elseif ($filter === 'dikembalikan') {
            $where[] = '(tanggal_pengembalian IS NOT NULL AND tanggal_pengembalian <> "0000-00-00" AND TRIM(tanggal_pengembalian) <> "")';
        }

        if ($search !== '') {
            $searchLower = strtolower($search);
            $statusCond = '';
            if (str_contains('dipinjam', $searchLower)) {
                $statusCond = ' OR (tanggal_pengembalian IS NULL OR tanggal_pengembalian = "0000-00-00" OR TRIM(tanggal_pengembalian) = "")';
            }
            if (str_contains('dikembalikan', $searchLower)) {
                $statusCond .= ' OR (tanggal_pengembalian IS NOT NULL AND tanggal_pengembalian <> "0000-00-00" AND TRIM(tanggal_pengembalian) <> "")';
            }

            $where[] = '(nama_barang LIKE :search OR merk_barang LIKE :search OR nama_peminjam LIKE :search OR tanggal_peminjaman LIKE :search OR COALESCE(tanggal_pengembalian, "") LIKE :search' . $statusCond . ')';
            $params['search'] = '%' . $search . '%';
        }

        $sql = 'SELECT * FROM `' . self::TABLE . '`';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Ambil data yang belum dikembalikan (untuk dropdown pengembalian) */
    public function fetchBelumKembali(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT id, nama_barang, merk_barang, nama_peminjam FROM `' . self::TABLE . '`
             WHERE tanggal_pengembalian IS NULL OR tanggal_pengembalian = "0000-00-00" OR TRIM(tanggal_pengembalian) = ""
             ORDER BY tanggal_peminjaman DESC'
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /** Ambil ringkasan statistik */
    public function fetchStats(PDO $pdo): array
    {
        try {
            $total     = (int) $pdo->query('SELECT COUNT(*) FROM `' . self::TABLE . '`')->fetchColumn();
            $dipinjam  = (int) $pdo->query('SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE tanggal_pengembalian IS NULL OR tanggal_pengembalian = "0000-00-00" OR TRIM(tanggal_pengembalian) = ""')->fetchColumn();
            $kembali   = $total - $dipinjam;
            return ['total' => $total, 'dipinjam' => $dipinjam, 'kembali' => $kembali];
        } catch (Throwable $e) {
            return ['total' => 0, 'dipinjam' => 0, 'kembali' => 0];
        }
    }

    /** Simpan data peminjaman baru + foto base64 */
    public function savePeminjaman(PDO $pdo, array $post): void
    {
        $namaBarang       = trim((string) ($post['nama_barang'] ?? ''));
        $merkBarang       = trim((string) ($post['merk_barang'] ?? ''));
        $namaPeminjam     = trim((string) ($post['nama_peminjam'] ?? ''));
        $tanggal          = trim((string) ($post['tanggal_peminjaman'] ?? ''));
        $buktiBase64      = (string) ($post['bukti_peminjaman'] ?? '');

        if ($namaBarang === '' || $merkBarang === '' || $namaPeminjam === '' || $tanggal === '') {
            throw new RuntimeException('Data peminjaman tidak lengkap.');
        }

        $buktiFile = $this->saveBase64Image($buktiBase64);

        $stmt = $pdo->prepare(
            'INSERT INTO `' . self::TABLE . '`
             (nama_barang, merk_barang, nama_peminjam, tanggal_peminjaman, bukti_peminjaman)
             VALUES (:nama_barang, :merk_barang, :nama_peminjam, :tanggal_peminjaman, :bukti_peminjaman)'
        );
        $stmt->execute([
            'nama_barang'       => $namaBarang,
            'merk_barang'       => $merkBarang,
            'nama_peminjam'     => $namaPeminjam,
            'tanggal_peminjaman'=> $tanggal,
            'bukti_peminjaman'  => $buktiFile,
        ]);
    }

    /** Simpan data pengembalian + foto base64 */
    public function saveReturn(PDO $pdo, array $post): void
    {
        $id              = (int) ($post['id_peminjaman'] ?? 0);
        $tanggal         = trim((string) ($post['tanggal_pengembalian'] ?? ''));
        $buktiBase64     = (string) ($post['bukti_pengembalian'] ?? '');

        if ($id <= 0 || $tanggal === '') {
            throw new RuntimeException('Data pengembalian tidak lengkap.');
        }

        $buktiFile = $this->saveBase64Image($buktiBase64);

        $stmt = $pdo->prepare(
            'UPDATE `' . self::TABLE . '`
             SET tanggal_pengembalian = :tanggal, bukti_pengembalian = :bukti
             WHERE id = :id'
        );
        $stmt->execute([
            'tanggal' => $tanggal,
            'bukti'   => $buktiFile,
            'id'      => $id,
        ]);
    }

    /** Update data peminjaman (admin) */
    public function update(PDO $pdo, array $post): void
    {
        $id                  = (int) ($post['edit_id'] ?? 0);
        $namaBarang          = trim((string) ($post['edit_nama_barang'] ?? ''));
        $merkBarang          = trim((string) ($post['edit_merk_barang'] ?? ''));
        $namaPeminjam        = trim((string) ($post['edit_nama_peminjam'] ?? ''));
        $tanggalPeminjaman   = trim((string) ($post['edit_tanggal_peminjaman'] ?? ''));
        $tanggalPengembalian = trim((string) ($post['edit_tanggal_pengembalian'] ?? ''));

        if ($id <= 0) {
            throw new RuntimeException('ID tidak valid.');
        }

        $stmt = $pdo->prepare(
            'UPDATE `' . self::TABLE . '`
             SET nama_barang = :nama_barang,
                 merk_barang = :merk_barang,
                 nama_peminjam = :nama_peminjam,
                 tanggal_peminjaman = :tanggal_peminjaman,
                 tanggal_pengembalian = :tanggal_pengembalian
             WHERE id = :id'
        );
        $stmt->execute([
            'nama_barang'          => $namaBarang,
            'merk_barang'          => $merkBarang,
            'nama_peminjam'        => $namaPeminjam,
            'tanggal_peminjaman'   => $tanggalPeminjaman,
            'tanggal_pengembalian' => $tanggalPengembalian !== '' ? $tanggalPengembalian : null,
            'id'                   => $id,
        ]);
    }

    /** Hapus data peminjaman (admin) */
    public function delete(PDO $pdo, int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('ID tidak valid.');
        }
        $stmt = $pdo->prepare('DELETE FROM `' . self::TABLE . '` WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Simpan gambar base64 ke disk, return nama file */
    private function saveBase64Image(string $base64): string
    {
        if ($base64 === '' || strpos($base64, ';base64,') === false) {
            return '';
        }
        $parts     = explode(';base64,', $base64);
        $imageData = base64_decode($parts[1] ?? '');
        if ($imageData === false || $imageData === '') {
            return '';
        }
        $dir = __DIR__ . '/../../peminjaman_laptop/uploads/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fileName = uniqid('', true) . '.png';
        file_put_contents($dir . $fileName, $imageData);
        return $fileName;
    }

    /** Generate & stream file Excel lewat browser */
    public function exportExcel(PDO $pdo): void
    {
        $autoloadPath = __DIR__ . '/../../peminjaman_laptop/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new RuntimeException('PhpSpreadsheet tidak ditemukan di peminjaman_laptop/vendor/.');
        }
        require_once $autoloadPath;

        $rows = $this->fetchAll($pdo);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Peminjaman Laptop');

        // Header
        $headers = ['No', 'Nama Barang', 'Merk', 'Peminjam', 'Tgl Pinjam', 'Tgl Kembali', 'Status'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B3E6F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Data
        $no = 1;
        foreach ($rows as $row) {
            $status = (!$row['tanggal_pengembalian'] || $row['tanggal_pengembalian'] === '0000-00-00')
                ? 'Belum Dikembalikan'
                : 'Sudah Dikembalikan';
            $sheet->fromArray([
                $no++,
                $row['nama_barang'],
                $row['merk_barang'],
                $row['nama_peminjam'],
                $row['tanggal_peminjaman'],
                $row['tanggal_pengembalian'] ?? '-',
                $status,
            ], null, 'A' . ($no));
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Peminjaman_Laptop_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
