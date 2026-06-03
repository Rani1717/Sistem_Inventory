<?php
require_once __DIR__ . '/../models/ItSupportPublicModel.php';
require_once __DIR__ . '/AuthController.php';

class PublicItSupportController
{
    private ItSupportPublicModel $model;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ItSupportPublicModel();
    }

    public function handle(string $page): void
    {
        switch ($page) {
            case 'submit-step-1':
            case 'submit-final':
                $this->submitFinal();
                return;
            case 'success':
            case 'step-2':
            case 'form':
            default:
                $this->render('step-1');
                return;
        }
    }

    private function submitFinal(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: it-support.php');
            exit;
        }

        $payload = [
            'email_pelapor' => trim((string) ($_POST['email_pelapor'] ?? '')),
            'nama_pelapor' => trim((string) ($_POST['nama_pelapor'] ?? '')),
            'divisi' => trim((string) ($_POST['divisi'] ?? '')),
            'aset_yang_perlu_diperbaiki' => trim((string) ($_POST['aset_yang_perlu_diperbaiki'] ?? '')),
            'lokasi_perbaikan' => trim((string) ($_POST['lokasi_perbaikan'] ?? '')),
            'deskripsi_kerusakan' => trim((string) ($_POST['deskripsi_kerusakan'] ?? '')),
            'email_verified' => (string) ($_POST['email_verified'] ?? '') === '1' ? 1 : 0,
        ];

        $errors = [];
        foreach (['email_pelapor', 'nama_pelapor', 'divisi', 'aset_yang_perlu_diperbaiki', 'lokasi_perbaikan', 'deskripsi_kerusakan'] as $requiredKey) {
            if (($payload[$requiredKey] ?? '') === '') {
                $errors[$requiredKey] = 'Field wajib diisi.';
            }
        }

        if ($payload['email_pelapor'] !== '' && !filter_var($payload['email_pelapor'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_pelapor'] = 'Format email tidak valid.';
        }
        if (($payload['email_verified'] ?? 0) !== 1) {
            $errors['email_verified'] = 'Konfirmasi email aktif harus dicentang.';
        }

        $pdo = $this->model->getConnection();
        if ($pdo instanceof PDO && $payload['email_pelapor'] !== '') {
            $user = $this->model->findUserByEmail($pdo, $payload['email_pelapor']);
            if ($user) {
                if ($payload['nama_pelapor'] === '') {
                    $payload['nama_pelapor'] = (string) ($user['nama_lengkap'] ?? '');
                }
                if ($payload['divisi'] === '') {
                    $payload['divisi'] = (string) ($user['division_label'] ?? $user['unit_kerja_default'] ?? '');
                }
                $payload['reporter_user_id'] = (int) ($user['id'] ?? 0);
            }
        }

        if ($pdo instanceof PDO) {
            $normalizedDivision = $this->normalizeDivisionFromMaster($pdo, $payload['divisi']);
            if ($payload['divisi'] !== '' && $normalizedDivision === '') {
                $errors['divisi'] = 'Divisi tidak valid. Pilih divisi sesuai master database.';
            } elseif ($normalizedDivision !== '') {
                $payload['divisi'] = $normalizedDivision;
            }
        }

        $existingDocumentation = trim((string) ($_POST['existing_dokumentasi_kerusakan'] ?? ''));
        $removeDocumentation = (string) ($_POST['remove_dokumentasi_kerusakan'] ?? '') === '1';
        $uploadPath = $removeDocumentation ? '' : $existingDocumentation;
        if (!empty($_FILES['dokumentasi_kerusakan']['name'])) {
            try {
                if ($existingDocumentation !== '') {
                    $this->deleteUploadedFile($existingDocumentation);
                }
                $uploadPath = $this->handleUpload($_FILES['dokumentasi_kerusakan'], true);
            } catch (Throwable $e) {
                $errors['dokumentasi_kerusakan'] = $e->getMessage();
            }
        }
        $payload['existing_dokumentasi_kerusakan'] = $uploadPath;

        $_SESSION['it_support_public']['old_form'] = $payload;
        $_SESSION['it_support_public']['errors'] = $errors;

        if ($errors) {
            $this->flash('error', 'Tiket belum berhasil dikirim. Periksa kembali field yang masih bermasalah.');
            header('Location: it-support.php');
            exit;
        }

        if (!$pdo instanceof PDO) {
            $_SESSION['it_support_public']['errors'] = ['global' => 'Koneksi database tidak tersedia.'];
            $this->flash('error', 'Koneksi database tidak tersedia.');
            header('Location: it-support.php');
            exit;
        }

        $submittedDate = trim((string) ($_POST['submitted_tanggal'] ?? ''));
        $submittedTime = trim((string) ($_POST['submitted_jam'] ?? ''));
        $submittedAt = $this->resolveSubmittedDateTime($submittedDate, $submittedTime);
        $finalDocumentation = $uploadPath !== '' ? $this->promoteUploadedFile($uploadPath) : '';
        $fullPayload = array_merge($payload, [
            'tanggal' => $submittedAt->format('Y-m-d'),
            'jam' => $submittedAt->format('H:i:s'),
            'dokumentasi_kerusakan' => $finalDocumentation,
            'reporter_user_id' => (int) ($payload['reporter_user_id'] ?? 0),
            'email_verified' => (int) ($payload['email_verified'] ?? 0),
        ]);

        try {
            $ticketNo = $this->model->createTicket($pdo, $fullPayload);
            unset($_SESSION['it_support_public']['old_form'], $_SESSION['it_support_public']['errors']);
            $this->flash('success', 'Permintaan berhasil dikirim.', [
                'ticket_no' => $ticketNo,
                'nama_pelapor' => $fullPayload['nama_pelapor'],
                'email_pelapor' => $fullPayload['email_pelapor'],
            ]);
        } catch (Throwable $e) {
            $_SESSION['it_support_public']['old_form'] = $payload;
            $_SESSION['it_support_public']['old_form']['existing_dokumentasi_kerusakan'] = $finalDocumentation !== '' ? $finalDocumentation : $uploadPath;
            $_SESSION['it_support_public']['errors'] = ['global' => 'Permintaan gagal disimpan ke database.'];
            $this->flash('error', 'Permintaan gagal disimpan ke database.');
        }

        header('Location: it-support.php');
        exit;
    }

    private function flash(string $type, string $message, array $meta = []): void
    {
        $_SESSION['it_support_public']['flash'] = [
            'type' => $type,
            'message' => $message,
            'meta' => $meta,
        ];
    }

    private function resolveSubmittedDateTime(string $date, string $time): DateTimeImmutable
    {
        $date = trim($date);
        $time = trim($time);
        if ($date !== '' && $time !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            $value = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time);
            if ($value instanceof DateTimeImmutable) {
                return $value;
            }
        }
        return new DateTimeImmutable('now');
    }

    private function handleUpload(array $file, bool $temporary = false): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload dokumentasi gagal.');
        }
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $mime = is_file($tmpPath) ? (string) mime_content_type($tmpPath) : '';
        if (!isset($allowedMime[$mime])) {
            throw new RuntimeException('Dokumentasi harus berupa JPG, PNG, atau WEBP.');
        }
        if (((int) ($file['size'] ?? 0)) > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran dokumentasi maksimal 5 MB.');
        }
        $dir = dirname(__DIR__, 2) . '/public/uploads/it-support' . ($temporary ? '/temp' : '');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $prefix = $temporary ? 'tmp_it_support_' : 'it_support_';
        $name = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmpPath, $target)) {
            throw new RuntimeException('File dokumentasi tidak bisa disimpan.');
        }
        return 'public/uploads/it-support/' . ($temporary ? 'temp/' : '') . $name;
    }

    private function promoteUploadedFile(string $path): string
    {
        if ($path === '' || strpos($path, 'public/uploads/it-support/temp/') !== 0) {
            return $path;
        }
        $source = dirname(__DIR__, 2) . '/' . $path;
        if (!is_file($source)) {
            return '';
        }
        $targetDir = dirname(__DIR__, 2) . '/public/uploads/it-support';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $name = 'it_support_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($extension ? '.' . $extension : '');
        $target = $targetDir . '/' . $name;
        if (!@rename($source, $target)) {
            if (!@copy($source, $target)) {
                throw new RuntimeException('File dokumentasi tidak bisa dipindahkan.');
            }
            @unlink($source);
        }
        return 'public/uploads/it-support/' . $name;
    }

    private function deleteUploadedFile(string $path): void
    {
        if ($path === '') {
            return;
        }
        if (strpos($path, 'public/uploads/it-support/') !== 0) {
            return;
        }
        $fullPath = dirname(__DIR__, 2) . '/' . $path;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function render(string $view): void
    {
        $data = $this->buildViewData($view);
        $viewFile = dirname(__DIR__) . '/views/public/' . $view . '.php';
        require dirname(__DIR__) . '/views/public/layout.php';
    }

    private function fetchDivisionOptions(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT id, division_code, division_label, sheet_sumber FROM master_divisi WHERE is_active = 1 ORDER BY sheet_sumber ASC, division_label ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $options = [];
            foreach ($rows as $row) {
                $label = trim((string) ($row['division_label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $options[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'code' => (string) ($row['division_code'] ?? ''),
                    'label' => $label,
                    'group' => (string) ($row['sheet_sumber'] ?? ''),
                ];
            }
            return $options;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function normalizeDivisionFromMaster(PDO $pdo, string $division): string
    {
        $division = trim($division);
        if ($division === '') {
            return '';
        }

        try {
            if (ctype_digit($division)) {
                $stmt = $pdo->prepare('SELECT division_label FROM master_divisi WHERE is_active = 1 AND id = :id LIMIT 1');
                $stmt->execute(['id' => (int) $division]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return trim((string) ($row['division_label'] ?? ''));
                }
            }

            $stmt = $pdo->prepare('SELECT division_label FROM master_divisi WHERE is_active = 1 AND (UPPER(TRIM(division_label)) = UPPER(TRIM(:division_label)) OR UPPER(TRIM(division_code)) = UPPER(TRIM(:division_code)) OR UPPER(TRIM(division_group_name)) = UPPER(TRIM(:division_group_name))) ORDER BY id ASC LIMIT 1');
            $stmt->execute([
                'division_label' => $division,
                'division_code' => $division,
                'division_group_name' => $division,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return trim((string) ($row['division_label'] ?? ''));
            }

            $stmt = $pdo->prepare('SELECT division_label FROM master_divisi WHERE is_active = 1 AND UPPER(TRIM(division_label)) LIKE UPPER(TRIM(:division_like)) ORDER BY id ASC LIMIT 1');
            $stmt->execute(['division_like' => '%' . $division . '%']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? trim((string) ($row['division_label'] ?? '')) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    private function buildViewData(string $view): array
    {
        $now = new DateTimeImmutable('now');
        $oldForm = $_SESSION['it_support_public']['old_form'] ?? [];
        $errors = $_SESSION['it_support_public']['errors'] ?? [];
        $flash = $_SESSION['it_support_public']['flash'] ?? [];
        unset($_SESSION['it_support_public']['flash']);

        $emailDefault = '';
        $nameDefault = '';
        $divisionDefault = '';
        $divisionOptions = [];
        $pdo = $this->model->getConnection();
        if ($pdo instanceof PDO) {
            $divisionOptions = $this->fetchDivisionOptions($pdo);
        }
        if (AuthController::check()) {
            $auth = $_SESSION['auth'] ?? [];
            $emailDefault = (string) ($auth['email'] ?? '');
            $nameDefault = (string) ($auth['nama_lengkap'] ?? '');
            $divisionDefault = (string) ($auth['unit_kerja_default'] ?? '');
            if ($pdo instanceof PDO) {
                $divisionByAuth = $this->normalizeDivisionFromMaster($pdo, (string) ($auth['default_divisi_id'] ?? ''));
                $divisionDefault = $divisionByAuth !== '' ? $divisionByAuth : ($this->normalizeDivisionFromMaster($pdo, $divisionDefault) ?: $divisionDefault);
            }
        }

        $form = [
            'email_pelapor' => $oldForm['email_pelapor'] ?? $emailDefault,
            'nama_pelapor' => $oldForm['nama_pelapor'] ?? $nameDefault,
            'divisi' => $oldForm['divisi'] ?? $divisionDefault,
            'aset_yang_perlu_diperbaiki' => $oldForm['aset_yang_perlu_diperbaiki'] ?? '',
            'lokasi_perbaikan' => $oldForm['lokasi_perbaikan'] ?? '',
            'deskripsi_kerusakan' => $oldForm['deskripsi_kerusakan'] ?? '',
            'email_verified' => (int) ($oldForm['email_verified'] ?? ($emailDefault !== '' ? 1 : 0)),
            'existing_dokumentasi_kerusakan' => (string) ($oldForm['existing_dokumentasi_kerusakan'] ?? ''),
        ];

        return [
            'page' => $view,
            'date' => strtoupper($this->formatIndonesianDate($now)),
            'time' => $this->formatIndonesianTime($now),
            'user_email' => $form['email_pelapor'],
            'email_verified' => (int) $form['email_verified'],
            'form' => $form,
            'division_options' => $divisionOptions,
            'errors' => $errors,
            'flash' => $flash,
            'whatsapp_url' => $this->buildWhatsappUrl(),
        ];
    }

    private function buildWhatsappUrl(): string
    {
        $number = (string) (getenv('IT_SUPPORT_WHATSAPP_NUMBER') ?: '6281399545044');
        $number = preg_replace('/\D+/', '', $number) ?: '6281399545044';
        $message = rawurlencode('');
        return 'https://wa.me/' . $number . '?text=' . $message;
    }

    private function formatIndonesianDate(DateTimeImmutable $date): string
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $month = $months[(int) $date->format('n')] ?? $date->format('F');
        return $date->format('d') . ' ' . $month . ' ' . $date->format('Y');
    }

    private function formatIndonesianTime(DateTimeImmutable $date): string
    {
        return $date->format('H:i:s');
    }
}
