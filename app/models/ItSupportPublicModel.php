<?php
require_once __DIR__ . '/Database.php';

class ItSupportPublicModel
{
    public function getConnection(): ?PDO
    {
        return Database::getConnection();
    }

    public function findUserByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT u.*, d.division_label FROM users u LEFT JOIN master_divisi d ON d.id = u.default_divisi_id WHERE u.email = :email AND u.is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ensureEmailVerificationColumn(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE it_support_request ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_pelapor");
        } catch (Throwable $e) {
            // kolom sudah ada / alter gagal diabaikan agar tetap kompatibel
        }
    }

    public function ensureNotificationReadColumn(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE it_support_request ADD COLUMN notification_read_at DATETIME NULL AFTER updated_at");
        } catch (Throwable $e) {
            // kolom sudah ada / alter gagal diabaikan agar form publik tetap kompatibel
        }
    }

    public function createTicket(PDO $pdo, array $payload): string
    {
        $this->ensureEmailVerificationColumn($pdo);
        $this->ensureNotificationReadColumn($pdo);
        $ticketNo = $this->generateTicketNo($pdo, $payload['tanggal']);
        $sql = 'INSERT INTO it_support_request (
            ticket_no, reporter_user_id, tanggal, jam, email_pelapor, email_verified, nama_pelapor, divisi,
            aset_yang_perlu_diperbaiki, lokasi_perbaikan, deskripsi_kerusakan, dokumentasi_kerusakan, status
        ) VALUES (
            :ticket_no, :reporter_user_id, :tanggal, :jam, :email_pelapor, :email_verified, :nama_pelapor, :divisi,
            :aset_yang_perlu_diperbaiki, :lokasi_perbaikan, :deskripsi_kerusakan, :dokumentasi_kerusakan, :status
        )';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'ticket_no' => $ticketNo,
            'reporter_user_id' => $payload['reporter_user_id'] ?: null,
            'tanggal' => $payload['tanggal'],
            'jam' => $payload['jam'],
            'email_pelapor' => $payload['email_pelapor'],
            'email_verified' => (int) ($payload['email_verified'] ?? 0),
            'nama_pelapor' => $payload['nama_pelapor'],
            'divisi' => $payload['divisi'],
            'aset_yang_perlu_diperbaiki' => $payload['aset_yang_perlu_diperbaiki'],
            'lokasi_perbaikan' => $payload['lokasi_perbaikan'],
            'deskripsi_kerusakan' => $payload['deskripsi_kerusakan'],
            'dokumentasi_kerusakan' => $payload['dokumentasi_kerusakan'] ?: null,
            'status' => 'NOT YET',
        ]);
        return $ticketNo;
    }

    private function generateTicketNo(PDO $pdo, string $tanggal): string
    {
        $cleanDate = str_replace('-', '', $tanggal);
        $prefix = 'TSR-' . $cleanDate . '-';
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM it_support_request WHERE tanggal = :tanggal');
        $stmt->execute(['tanggal' => $tanggal]);
        $total = (int) (($stmt->fetch()['total'] ?? 0)) + 1;
        return $prefix . str_pad((string) $total, 4, '0', STR_PAD_LEFT);
    }
}
