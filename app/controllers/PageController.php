<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/UiModel.php';
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/AuthController.php';

if (!function_exists("mb_strlen")) { function mb_strlen($string, $encoding = null) { return strlen((string) $string); } }
if (!function_exists("mb_substr")) { function mb_substr($string, $start, $length = null, $encoding = null) { return $length === null ? substr((string) $string, (int) $start) : substr((string) $string, (int) $start, (int) $length); } }
if (!function_exists("mb_strtolower")) { function mb_strtolower($string, $encoding = null) { return strtolower((string) $string); } }
if (!function_exists("mb_strtoupper")) { function mb_strtoupper($string, $encoding = null) { return strtoupper((string) $string); } }
if (!function_exists("mb_str_split")) { function mb_str_split($string, $length = 1, $encoding = null) { return str_split((string) $string, max(1, (int) $length)); } }
if (!function_exists("str_contains")) { function str_contains($haystack, $needle) { return $needle === "" || strpos((string) $haystack, (string) $needle) !== false; } }

class PageController
{
    private UiModel $model;
    private string $lastFetchError = '';

    private array $pageMap = [
        'splash' => ['view' => 'pages/splash.php', 'layout' => 'standalone'],
        'login' => ['view' => 'pages/login.php', 'layout' => 'standalone'],
        'it-support-1' => ['view' => 'pages/it-support-1.php', 'layout' => 'standalone'],
        'it-support-2' => ['view' => 'pages/it-support-2.php', 'layout' => 'standalone'],
        'inventory-pc' => ['view' => 'pages/inventory-pc.php', 'layout' => 'app'],
        'inventory-other' => ['view' => 'pages/inventory-other.php', 'layout' => 'app'],
        'dashboard' => ['view' => 'pages/dashboard.php', 'layout' => 'app'],
        'data-inventaris' => ['view' => 'pages/data-inventaris.php', 'layout' => 'app'],
        'data-inventaris-subreg' => ['view' => 'pages/data-inventaris-subreg.php', 'layout' => 'app'],
        'inventaris-detail' => ['view' => 'pages/inventaris-detail.php', 'layout' => 'app'],
        'data-keluhan' => ['view' => 'pages/data-keluhan.php', 'layout' => 'app'],
        'log-barang'                 => ['view' => 'pages/log-barang.php', 'layout' => 'app'],
        'peminjaman-laptop'          => ['view' => 'pages/peminjaman-laptop.php', 'layout' => 'app'],
        'routine-monitoring'         => ['view' => 'pages/routine-monitoring.php', 'layout' => 'app'],
        'laporan' => ['view' => 'pages/laporan.php', 'layout' => 'app'],
        'account-settings' => ['view' => 'pages/account-settings.php', 'layout' => 'app'],
        'user-management' => ['view' => 'pages/user-management.php', 'layout' => 'app'],
        'notifikasi-alert' => ['view' => 'pages/notifikasi-alert.php', 'layout' => 'app'],
    ];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new UiModel();
    }

    public function render(string $page): void
    {
        // Silent background sync check on page load to keep things automatic
        $protectedPages = ['dashboard', 'data-inventaris', 'data-inventaris-subreg', 'inventaris-detail', 'data-keluhan', 'log-barang', 'peminjaman-laptop', 'routine-monitoring', 'laporan', 'account-settings', 'user-management', 'inventory-pc', 'inventory-other', 'notifikasi-alert'];
        if (in_array($page, $protectedPages, true) && AuthController::check()) {
            try {
                $pdo = Database::getConnection();
                $lastSync = (int) $this->getSetting($pdo, 'last_gform_sync_time', '0');
                if (time() - $lastSync > 5) { // check at most once every 5 seconds
                    $this->setSetting($pdo, 'last_gform_sync_time', (string) time());
                    $this->syncGoogleFormSubmissions($pdo, false, true);
                }

                // Auto-run alert scanner on page loads (throttled to once every 5 seconds)
                $lastAlertCheck = (int) $this->getSetting($pdo, 'last_alert_check_time', '0');
                if (time() - $lastAlertCheck > 5) {
                    $this->setSetting($pdo, 'last_alert_check_time', (string) time());
                    $this->buildAlertNotifications($pdo);
                }
            } catch (Throwable $e) {
                // Ignore database or curl errors silently to prevent blocking page render
            }
        }

        if ((string) ($_GET["ajax"] ?? "") === "get_unread_alert_count") {
            $this->jsonGetUnreadAlertCount();
            return;
        }
        if ((string) ($_GET["ajax"] ?? "") === "it_support_notifications") {
            $this->jsonItSupportNotifications();
            return;
        }
        if ((string) ($_GET["ajax"] ?? "") === "get_pc_list") {
            $this->jsonGetPcList();
            return;
        }
        if ((string) ($_GET["ajax"] ?? "") === "save_routine_monitoring_cell") {
            $this->jsonSaveRoutineMonitoringCell();
            return;
        }
        if ((string) ($_GET['page'] ?? '') !== '' && isset($_GET['mark_read_id'])) {
            $markId = (int) $_GET['mark_read_id'];
            if ($markId > 0) {
                try {
                    $pdo = Database::getConnection();
                    if ($pdo instanceof PDO) {
                        $stmt = $pdo->prepare(
                            "UPDATE alert_notifications
                             SET is_read = 1, dibaca_at = NOW(),
                                 dibaca_oleh = :username
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            'username' => $_SESSION['auth']['username'] ?? 'system',
                            'id' => $markId,
                        ]);
                    }
                } catch (Throwable $e) {
                    // abaikan, tidak boleh blocking render halaman
                }
            }
        }

        if (!isset($this->pageMap[$page])) {
            $page = 'splash';
        }

        $publicPages = ['splash', 'login'];
        $protectedPages = ['dashboard', 'data-inventaris', 'data-inventaris-subreg', 'inventaris-detail', 'data-keluhan', 'log-barang', 'peminjaman-laptop', 'routine-monitoring', 'laporan', 'account-settings', 'user-management', 'inventory-pc', 'inventory-other', 'notifikasi-alert'];
        if (in_array($page, ['it-support-1', 'it-support-2'], true)) {
            header('Location: it-support.php');
            exit;
        }

        if (!in_array($page, $publicPages, true) && !AuthController::check()) {
            header('Location: index.php?page=login');
            exit;
        }

        if ($page === 'login' && AuthController::check()) {
            header('Location: index.php?page=' . AuthController::defaultPageForCurrentUser());
            exit;
        }

        if (in_array($page, $protectedPages, true) && !AuthController::canAccessPage($page)) {
            http_response_code(403);
            echo 'Akses ditolak. Role Anda tidak memiliki akses ke halaman ini.';
            exit;
        }

        $filters = $this->resolveFilters();
        if ($page === 'data-inventaris') {
            $this->handleDivisionManagementAction();
        }
        if ($page === 'dashboard') {
            $this->handleDashboardAction($filters);
        }
        if ($page === 'inventaris-detail') {
            $this->handleInventoryAction($filters);
        }
        if ($page === 'log-barang') {
            $this->handleLogBarangAction($filters);
        }
        if ($page === 'routine-monitoring') {
            $this->handleRoutineMonitoringAction($filters);
        }
        if ($page === 'data-keluhan') {
            $this->handleComplaintAction();
        }
        if ($page === 'laporan') {
            $this->handleLaporanAction($filters);
        }
        if ($page === 'account-settings') {
            $this->handleAccountSettingsAction();
        }
        if ($page === 'user-management') {
            $this->handleUserManagementAction();
        }
        if (in_array($page, ['inventory-pc', 'inventory-other'], true)) {
            $this->handleNewInventoryAction($page);
        }
        if ($page === 'peminjaman-laptop') {
            $this->handlePeminjamanAction();
        }
        if ($page === 'notifikasi-alert') {
            $this->handleNotifikasiAlertAction();
        }

        $data = $this->model->getAll($page, $filters);
        if (!empty($_SESSION['auth']['email'])) {
            $data['user_email'] = $_SESSION['auth']['email'];
        }
        if (in_array($page, ['inventory-pc', 'inventory-other'], true)) {
            $data['inventory_form'] = $this->buildInventoryFormData();
        }
        if ($page === 'account-settings') {
            $authModel = new AuthModel();
            $data['account_user'] = $authModel->findUserById((int) ($_SESSION['auth']['id'] ?? 0)) ?: ($_SESSION['auth'] ?? []);
        }
        if (AuthController::isAdminSpmt()) {
            $authModelForAdmin = new AuthModel();
            $pendingUserNotif = $authModelForAdmin->fetchPendingUsersForNotification();
            $data['pending_user_count'] = $pendingUserNotif['count'];
            $data['pending_user_notifications'] = $pendingUserNotif;
        }
        $data['accessible_pages'] = AuthController::accessiblePages();
        if ($page === 'data-inventaris') {
            $data['is_admin_spmt'] = AuthController::isAdminSpmt();
            if (AuthController::isAdminSpmt()) {
                $data['division_management_rows'] = $this->fetchDivisionManagementRows();
            }
        }
        if ($page === 'inventaris-detail') {
            $data['inventory_division_options'] = $this->fetchInventoryDivisionOptions();
        }
        if ($page === 'log-barang') {
            $data['log_division_options'] = $this->fetchInventoryDivisionOptions();
            $data['users_options'] = (new AuthModel())->fetchUsersForAdmin('', 'active');
        }
        if ($page === 'routine-monitoring') {
            $data['routine_monitoring'] = $this->buildRoutineMonitoringData($filters);
        }
        if ($page === 'data-keluhan') {
            $pdo = Database::getConnection();
            $data['google_sheet_csv_url'] = $pdo instanceof PDO ? $this->getSetting($pdo, 'google_sheet_csv_url') : '';
        }
        if ($page === 'user-management') {
            $authModel = new AuthModel();
            $data['user_management_filters'] = [
                'search' => trim((string) ($_GET['search'] ?? '')),
                'status' => trim((string) ($_GET['status'] ?? 'all')),
            ];
            $data['user_management_rows'] = $authModel->fetchUsersForAdmin('', 'all');
            $data['divisions'] = $authModel->fetchActiveDivisions();
        }
        if ($page === 'laporan') {
            $pdoForReport = Database::getConnection();
            $data['report_filters'] = $this->buildLaporanFilters($filters);
            $data['report_division_options'] = $pdoForReport instanceof PDO ? $this->fetchReportDivisionOptions($pdoForReport) : [];
            if ($pdoForReport instanceof PDO) {
                if (trim((string) ($_GET['report_view'] ?? '')) !== '') {
                    $data['report_view'] = $this->buildLaporanViewData($pdoForReport, (string) $_GET['report_view'], $filters);
                }
            }
        }
        if ($page === 'peminjaman-laptop') {
            $pdo = Database::getConnection();
            if ($pdo instanceof PDO) {
                $pm = new PeminjamanModel();
                $pm->ensureTable($pdo);
                $filterStatus = trim((string) ($_GET['pinjam_filter'] ?? ''));
                $filterSearch = trim((string) ($_GET['pinjam_search'] ?? ''));
                $data['peminjaman_rows']         = $pm->fetchAll($pdo, $filterStatus, $filterSearch);
                $data['peminjaman_belum_kembali'] = $pm->fetchBelumKembali($pdo);
                $data['peminjaman_stats']         = $pm->fetchStats($pdo);
                $data['peminjaman_filter']        = $filterStatus;
                $data['peminjaman_search']        = $filterSearch;
            } else {
                $data['peminjaman_rows']         = [];
                $data['peminjaman_belum_kembali'] = [];
                $data['peminjaman_stats']         = ['total' => 0, 'dipinjam' => 0, 'kembali' => 0];
                $data['peminjaman_filter']        = '';
                $data['peminjaman_search']        = '';
            }
        }
        if ($page === 'notifikasi-alert') {
            if (!AuthController::canAccessAlertSystem()) {
                header('Location: index.php?page=dashboard');
                exit;
            }
            $pdo = Database::getConnection();
            if ($pdo instanceof PDO) {
                $data['alert_rows'] = $this->buildAlertNotifications($pdo);
                $data['alert_stats'] = $this->buildAlertStats($pdo);
                $data['alert_filters'] = [
                    'kategori' => trim((string) ($_GET['alert_kategori'] ?? '')),
                    'level' => trim((string) ($_GET['alert_level'] ?? '')),
                    'is_read' => trim((string) ($_GET['alert_read'] ?? '')),
                    'tindak_lanjut' => trim((string) ($_GET['alert_tindak_lanjut'] ?? '')),
                ];
            }
        }
        if (!empty($_SESSION['flash'])) {
            $data['flash'] = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        $data['page'] = $page;
        $data['page_map'] = $this->pageMap;
        $data['current_view'] = $this->pageMap[$page]['view'];
        $data['current_layout'] = $this->pageMap[$page]['layout'];

        ob_start();
        include __DIR__ . '/../views/layouts/main.php';
        $html = (string) ob_get_clean();
        echo $this->postProcessHtml($html, $page, $data);
    }

    private function resolveFilters(): array
    {
        $keys = ['division_code', 'division_id', 'user', 'email', 'display_division', 'user_page', 'focus_item', 'log_year', 'log_month', 'log_date', 'log_month_year', 'log_status', 'log_sort', 'log_search', 'complaint_status', 'complaint_division', 'complaint_search', 'complaint_date_from', 'complaint_date_to', 'report_date_from', 'report_date_to', 'report_division', 'report_month', 'report_year', 'report_user_role', 'report_user_division', 'report_all', 'report_category', 'routine_period', 'routine_date', 'routine_week', 'routine_month', 'routine_year'];
        $persisted = $_SESSION['spmt_context'] ?? [];

        if (isset($_GET['reset_context']) && $_GET['reset_context'] === '1') {
            $persisted = [];
        }

        foreach ($keys as $key) {
            if (!isset($_GET[$key])) {
                continue;
            }
            $value = trim((string) $_GET[$key]);
            if ($value === '') {
                unset($persisted[$key]);
                continue;
            }
            $persisted[$key] = $value;
        }

        $page = trim((string) ($_GET['page'] ?? 'dashboard'));
        
        if ($page === 'data-inventaris') {
            foreach (['division_code', 'division_id', 'display_division', 'user_page', 'user', 'email', 'focus_item'] as $inventoryKey) {
                unset($persisted[$inventoryKey]);
            }
        }
        if ($page === 'inventaris-detail') {
            $isAfterAddInventory = trim((string) ($_GET['after_add_inventory'] ?? '')) === '1';
            $hasExplicitUserTarget = array_key_exists('user', $_GET) || array_key_exists('email', $_GET) || array_key_exists('focus_item', $_GET);
            if ((array_key_exists('user_page', $_GET) || array_key_exists('division_code', $_GET)) && !$hasExplicitUserTarget) {
                unset($persisted['user'], $persisted['email'], $persisted['focus_item']);
            }
            if (!$isAfterAddInventory && !$hasExplicitUserTarget && !array_key_exists('user_page', $_GET)) {
                $persisted['user_page'] = '1';
            }
        }
        if ($page === 'data-keluhan') {
            $complaintKeys = ['complaint_status', 'complaint_division', 'complaint_search', 'complaint_date_from', 'complaint_date_to'];
            $hasComplaintQuery = false;
            foreach ($complaintKeys as $complaintKey) {
                if (array_key_exists($complaintKey, $_GET)) {
                    $hasComplaintQuery = true;
                    break;
                }
            }
            if (!$hasComplaintQuery && trim((string) ($_GET['action'] ?? '')) !== 'export') {
                foreach ($complaintKeys as $complaintKey) {
                    unset($persisted[$complaintKey]);
                }
            }
        }

        if ($page === 'laporan') {
            $reportKeys = ['report_date_from', 'report_date_to', 'report_division', 'report_month', 'report_year', 'report_user_role', 'report_user_division', 'report_all', 'routine_period', 'routine_date', 'routine_week'];
            $hasReportQuery = false;
            foreach ($reportKeys as $reportKey) {
                if (array_key_exists($reportKey, $_GET)) {
                    $hasReportQuery = true;
                    break;
                }
            }
            if (!$hasReportQuery) {
                foreach ($reportKeys as $reportKey) {
                    unset($persisted[$reportKey]);
                }
            }
        }

        if ($page === 'log-barang') {
            if (isset($_GET['log_month_year'])) {
                unset($persisted['log_year'], $persisted['log_month']);
            }
            if (isset($_GET['log_year']) || isset($_GET['log_month'])) {
                unset($persisted['log_month_year']);
            }
            
            $logPeriodKeys = ['log_year', 'log_month', 'log_date', 'log_month_year'];
            $hasLogPeriodQuery = false;
            foreach ($logPeriodKeys as $logPeriodKey) {
                if (array_key_exists($logPeriodKey, $_GET)) {
                    $hasLogPeriodQuery = true;
                    break;
                }
            }
            if (!$hasLogPeriodQuery && trim((string) ($_GET['action'] ?? '')) !== 'export') {
                foreach ($logPeriodKeys as $logPeriodKey) {
                    unset($persisted[$logPeriodKey]);
                }
            }
        }

        $_SESSION['spmt_context'] = $persisted;
        return $persisted;
    }




    private function currentDatabaseName(PDO $pdo): string
    {
        try {
            return (string) ($pdo->query('SELECT DATABASE()')->fetchColumn() ?: '');
        } catch (Throwable $e) {
            return '';
        }
    }

    private function tableExists(PDO $pdo, string $schema, string $table): bool
    {
        if ($schema === '' || $table === '') {
            return false;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table');
            $stmt->execute(['schema' => $schema, 'table' => $table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
    {
        if ($schema === '' || $table === '' || $column === '') {
            return false;
        }
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name = :column');
            $stmt->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function handleDivisionManagementAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $action = trim((string) ($_POST['division_action'] ?? ''));
        if (!in_array($action, ['add', 'update', 'activate', 'deactivate', 'delete'], true)) {
            return;
        }
        if (!AuthController::isAdminSpmt()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Akses ditolak. Fitur kelola divisi hanya untuk admin.spmt.'];
            header('Location: index.php?page=data-inventaris');
            exit;
        }
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
            header('Location: index.php?page=data-inventaris');
            exit;
        }

        try {
            if ($action === 'update') {
                $id = max(0, (int) ($_POST['division_id'] ?? 0));
                $label = $this->normalizeDivisionInput((string) ($_POST['division_label'] ?? ''));
                if ($id <= 0 || $label === '') {
                    throw new RuntimeException('Data divisi tidak lengkap.');
                }
                $sheetSource = strtoupper(trim((string) ($_POST['sheet_sumber'] ?? 'SPMT')));
                if (!in_array($sheetSource, ['SPMT', 'SUBREG'], true)) {
                    $sheetSource = 'SPMT';
                }
                $groupName = $label . '_' . $sheetSource;
                $stmt = $pdo->prepare('UPDATE master_divisi SET division_label = :label, division_group_name = :group_name, sheet_sumber = :sheet_sumber WHERE id = :id');
                $stmt->execute([
                    'label' => $label,
                    'group_name' => $groupName,
                    'sheet_sumber' => $sheetSource,
                    'id' => $id,
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Nama divisi berhasil diperbarui.'];
            } elseif ($action === 'activate' || $action === 'deactivate') {
                $id = max(0, (int) ($_POST['division_id'] ?? 0));
                if ($id <= 0) {
                    throw new RuntimeException('Divisi tidak valid.');
                }
                $activeValue = $action === 'activate' ? 1 : 0;
                $stmt = $pdo->prepare('UPDATE master_divisi SET is_active = :is_active WHERE id = :id');
                $stmt->execute(['is_active' => $activeValue, 'id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => $action === 'activate' ? 'Divisi berhasil diaktifkan kembali.' : 'Divisi berhasil dinonaktifkan dari tampilan.'];
            } elseif ($action === 'delete') {
                $id = max(0, (int) ($_POST['division_id'] ?? 0));
                if ($id <= 0) {
                    throw new RuntimeException('Divisi tidak valid.');
                }
                $stmt = $pdo->prepare('SELECT id, division_label, inventory_db_name FROM master_divisi WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $division = $stmt->fetch();
                if (!$division) {
                    throw new RuntimeException('Divisi tidak ditemukan.');
                }
                $dbName = trim((string) ($division['inventory_db_name'] ?? ''));
                if ($dbName !== '') {
                    if (!$this->isSafeIdentifier($dbName)) {
                        throw new RuntimeException('Nama database divisi tidak aman untuk dihapus.');
                    }
                    $quotedDb = '`' . str_replace('`', '``', $dbName) . '`';
                    $pdo->exec('DROP DATABASE IF EXISTS ' . $quotedDb);
                }
                if ($this->tableExists($pdo, $this->currentDatabaseName($pdo), 'users') && $this->columnExists($pdo, $this->currentDatabaseName($pdo), 'users', 'default_divisi_id')) {
                    $pdo->prepare('UPDATE users SET default_divisi_id = NULL WHERE default_divisi_id = :id')->execute(['id' => $id]);
                }
                foreach (['user_divisi', 'user_divisi_akses'] as $relationTable) {
                    if ($this->tableExists($pdo, $this->currentDatabaseName($pdo), $relationTable)) {
                        $pdo->prepare('DELETE FROM ' . $relationTable . ' WHERE divisi_id = :id')->execute(['id' => $id]);
                    }
                }
                $pdo->prepare('DELETE FROM master_divisi WHERE id = :id')->execute(['id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Divisi, database, dan seluruh isi database inventaris berhasil dihapus.'];
            } else {
                $label = $this->normalizeDivisionInput((string) ($_POST['division_label'] ?? ''));
                $sheetSource = strtoupper(trim((string) ($_POST['sheet_sumber'] ?? 'SPMT')));
                if (!in_array($sheetSource, ['SPMT', 'SUBREG'], true)) {
                    $sheetSource = 'SPMT';
                }
                if ($label === '') {
                    throw new RuntimeException('Nama divisi wajib diisi.');
                }
                $code = strtoupper(trim((string) ($_POST['division_code'] ?? '')));
                if ($code === '') {
                    $code = $sheetSource . '_' . $this->slugifyDivisionIdentifier($label);
                }
                $code = preg_replace('/[^A-Z0-9_]/', '_', $code) ?: '';
                $code = preg_replace('/_+/', '_', $code) ?: $code;
                $code = trim($code, '_');
                if ($code === '') {
                    throw new RuntimeException('Kode divisi tidak valid.');
                }
                if (substr($code, 0, strlen($sheetSource . '_')) !== $sheetSource . '_') {
                    $code = $sheetSource . '_' . $code;
                }

                $dbName = strtolower(trim((string) ($_POST['inventory_db_name'] ?? '')));
                if ($dbName === '') {
                    $dbName = 'db_' . strtolower($sheetSource) . '_' . strtolower($this->slugifyDivisionIdentifier($label));
                }
                $dbName = preg_replace('/[^a-z0-9_]/', '_', $dbName) ?: '';
                $dbName = preg_replace('/_+/', '_', $dbName) ?: $dbName;
                $dbName = trim($dbName, '_');
                if ($dbName === '' || !preg_match('/^[a-zA-Z0-9_]{3,64}$/', $dbName)) {
                    throw new RuntimeException('Nama database tidak valid. Gunakan huruf, angka, dan underscore.');
                }

                $sqlFile = strtolower($sheetSource) . '__' . strtolower($this->slugifyDivisionIdentifier($label)) . '.sql';
                $groupName = $label . '_' . $sheetSource;
                $stmt = $pdo->prepare('INSERT INTO master_divisi (division_code, sheet_sumber, division_group_name, division_label, inventory_db_name, sql_file_name, is_active) VALUES (:code, :sheet_sumber, :group_name, :label, :db_name, :sql_file, 1)');
                $stmt->execute([
                    'code' => $code,
                    'sheet_sumber' => $sheetSource,
                    'group_name' => $groupName,
                    'label' => $label,
                    'db_name' => $dbName,
                    'sql_file' => $sqlFile,
                ]);
                $this->ensureInventoryDatabase($pdo, $dbName);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Divisi dan database inventaris baru berhasil ditambahkan.'];
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengelola divisi: ' . $e->getMessage()];
        }
        header('Location: index.php?page=data-inventaris');
        exit;
    }

    private function fetchDivisionManagementRows(): array
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, division_code, sheet_sumber, division_label, inventory_db_name, is_active FROM master_divisi ORDER BY is_active DESC, sheet_sumber ASC, id ASC');
            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            return [];
        }
    }


    private function fetchInventoryDivisionOptions(): array
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT division_code, division_label, inventory_db_name, sheet_sumber, is_active FROM master_divisi WHERE is_active = 1 ORDER BY sheet_sumber ASC, division_label ASC, id ASC');
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchInventoryDivisionByCode(PDO $pdo, string $divisionCode): ?array
    {
        $divisionCode = trim($divisionCode);
        if ($divisionCode === '') {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM master_divisi WHERE division_code = :division_code AND is_active = 1 LIMIT 1');
        $stmt->execute(['division_code' => $divisionCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function normalizeDivisionInput(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value));
        return mb_strtoupper((string) $value, 'UTF-8');
    }

    private function slugifyDivisionIdentifier(string $value): string
    {
        $value = strtoupper($value);
        $value = str_replace(['&', '+'], ' DAN ', $value);
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?: '';
        $value = preg_replace('/_+/', '_', $value) ?: $value;
        return trim($value, '_');
    }

    private function ensureInventoryDatabase(PDO $pdo, string $dbName): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $dbName)) {
            throw new RuntimeException('Nama database tidak valid.');
        }
        $quotedDb = '`' . str_replace('`', '``', $dbName) . '`';
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $quotedDb . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $quotedDb . '.`pc` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `id_inventaris` varchar(255) DEFAULT NULL,
            `unit_kerja` varchar(255) DEFAULT NULL,
            `jenis_perangkat` varchar(255) DEFAULT NULL,
            `merk_perangkat` varchar(255) DEFAULT NULL,
            `computer_name` varchar(255) DEFAULT NULL,
            `user` varchar(255) DEFAULT NULL,
            `processor` varchar(255) DEFAULT NULL,
            `ram` varchar(255) DEFAULT NULL,
            `kapasitas_harddisk` varchar(255) DEFAULT NULL,
            `ip_address` varchar(255) DEFAULT NULL,
            `sistem_operasi` varchar(255) DEFAULT NULL,
            `licensed_windows` varchar(255) DEFAULT NULL,
            `microsoft_office` varchar(255) DEFAULT NULL,
            `licensed_office` varchar(255) DEFAULT NULL,
            `gambar` varchar(255) DEFAULT NULL,
            `status` varchar(100) DEFAULT "AKTIF",
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $quotedDb . '.`perangkat_lain` (
            `id_inventaris` varchar(255) DEFAULT NULL,
            `jenis_perangkat` varchar(255) DEFAULT NULL,
            `merk_perangkat` varchar(255) DEFAULT NULL,
            `unit_kerja` varchar(255) DEFAULT NULL,
            `user` varchar(255) DEFAULT NULL,
            `status` varchar(100) DEFAULT "AKTIF",
            `gambar` varchar(255) DEFAULT NULL,
            `pc_row_id` BIGINT NULL DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `last_edited_at` datetime DEFAULT NULL,
            `sync_at` datetime DEFAULT NULL,
            `edit_source` varchar(50) DEFAULT "manual"
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function handleDashboardAction(array $filters): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = trim((string) ($_POST['dashboard_action'] ?? $_POST['action'] ?? ''));
        if (!in_array($action, ['save_cctv', 'delete_cctv', 'edit_cctv_camera', 'delete_cctv_camera'], true)) {
            return;
        }

        if (AuthController::role() === 'user') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Akses ditolak. Sebagai User, Anda tidak diperbolehkan melakukan tindakan ini.'];
            header('Location: index.php?page=dashboard');
            exit;
        }

        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return;
        }

        try {
            $this->model->ensureCctvTable($pdo);

            if ($action === 'delete_cctv_camera') {
                $id = max(0, (int) ($_POST['cctv_cam_id'] ?? 0));
                if ($id <= 0) {
                    throw new RuntimeException('ID kamera tidak valid.');
                }
                $stmt = $pdo->prepare('DELETE FROM cctv_inventaris WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kamera CCTV berhasil dihapus.'];

            } elseif ($action === 'edit_cctv_camera') {
                $id     = max(0, (int) ($_POST['cctv_cam_id'] ?? 0));
                $nama   = trim((string) ($_POST['nama_cctv'] ?? ''));
                $status = strtoupper(trim((string) ($_POST['status'] ?? 'AKTIF')));
                if (!in_array($status, ['AKTIF', 'RUSAK', 'NONAKTIF'], true)) {
                    $status = 'AKTIF';
                }
                if ($id <= 0 || $nama === '') {
                    throw new RuntimeException('Data kamera tidak valid.');
                }
                $kode = '';
                if (preg_match('/^([A-Za-z0-9]+)[_\s\-]/', $nama, $matches)) {
                    $kode = strtoupper($matches[1]);
                }
                $stmt = $pdo->prepare('UPDATE cctv_inventaris SET nama_cctv = :nama, kode_cctv = :kode, status = :status WHERE id = :id');
                $stmt->execute(['nama' => $nama, 'kode' => $kode, 'status' => $status, 'id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kamera CCTV berhasil diperbarui.'];

            } elseif ($action === 'delete_cctv') {
                $id = max(0, (int) ($_POST['cctv_id'] ?? 0));
                if ($id <= 0) {
                    throw new RuntimeException('Data CCTV tidak valid.');
                }
                $stmt = $pdo->prepare('DELETE FROM dashboard_cctv WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data CCTV berhasil dihapus dan dashboard sudah diperbarui.'];
            } else {
                $id = max(0, (int) ($_POST['cctv_id'] ?? 0));
                $lokasi = trim((string) ($_POST['cctv_lokasi'] ?? ''));
                $jumlah = max(0, (int) ($_POST['cctv_jumlah'] ?? 0));
                $color = trim((string) ($_POST['cctv_color'] ?? '#5B8DEF'));
                if ($lokasi === '') {
                    throw new RuntimeException('Lokasi CCTV wajib diisi.');
                }
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    $color = '#5B8DEF';
                }
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE dashboard_cctv SET lokasi = :lokasi, jumlah = :jumlah, color = :color WHERE id = :id');
                    $stmt->execute(['lokasi' => $lokasi, 'jumlah' => $jumlah, 'color' => $color, 'id' => $id]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data CCTV berhasil diedit dan dashboard sudah diperbarui.'];
                } else {
                    $stmt = $pdo->prepare('INSERT INTO dashboard_cctv (lokasi, jumlah, color) VALUES (:lokasi, :jumlah, :color)');
                    $stmt->execute(['lokasi' => $lokasi, 'jumlah' => $jumlah, 'color' => $color]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data CCTV baru berhasil ditambahkan dan dashboard sudah diperbarui.'];
                }
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Aksi CCTV gagal: ' . $e->getMessage()];
        }

        header('Location: index.php?page=dashboard');
        exit;
    }

    private function handleAccountSettingsAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $userId = (int) ($_SESSION['auth']['id'] ?? 0);
        $name = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $errors = [];

        if ($userId <= 0) {
            $errors['general'] = 'Session user tidak valid. Silakan login ulang.';
        }
        if ($name === '') {
            $errors['nama_lengkap'] = 'Nama user wajib diisi.';
        }
        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }
        if ($password !== '' && mb_strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }

        try {
            $authModel = new AuthModel();
            if ($email !== '' && $userId > 0 && $authModel->isEmailUsedByOtherUser($email, $userId)) {
                $errors['email'] = 'Email sudah digunakan user lain.';
            }
            if ($errors) {
                $_SESSION['account_errors'] = $errors;
                $_SESSION['account_old'] = ['nama_lengkap' => $name, 'email' => $email];
                header('Location: ' . $this->buildAccountReturnUrl(true));
                exit;
            }

            $authModel->updateAccount($userId, $name, $email, $password !== '' ? $password : null);
            $freshUser = $authModel->findUserById($userId);
            if ($freshUser) {
                $_SESSION['auth']['nama_lengkap'] = $freshUser['nama_lengkap'];
                $_SESSION['auth']['email'] = $freshUser['email'];
                $_SESSION['auth']['username'] = $freshUser['username'];
                $_SESSION['auth']['role'] = $freshUser['role'];
                $_SESSION['auth']['default_divisi_id'] = $freshUser['default_divisi_id'];
                $_SESSION['auth']['unit_kerja_default'] = $freshUser['unit_kerja_default'];
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Setting akun berhasil diperbarui.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal memperbarui akun: ' . $e->getMessage()];
        }

        header('Location: ' . $this->buildAccountReturnUrl(false));
        exit;
    }

    private function handlePeminjamanAction(): void
    {
        $action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));

        if ($action === 'export_peminjaman') {
            if (!AuthController::canAccessPage('peminjaman-laptop')) {
                http_response_code(403);
                echo 'Akses ditolak.';
                exit;
            }
            $pdo = Database::getConnection();
            if (!$pdo instanceof PDO) {
                echo 'Koneksi database tidak tersedia.';
                exit;
            }
            $pm = new PeminjamanModel();
            $pm->ensureTable($pdo);
            try {
                $pm->exportExcel($pdo);
            } catch (Throwable $e) {
                echo 'Gagal export: ' . $e->getMessage();
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!in_array($action, ['save_peminjaman', 'save_pengembalian', 'edit_peminjaman', 'delete_peminjaman'], true)) {
            return;
        }
        if (!AuthController::canAccessPage('peminjaman-laptop')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Akses ditolak.'];
            header('Location: index.php?page=peminjaman-laptop');
            exit;
        }

        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
            header('Location: index.php?page=peminjaman-laptop');
            exit;
        }

        $pm = new PeminjamanModel();
        $pm->ensureTable($pdo);

        try {
            if ($action === 'save_peminjaman') {
                $pm->savePeminjaman($pdo, $_POST);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data peminjaman berhasil disimpan.'];
            } elseif ($action === 'save_pengembalian') {
                $pm->saveReturn($pdo, $_POST);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pengembalian berhasil disimpan.'];
            } elseif ($action === 'edit_peminjaman') {
                $pm->update($pdo, $_POST);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data peminjaman berhasil diperbarui.'];
            } elseif ($action === 'delete_peminjaman') {
                $id = (int) ($_POST['id'] ?? 0);
                $pm->delete($pdo, $id);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data peminjaman berhasil dihapus.'];
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Aksi gagal: ' . $e->getMessage()];
        }

        header('Location: index.php?page=peminjaman-laptop');
        exit;
    }

    private function ensureRoutineMonitoringTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS routine_monitoring_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(50) NOT NULL,
            icon_class VARCHAR(100) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_routine_category_name (category_name),
            INDEX idx_routine_category_active (is_active, category_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS routine_monitoring_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_group VARCHAR(50) NOT NULL DEFAULT "GATE",
            category_field VARCHAR(50) NULL,
            item_name VARCHAR(150) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_routine_item_name (item_name),
            INDEX idx_routine_item_group (item_group),
            INDEX idx_routine_item_active (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS routine_monitoring (
            id INT AUTO_INCREMENT PRIMARY KEY,
            period_type ENUM("daily", "weekly") NOT NULL DEFAULT "daily",
            period_key VARCHAR(20) NOT NULL,
            monitor_date DATE NOT NULL,
            item_id INT NULL,
            item_name VARCHAR(150) NOT NULL,
            condition_status VARCHAR(20) NOT NULL DEFAULT "BAIK",
            keterangan TEXT NULL,
            checked_by_user_id INT NULL,
            checked_by_name VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_routine_period_item (period_type, period_key, item_name),
            UNIQUE KEY uniq_routine_period_item_id (period_type, period_key, item_id),
            INDEX idx_routine_period (period_type, period_key),
            INDEX idx_routine_date (monitor_date),
            INDEX idx_routine_item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        try { $pdo->exec('ALTER TABLE routine_monitoring ADD COLUMN item_id INT NULL AFTER monitor_date'); } catch (Throwable $e) {}
        try { $pdo->exec('ALTER TABLE routine_monitoring MODIFY item_name VARCHAR(150) NOT NULL'); } catch (Throwable $e) {}
        try { $pdo->exec('UPDATE routine_monitoring SET condition_status = "BAIK" WHERE condition_status = "AMAN"'); } catch (Throwable $e) {}
        try { $pdo->exec('ALTER TABLE routine_monitoring MODIFY condition_status VARCHAR(20) NOT NULL DEFAULT "BAIK"'); } catch (Throwable $e) {}
        try { $pdo->exec('ALTER TABLE routine_monitoring ADD UNIQUE KEY uniq_routine_period_item_id (period_type, period_key, item_id)'); } catch (Throwable $e) {}
        try { $pdo->exec('ALTER TABLE routine_monitoring ADD INDEX idx_routine_item_id (item_id)'); } catch (Throwable $e) {}
        try { $pdo->exec('ALTER TABLE routine_monitoring_items ADD COLUMN category_field VARCHAR(50) NULL AFTER item_group'); } catch (Throwable $e) {}
        try { $pdo->exec('UPDATE routine_monitoring_items SET item_group = "GATE" WHERE item_group = "UMUM" OR item_group IS NULL OR item_group = ""'); } catch (Throwable $e) {}
        try { $pdo->exec('UPDATE routine_monitoring_items SET category_field = item_group WHERE category_field IS NULL OR category_field = ""'); } catch (Throwable $e) {}
        try { $pdo->exec('UPDATE routine_monitoring SET condition_status = "BAIK" WHERE condition_status = "AMAN"'); } catch (Throwable $e) {}

        $categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM routine_monitoring_categories')->fetchColumn();
        if ($categoryCount === 0) {
            $catStmt = $pdo->prepare('INSERT IGNORE INTO routine_monitoring_categories (category_name, icon_class, is_active) VALUES (:category_name, :icon_class, 1)');
            foreach ([
                ['GATE', 'fa-solid fa-door-open'],
                ['CCTV', 'fa-solid fa-video'],
                ['SERVER', 'fa-solid fa-server'],
            ] as $cat) {
                $catStmt->execute(['category_name' => $cat[0], 'icon_class' => $cat[1]]);
            }
        }

        $count = (int) $pdo->query('SELECT COUNT(*) FROM routine_monitoring_items')->fetchColumn();
        if ($count === 0) {
            $seed = [
                ['GATE', 'GATE 1'],
                ['GATE', 'GATE 2'],
                ['CCTV', 'CCTV GATE'],
                ['CCTV', 'CCTV LOBBY'],
                ['SERVER', 'SERVER UTAMA'],
                ['SERVER', 'SERVER BACKUP'],
            ];
            $stmt = $pdo->prepare('INSERT IGNORE INTO routine_monitoring_items (item_group, item_name, sort_order, is_active) VALUES (:item_group, :item_name, :sort_order, 1)');
            foreach ($seed as $idx => $row) {
                $stmt->execute(['item_group' => $row[0], 'item_name' => $row[1], 'sort_order' => ($idx + 1) * 10]);
            }
        }
    }

    private function routineDefaultItems(?PDO $pdo = null): array
    {
        if ($pdo instanceof PDO) {
            try {
                $this->ensureRoutineMonitoringTable($pdo);
                // Fetch non-CCTV items from database
                $stmt = $pdo->query('SELECT i.id, COALESCE(NULLIF(i.category_field, ""), i.item_group) AS item_group, i.item_name, i.sort_order, i.is_active FROM routine_monitoring_items i INNER JOIN routine_monitoring_categories c ON UPPER(c.category_name) = UPPER(COALESCE(NULLIF(i.category_field, ""), i.item_group)) AND c.is_active = 1 WHERE i.is_active = 1 AND UPPER(COALESCE(NULLIF(i.category_field, ""), i.item_group)) <> \'CCTV\' ORDER BY COALESCE(NULLIF(i.category_field, ""), i.item_group) ASC, i.sort_order ASC, i.item_name ASC');
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                
                // Fetch CCTV items dynamically from cctv_inventaris where status is active
                $cctvStmt = $pdo->query('SELECT id, nama_cctv, lokasi FROM cctv_inventaris WHERE status = \'AKTIF\' ORDER BY lokasi ASC, nama_cctv ASC');
                $cctvRows = $cctvStmt ? $cctvStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($cctvRows as $cctv) {
                    $rows[] = [
                        'id' => (int)$cctv['id'] + 10000, // Offset to avoid collision with general items
                        'item_group' => 'CCTV',
                        'item_name' => $cctv['nama_cctv'],
                        'lokasi' => $cctv['lokasi'],
                        'sort_order' => 100,
                        'is_active' => 1
                    ];
                }
                
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Throwable $e) {}
        }
        return [
            ['id' => 0, 'item_group' => 'GATE', 'item_name' => 'GATE 1', 'sort_order' => 10, 'is_active' => 1],
            ['id' => 10001, 'item_group' => 'CCTV', 'item_name' => 'CCTV GATE', 'lokasi' => 'GATE', 'sort_order' => 20, 'is_active' => 1],
            ['id' => 0, 'item_group' => 'SERVER', 'item_name' => 'SERVER UTAMA', 'sort_order' => 30, 'is_active' => 1],
        ];
    }

    private function normalizeRoutineStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        if ($status === 'KURANG_BAIK' || $status === 'KURANGBAIK' || $status === 'KURANG BAIK') {
            return 'KURANG BAIK';
        }
        if (in_array($status, ['BAIK', 'KURANG BAIK', 'BURUK', 'ON', 'OFF'], true)) {
            return $status;
        }
        return 'BAIK';
    }

    private function getRoutineItemLocationAndName(array $item, string $group): array
    {
        $group = strtoupper($group);
        $fullName = (string) ($item['item_name'] ?? '');
        
        if ($group === 'GATE') {
            $parts = explode(' - ', $fullName, 2);
            if (count($parts) === 2) {
                return [trim($parts[0]), trim($parts[1])];
            }
            return ['Lainnya', $fullName];
        }
        
        if ($group === 'CCTV') {
            $loc = trim((string) ($item['lokasi'] ?? ''));
            return [$loc !== '' ? $loc : 'Lainnya', $fullName];
        }
        
        return ['', $fullName];
    }

    private function routineAllItems(PDO $pdo): array
    {
        $this->ensureRoutineMonitoringTable($pdo);
        $stmt = $pdo->query('SELECT id, COALESCE(NULLIF(category_field, ""), item_group) AS item_group, item_name, sort_order, is_active FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ""), item_group)) <> \'UMUM\' AND UPPER(COALESCE(NULLIF(category_field, ""), item_group)) <> \'CCTV\' ORDER BY is_active DESC, COALESCE(NULLIF(category_field, ""), item_group) ASC, item_name ASC');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        try {
            $cctvStmt = $pdo->query('SELECT c.id, c.nama_cctv, c.lokasi, c.status, d.color FROM cctv_inventaris c LEFT JOIN dashboard_cctv d ON UPPER(TRIM(d.lokasi)) = UPPER(TRIM(c.lokasi)) ORDER BY c.status ASC, c.lokasi ASC, c.nama_cctv ASC');
            $cctvRows = $cctvStmt ? $cctvStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            // Gather all active/non-empty locations from actual cameras
            $locNames = [];
            foreach ($cctvRows as $cctv) {
                $loc = trim((string) ($cctv['lokasi'] ?? ''));
                if ($loc !== '' && $cctv['status'] === 'AKTIF') {
                    $locNames[] = $loc;
                }
            }
            $locNames = array_unique($locNames);
            natcasesort($locNames);
            $locNames = array_values($locNames);
            $locColorMap = [];
            foreach ($locNames as $idx => $name) {
                $locColorMap[strtoupper(trim($name))] = $this->model->getPaletteColor($idx);
            }

            foreach ($cctvRows as $cctv) {
                $rows[] = [
                    'id' => (int)$cctv['id'] + 10000,
                    'item_group' => 'CCTV',
                    'item_name' => $cctv['nama_cctv'],
                    'lokasi' => $cctv['lokasi'],
                    'color' => $locColorMap[strtoupper(trim($cctv['lokasi']))] ?? '#5B8DEF',
                    'sort_order' => 100,
                    'is_active' => ($cctv['status'] === 'AKTIF' ? 1 : 0)
                ];
            }
        } catch (Throwable $e) {}
        return $rows;
    }

    private function routineCategories(PDO $pdo, bool $activeOnly = true): array
    {
        $this->ensureRoutineMonitoringTable($pdo);
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $stmt = $pdo->query('SELECT id, category_name, icon_class, is_active FROM routine_monitoring_categories ' . $where . ' ORDER BY is_active DESC, category_name ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];
        $defaultIcons = [
            'GATE' => 'fa-solid fa-door-open',
            'CCTV' => 'fa-solid fa-video',
            'SERVER' => 'fa-solid fa-server',
        ];
        foreach ($rows as &$row) {
            $name = $this->normalizeRoutineItemGroup((string) ($row['category_name'] ?? 'GATE'));
            $row['category_name'] = $name;
            $row['icon_class'] = trim((string) ($row['icon_class'] ?? '')) ?: ($defaultIcons[$name] ?? 'fa-solid fa-list-check');
        }
        unset($row);
        return $rows;
    }


    private function normalizeRoutineItemGroup(string $group): string
    {
        $group = strtoupper(trim($group));
        $group = preg_replace('/[^A-Z0-9 _\-]/', '', $group) ?? '';
        $group = trim(preg_replace('/\s+/', ' ', $group) ?? $group);
        return $group !== '' ? $group : 'GATE';
    }

    private function normalizeRoutinePeriodType(string $period): string
    {
        return strtolower(trim($period)) === 'weekly' ? 'weekly' : 'daily';
    }

    private function routinePeriodContext(array $filters): array
    {
        $tz = new DateTimeZone('Asia/Jakarta');
        $today = new DateTimeImmutable('today', $tz);
        $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $month = (int) ($filters['routine_month'] ?? $_GET['routine_month'] ?? $today->format('n'));
        $year = (int) ($filters['routine_year'] ?? $_GET['routine_year'] ?? $today->format('Y'));
        if ($month < 1 || $month > 12) {
            $month = (int) $today->format('n');
        }
        if ($year < 2020 || $year > 2100) {
            $year = (int) $today->format('Y');
        }
        $search = trim((string) ($filters['routine_search'] ?? $_GET['routine_search'] ?? ''));
        $monthStart = DateTimeImmutable::createFromFormat('Y-n-j H:i:s', sprintf('%04d-%d-1 00:00:00', $year, $month), $tz);
        if (!$monthStart) {
            $monthStart = $today->modify('first day of this month')->setTime(0, 0, 0);
        }
        $monthEnd = $monthStart->modify('last day of this month');
        $days = [];
        for ($cursor = $monthStart; $cursor <= $monthEnd; $cursor = $cursor->modify('+1 day')) {
            $days[] = [
                'date' => $cursor->format('Y-m-d'),
                'day' => $cursor->format('j'),
                'day_name' => $cursor->format('D'),
                'label' => $cursor->format('d/m'),
            ];
        }
        return [
            'period_type' => 'daily',
            'period_key' => $today->format('Y-m-d'),
            'monitor_date' => $today->format('Y-m-d'),
            'date_value' => $today->format('Y-m-d'),
            'week_value' => $today->format('o-\WW'),
            'month_value' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'year_value' => (string) $year,
            'month_number' => $month,
            'year_number' => $year,
            'month_label' => ($monthNames[$month] ?? $monthStart->format('F')) . ' ' . $year,
            'start_date' => $monthStart->format('Y-m-d'),
            'end_date' => $monthEnd->format('Y-m-d'),
            'days' => $days,
            'search' => $search,
            'title' => 'Monitoring Bulan ' . (($monthNames[$month] ?? $monthStart->format('F')) . ' ' . $year),
        ];
    }
    private function buildRoutineMonitoringUrl(array $extra = []): string
    {
        $query = ['page' => 'routine-monitoring'];
        foreach (['routine_month', 'routine_year', 'routine_search', 'recap_date'] as $key) {
            $value = trim((string) ($_GET[$key] ?? $_POST[$key] ?? ''));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }
        foreach ($extra as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }
        return 'index.php?' . http_build_query($query);
    }
    private function handleRoutineMonitoringAction(array $filters): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
                header('Location: index.php?page=routine-monitoring');
                exit;
            }
            return;
        }
        $this->ensureRoutineMonitoringTable($pdo);
        if (($_GET['action'] ?? '') === 'export_routine_pdf' || ($_GET['action'] ?? '') === 'export_routine_pdf_pivot') {
            $this->streamRoutineMonitoringPivotPdf($filters);
            exit;
        }
        if (($_GET['action'] ?? '') === 'export_routine_xlsx_pivot') {
            $this->streamRoutineMonitoringPivotExcel($filters);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $action = trim((string) ($_POST['action'] ?? ''));

        // Auto-save checklist matrix if it is passed in any routine monitoring POST action (except main save action)
        if ($action !== 'save_routine_monitoring' && isset($_POST['matrix_json'])) {
            try {
                $this->saveRoutineMonitoringMatrix($pdo, (string) $_POST['matrix_json'], $filters);
            } catch (Throwable $e) {
                // Silently ignore auto-save errors during secondary actions to prevent blocking the modal actions
            }
        }

        if (in_array($action, ['add_routine_category', 'update_routine_category', 'delete_routine_category'], true)) {
            if (!AuthController::isAdminSpmt()) {
                http_response_code(403);
                echo 'Akses ditolak. Kelola kategori checking khusus admin.spmt.';
                exit;
            }
            try {
                if ($action === 'add_routine_category') {
                    $name = $this->normalizeRoutineItemGroup((string) ($_POST['category_name'] ?? ''));
                    $icon = match ($name) {
                        'GATE' => 'fa-solid fa-door-open',
                        'CCTV' => 'fa-solid fa-video',
                        'SERVER' => 'fa-solid fa-server',
                        default => 'fa-solid fa-list-check',
                    };
                    $stmt = $pdo->prepare('INSERT INTO routine_monitoring_categories (category_name, icon_class, is_active) VALUES (:category_name, :icon_class, 1) ON DUPLICATE KEY UPDATE is_active = 1, updated_at = CURRENT_TIMESTAMP');
                    $stmt->execute(['category_name' => $name, 'icon_class' => $icon]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori checking berhasil ditambahkan.'];
                } elseif ($action === 'update_routine_category') {
                    $categoryId = max(0, (int) ($_POST['category_id'] ?? 0));
                    $name = $this->normalizeRoutineItemGroup((string) ($_POST['category_name'] ?? ''));
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    if ($categoryId <= 0) { throw new RuntimeException('Kategori tidak valid.'); }
                    $oldStmt = $pdo->prepare('SELECT category_name FROM routine_monitoring_categories WHERE id = :id');
                    $oldStmt->execute(['id' => $categoryId]);
                    $oldName = $this->normalizeRoutineItemGroup((string) ($oldStmt->fetchColumn() ?: ''));
                    $stmt = $pdo->prepare('UPDATE routine_monitoring_categories SET category_name = :category_name, is_active = :is_active WHERE id = :id');
                    $stmt->execute(['category_name' => $name, 'is_active' => $isActive, 'id' => $categoryId]);
                    if ($oldName !== '' && $oldName !== $name) {
                        $sync = $pdo->prepare('UPDATE routine_monitoring_items SET item_group = :new_name, category_field = :category_field WHERE UPPER(COALESCE(NULLIF(category_field, ""), item_group)) = UPPER(:old_name)');
                        $sync->execute(['new_name' => $name, 'category_field' => $name, 'old_name' => $oldName]);
                    }
                    if ($isActive === 0) {
                        $off = $pdo->prepare('UPDATE routine_monitoring_items SET is_active = 0 WHERE UPPER(COALESCE(NULLIF(category_field, ""), item_group)) = UPPER(:category_name)');
                        $off->execute(['category_name' => $name]);
                    }
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori checking berhasil diperbarui.'];
                } else {
                    $categoryId = max(0, (int) ($_POST['category_id'] ?? 0));
                    if ($categoryId <= 0) { throw new RuntimeException('Kategori tidak valid.'); }
                    $oldStmt = $pdo->prepare('SELECT category_name FROM routine_monitoring_categories WHERE id = :id');
                    $oldStmt->execute(['id' => $categoryId]);
                    $oldName = $this->normalizeRoutineItemGroup((string) ($oldStmt->fetchColumn() ?: ''));
                    
                    // 1. Delete category
                    $stmt = $pdo->prepare('DELETE FROM routine_monitoring_categories WHERE id = :id');
                    $stmt->execute(['id' => $categoryId]);
                    
                    if ($oldName !== '') {
                        if (strtoupper($oldName) === 'CCTV') {
                            // Delete all CCTV cameras from inventaris
                            $pdo->exec('DELETE FROM cctv_inventaris');
                            // Delete all CCTV checklist records from routine_monitoring (item_id >= 10000)
                            $pdo->exec('DELETE FROM routine_monitoring WHERE item_id >= 10000');
                            // Clear CCTV locations from dashboard
                            $pdo->exec('DELETE FROM dashboard_cctv');
                        } else {
                            // 2. Fetch all item IDs belonging to this category to clean routine_monitoring history
                            $itemsStmt = $pdo->prepare('SELECT id FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ""), item_group)) = UPPER(:category_name)');
                            $itemsStmt->execute(['category_name' => $oldName]);
                            $itemIds = $itemsStmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (!empty($itemIds)) {
                                $inClause = implode(',', array_map('intval', $itemIds));
                                $pdo->exec("DELETE FROM routine_monitoring WHERE item_id IN ($inClause)");
                            }
                            
                            // 3. Delete the items themselves
                            $delItems = $pdo->prepare('DELETE FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ""), item_group)) = UPPER(:category_name)');
                            $delItems->execute(['category_name' => $oldName]);
                        }
                    }
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori checking dan semua item didalamnya berhasil dihapus dari database.'];
                }
            } catch (Throwable $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengelola kategori checking: ' . $e->getMessage()];
            }
            header('Location: ' . $this->buildRoutineMonitoringUrl());
            exit;
        }

        if (in_array($action, ['add_routine_item', 'update_routine_item', 'delete_routine_item'], true)) {
            if (!AuthController::isAdminSpmt()) {
                http_response_code(403);
                echo 'Akses ditolak. Kelola item checking khusus admin.spmt.';
                exit;
            }
            try {
                if ($action === 'add_routine_item') {
                    $group = $this->normalizeRoutineItemGroup((string) ($_POST['item_group'] ?? 'GATE'));
                    $name = trim((string) ($_POST['item_name'] ?? ''));
                    $lokasi = trim((string) ($_POST['lokasi'] ?? 'UMUM'));
                    if ($name === '') {
                        throw new RuntimeException('Nama checking wajib diisi.');
                    }
                    if ($group === 'CCTV') {
                        $locClean = $lokasi === '' ? 'UMUM' : $lokasi;
                        $color = $this->model->stringToColor($locClean);

                        $kode = '';
                        if (preg_match('/^([A-Za-z0-9]+)[_\s\-]/', $name, $matches)) {
                            $kode = strtoupper($matches[1]);
                        }
                        $stmt = $pdo->prepare('INSERT INTO cctv_inventaris (nama_cctv, kode_cctv, lokasi, status) VALUES (:nama_cctv, :kode_cctv, :lokasi, \'AKTIF\')');
                        $stmt->execute(['nama_cctv' => $name, 'kode_cctv' => $kode, 'lokasi' => $locClean]);

                        // Sync location color in dashboard_cctv
                        $chk = $pdo->prepare('SELECT id FROM dashboard_cctv WHERE UPPER(TRIM(lokasi)) = UPPER(TRIM(:lokasi))');
                        $chk->execute(['lokasi' => $locClean]);
                        $cctvDbId = $chk->fetchColumn();
                        if ($cctvDbId) {
                            $upd = $pdo->prepare('UPDATE dashboard_cctv SET color = :color WHERE id = :id');
                            $upd->execute(['color' => $color, 'id' => $cctvDbId]);
                        } else {
                            $ins = $pdo->prepare('INSERT INTO dashboard_cctv (lokasi, jumlah, color) VALUES (:lokasi, 1, :color)');
                            $ins->execute(['lokasi' => $locClean, 'color' => $color]);
                        }

                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kamera CCTV berhasil ditambahkan ke inventaris.'];
                    } else {
                        if ($group === 'GATE') {
                            $locClean = $lokasi === '' ? 'UMUM' : $lokasi;
                            $name = $locClean . ' - ' . $name;
                        }
                        $sortOrder = (int) ($pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM routine_monitoring_items')->fetchColumn() ?: 10);
                        $stmt = $pdo->prepare('INSERT INTO routine_monitoring_items (item_group, category_field, item_name, sort_order, is_active) VALUES (:item_group, :category_field, :item_name, :sort_order, 1)');
                        $stmt->execute(['item_group' => $group, 'category_field' => $group, 'item_name' => $name, 'sort_order' => $sortOrder]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item checking berhasil ditambahkan.'];
                    }
                } elseif ($action === 'update_routine_item') {
                    $itemId = max(0, (int) ($_POST['item_id'] ?? 0));
                    $group = $this->normalizeRoutineItemGroup((string) ($_POST['item_group'] ?? 'GATE'));
                    $name = trim((string) ($_POST['item_name'] ?? ''));
                    $lokasi = trim((string) ($_POST['lokasi'] ?? ''));
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    if ($itemId <= 0) {
                        throw new RuntimeException('Item checking tidak valid.');
                    }
                    if ($name === '') {
                        throw new RuntimeException('Nama checking wajib diisi.');
                    }
                    if ($group === 'CCTV' || $itemId >= 10000) {
                        $actualId = $itemId >= 10000 ? ($itemId - 10000) : $itemId;
                        $status = $isActive ? 'AKTIF' : 'NONAKTIF';
                        $locClean = $lokasi === '' ? 'UMUM' : $lokasi;

                        $color = $this->model->stringToColor($locClean);

                        $kode = '';
                        if (preg_match('/^([A-Za-z0-9]+)[_\s\-]/', $name, $matches)) {
                            $kode = strtoupper($matches[1]);
                        }
                        $stmt = $pdo->prepare('UPDATE cctv_inventaris SET nama_cctv = :nama, kode_cctv = :kode, lokasi = :lokasi, status = :status WHERE id = :id');
                        $stmt->execute(['nama' => $name, 'kode' => $kode, 'lokasi' => $locClean, 'status' => $status, 'id' => $actualId]);

                        // Sync location color in dashboard_cctv
                        $chk = $pdo->prepare('SELECT id FROM dashboard_cctv WHERE UPPER(TRIM(lokasi)) = UPPER(TRIM(:lokasi))');
                        $chk->execute(['lokasi' => $locClean]);
                        $cctvDbId = $chk->fetchColumn();
                        if ($cctvDbId) {
                            $upd = $pdo->prepare('UPDATE dashboard_cctv SET color = :color WHERE id = :id');
                            $upd->execute(['color' => $color, 'id' => $cctvDbId]);
                        } else {
                            $ins = $pdo->prepare('INSERT INTO dashboard_cctv (lokasi, jumlah, color) VALUES (:lokasi, 1, :color)');
                            $ins->execute(['lokasi' => $locClean, 'color' => $color]);
                        }

                        // Clean up any empty locations from dashboard_cctv
                        $pdo->exec("DELETE FROM dashboard_cctv WHERE UPPER(TRIM(lokasi)) NOT IN (SELECT DISTINCT UPPER(TRIM(lokasi)) FROM cctv_inventaris)");

                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data Kamera CCTV berhasil diperbarui.'];
                    } else {
                        if ($group === 'GATE') {
                            $locClean = $lokasi === '' ? 'UMUM' : $lokasi;
                            $name = $locClean . ' - ' . $name;
                        }
                        $oldStmt = $pdo->prepare('SELECT item_name FROM routine_monitoring_items WHERE id = :id');
                        $oldStmt->execute(['id' => $itemId]);
                        $oldName = trim((string) ($oldStmt->fetchColumn() ?: ''));
                        $stmt = $pdo->prepare('UPDATE routine_monitoring_items SET item_group = :item_group, category_field = :category_field, item_name = :item_name, is_active = :is_active WHERE id = :id');
                        $stmt->execute(['item_group' => $group, 'category_field' => $group, 'item_name' => $name, 'is_active' => $isActive, 'id' => $itemId]);
                        if ($oldName !== '' && $oldName !== $name) {
                            $sync = $pdo->prepare('UPDATE routine_monitoring SET item_name = :new_name WHERE item_id = :item_id OR item_name = :old_name');
                            $sync->execute(['new_name' => $name, 'item_id' => $itemId, 'old_name' => $oldName]);
                        }
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item checking berhasil diperbarui.'];
                    }
                } else {
                    $itemId = max(0, (int) ($_POST['item_id'] ?? 0));
                    $group = $this->normalizeRoutineItemGroup((string) ($_POST['item_group'] ?? 'GATE'));
                    if ($itemId <= 0) {
                        throw new RuntimeException('Item checking tidak valid.');
                    }
                    if ($group === 'CCTV' || $itemId >= 10000) {
                        $actualId = $itemId >= 10000 ? ($itemId - 10000) : $itemId;
                        $mappedId = $actualId + 10000;
                        
                        // Delete camera from inventaris
                        $stmt = $pdo->prepare('DELETE FROM cctv_inventaris WHERE id = :id');
                        $stmt->execute(['id' => $actualId]);
                        
                        // Delete related monitoring checklist data
                        $stmtRm = $pdo->prepare('DELETE FROM routine_monitoring WHERE item_id = :item_id');
                        $stmtRm->execute(['item_id' => $mappedId]);

                        // Clean up any empty locations from dashboard_cctv
                        $pdo->exec("DELETE FROM dashboard_cctv WHERE UPPER(TRIM(lokasi)) NOT IN (SELECT DISTINCT UPPER(TRIM(lokasi)) FROM cctv_inventaris)");
                        
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kamera CCTV berhasil dihapus dari database.'];
                    } else {
                        // Delete item from routine_monitoring_items
                        $stmt = $pdo->prepare('DELETE FROM routine_monitoring_items WHERE id = :id');
                        $stmt->execute(['id' => $itemId]);
                        
                        // Delete related checks from routine_monitoring
                        $stmtRm = $pdo->prepare('DELETE FROM routine_monitoring WHERE item_id = :item_id');
                        $stmtRm->execute(['item_id' => $itemId]);
                        
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item checking berhasil dihapus dari database.'];
                    }
                }
            } catch (Throwable $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengelola item checking: ' . $e->getMessage()];
            }
            header('Location: ' . $this->buildRoutineMonitoringUrl());
            exit;
        }

        if ($action !== 'save_routine_monitoring') {
            return;
        }
        $context = $this->routinePeriodContext($filters);
        $matrixJson = (string) ($_POST['matrix_json'] ?? '');
        $saveCategoryRaw = trim((string) ($_POST['save_category'] ?? ''));
        try {
            $this->saveRoutineMonitoringMatrix($pdo, $matrixJson, $filters, $saveCategoryRaw);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Checklist Routine Monitoring berhasil disimpan.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal menyimpan Routine Monitoring: ' . $e->getMessage()];
        }
        header('Location: ' . $this->buildRoutineMonitoringUrl([
            'routine_month' => $context['month_value'],
            'routine_year' => $context['year_value'],
            'routine_search' => $context['search'],
        ]));
        exit;
    }

    private function saveRoutineMonitoringMatrix(PDO $pdo, string $matrixJson, array $filters, ?string $saveCategoryRaw = null): void
    {
        $context = $this->routinePeriodContext($filters);
        $postedItems = [];
        $decoded = json_decode((string) $matrixJson, true);
        if (is_array($decoded)) {
            $postedItems = $decoded;
        }
        if (empty($postedItems)) {
            return;
        }
        $auth = $_SESSION['auth'] ?? [];
        $saveCategory = $saveCategoryRaw !== null && trim($saveCategoryRaw) !== '' ? $this->normalizeRoutineItemGroup($saveCategoryRaw) : '';
        
        $routineItems = $this->routineDefaultItems($pdo);
        $upsert = $pdo->prepare('INSERT INTO routine_monitoring (period_type, period_key, monitor_date, item_id, item_name, condition_status, keterangan, checked_by_user_id, checked_by_name) VALUES (:period_type, :period_key, :monitor_date, :item_id, :item_name, :condition_status, :keterangan, :checked_by_user_id, :checked_by_name) ON DUPLICATE KEY UPDATE item_name = VALUES(item_name), condition_status = VALUES(condition_status), keterangan = VALUES(keterangan), checked_by_user_id = VALUES(checked_by_user_id), checked_by_name = VALUES(checked_by_name), updated_at = CURRENT_TIMESTAMP');
        $tz = new DateTimeZone('Asia/Jakarta');
        foreach ($routineItems as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $itemName = trim((string) ($item['item_name'] ?? ''));
            $itemGroup = $this->normalizeRoutineItemGroup((string) ($item['item_group'] ?? 'GATE'));
            if ($itemId <= 0 || $itemName === '') {
                continue;
            }
            if ($saveCategory !== '' && $saveCategory !== $itemGroup) {
                continue;
            }
            $itemRows = $postedItems[$itemId] ?? [];
            if (!is_array($itemRows) || empty($itemRows)) {
                continue;
            }
            $deleteStmt = $pdo->prepare('DELETE FROM routine_monitoring WHERE period_type = :period_type AND period_key = :period_key AND item_id = :item_id');
            foreach ($itemRows as $monitorDate => $posted) {
                if (!is_array($posted)) {
                    continue;
                }
                $statusRaw = trim((string) ($posted['condition_status'] ?? ''));
                try {
                    $dateObj = new DateTimeImmutable((string) $monitorDate, $tz);
                } catch (Throwable $e) {
                    continue;
                }
                $dateKey = $dateObj->format('Y-m-d');
                if ($dateKey < $context['start_date'] || $dateKey > $context['end_date']) {
                    continue;
                }
                if ($statusRaw === '') {
                    // User set ke "-": hapus data yang ada agar tampil kembali kosong
                    $deleteStmt->execute([
                        'period_type' => 'daily',
                        'period_key'  => $dateKey,
                        'item_id'     => $itemId,
                    ]);
                    continue;
                }
                $params = [
                    'period_type'       => 'daily',
                    'period_key'        => $dateKey,
                    'monitor_date'      => $dateKey,
                    'item_id'           => $itemId,
                    'item_name'         => $itemName,
                    'condition_status'  => $this->normalizeRoutineStatus($statusRaw),
                    'keterangan'        => trim((string) ($posted['keterangan'] ?? '')) !== '' ? trim((string) ($posted['keterangan'] ?? '')) : null,
                    'checked_by_user_id' => (int) ($auth['id'] ?? 0) ?: null,
                    'checked_by_name'   => (string) ($auth['nama_lengkap'] ?? $auth['username'] ?? ''),
                ];
                $upsert->execute($params);
            }
        }
    }
    private function buildRoutineMonitoringData(array $filters): array
    {
        $context = $this->routinePeriodContext($filters);
        $search = strtolower(trim((string) ($context['search'] ?? '')));
        $items = [];
        $manageItems = [];
        $categories = [];
        $manageCategories = [];
        $recapByDate = [];
        $pdo = Database::getConnection();
        if ($pdo instanceof PDO) {
            try {
                $this->ensureRoutineMonitoringTable($pdo);
                $categories = $this->routineCategories($pdo, true);
                $manageCategories = $this->routineCategories($pdo, true);
                foreach ($this->routineDefaultItems($pdo) as $item) {
                    $itemId = (int) ($item['id'] ?? 0);
                    $itemName = trim((string) ($item['item_name'] ?? ''));
                    $itemGroup = $this->normalizeRoutineItemGroup((string) ($item['item_group'] ?? 'GATE'));
                    if ($itemId <= 0 || $itemName === '' || $itemGroup === 'UMUM') {
                        continue;
                    }
                    if ($search !== '' && stripos($itemName, $search) === false && stripos($itemGroup, $search) === false && (!isset($item['lokasi']) || stripos($item['lokasi'], $search) === false)) {
                        continue;
                    }
                    $calendar = [];
                    foreach (($context['days'] ?? []) as $day) {
                        $calendar[(string) ($day['date'] ?? '')] = [
                            'condition_status' => '',
                            'keterangan' => '',
                            'updated_at' => '',
                            'checked_by_name' => '',
                        ];
                    }
                    $items[$itemId] = [
                        'id' => $itemId,
                        'item_group' => $itemGroup,
                        'item_name' => $itemName,
                        'lokasi' => $item['lokasi'] ?? '',
                        'calendar' => $calendar,
                    ];
                }
                $manageItems = $this->routineAllItems($pdo);
                $stmt = $pdo->prepare('SELECT item_id, item_name, monitor_date, condition_status, keterangan, checked_by_name, updated_at FROM routine_monitoring WHERE period_type = :period_type AND monitor_date BETWEEN :start_date AND :end_date ORDER BY monitor_date ASC, item_name ASC');
                $stmt->execute(['period_type' => 'daily', 'start_date' => $context['start_date'], 'end_date' => $context['end_date']]);
                foreach ($stmt->fetchAll() as $row) {
                    $itemId = (int) ($row['item_id'] ?? 0);
                    $monitorDate = (string) ($row['monitor_date'] ?? '');
                    if ($itemId > 0 && isset($items[$itemId]) && isset($items[$itemId]['calendar'][$monitorDate])) {
                        $status = $this->normalizeRoutineStatus((string) ($row['condition_status'] ?? 'BAIK'));
                        $items[$itemId]['calendar'][$monitorDate] = [
                            'condition_status' => $status,
                            'keterangan' => (string) ($row['keterangan'] ?? ''),
                            'updated_at' => (string) ($row['updated_at'] ?? ''),
                            'checked_by_name' => (string) ($row['checked_by_name'] ?? ''),
                        ];
                    }
                }
            } catch (Throwable $e) {
            }
        }

        $groupedItems = [];
        $summaryByGroup = [];
        $groupOrder = ['GATE', 'CCTV', 'SERVER'];
        foreach ($items as $item) {
            $group = $this->normalizeRoutineItemGroup((string) ($item['item_group'] ?? 'GATE'));
            if (!isset($groupedItems[$group])) {
                $groupedItems[$group] = [];
            }
            if (!isset($summaryByGroup[$group])) {
                $summaryByGroup[$group] = ['BAIK' => [], 'KURANG BAIK' => [], 'BURUK' => [], 'ON' => [], 'OFF' => []];
            }
            foreach (($item['calendar'] ?? []) as $date => $cell) {
                $status = trim((string) ($cell['condition_status'] ?? ''));
                if ($status === '') {
                    continue;
                }
                if (!isset($recapByDate[$date])) {
                    $recapByDate[$date] = [];
                }
                if (!isset($recapByDate[$date][$group])) {
                    $recapByDate[$date][$group] = ['BAIK' => [], 'KURANG BAIK' => [], 'BURUK' => [], 'ON' => [], 'OFF' => []];
                }
                $itemName = (string) ($item['item_name'] ?? '-');
                $lokasi = (string) ($item['lokasi'] ?? '');
                if ($group === 'GATE') {
                    $parts = explode(' - ', $itemName, 2);
                    if (count($parts) === 2) {
                        $lokasi = trim($parts[0]);
                        $itemName = trim($parts[1]);
                    }
                }
                $entry = [
                    'item_name' => $itemName,
                    'lokasi' => $lokasi,
                    'condition_status' => $status,
                    'keterangan' => (string) ($cell['keterangan'] ?? ''),
                    'updated_at' => (string) ($cell['updated_at'] ?? ''),
                ];
                $recapByDate[$date][$group][$status][] = $entry;
                $summaryByGroup[$group][$status][] = $entry;
            }
            $groupedItems[$group][] = $item;
        }

        uksort($groupedItems, static function ($a, $b) use ($groupOrder) {
            $ia = array_search($a, $groupOrder, true);
            $ib = array_search($b, $groupOrder, true);
            $ia = $ia === false ? 99 : $ia;
            $ib = $ib === false ? 99 : $ib;
            return $ia <=> $ib ?: strcmp((string) $a, (string) $b);
        });
        uksort($summaryByGroup, static function ($a, $b) use ($groupOrder) {
            $ia = array_search($a, $groupOrder, true);
            $ib = array_search($b, $groupOrder, true);
            $ia = $ia === false ? 99 : $ia;
            $ib = $ib === false ? 99 : $ib;
            return $ia <=> $ib ?: strcmp((string) $a, (string) $b);
        });

        return [
            'context' => $context,
            'items' => array_values($items),
            'grouped_items' => $groupedItems,
            'manage_items' => $manageItems,
            'categories' => $categories,
            'manage_categories' => $manageCategories,
            'summary_by_group' => $summaryByGroup,
            'recap_by_date' => $recapByDate,
        ];
    }
    private function streamRoutineMonitoringPivotPdf(array $filters): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return;
        }

        $scope = strtolower(trim((string) ($_GET['recap_scope'] ?? 'week')));
        if (!in_array($scope, ['day', 'week', 'month'], true)) {
            $scope = 'week';
        }

        $yearNum = (int) ($filters['routine_year'] ?? $_GET['routine_year'] ?? date('Y'));
        $monthNum = (int) ($filters['routine_month'] ?? $_GET['routine_month'] ?? date('m'));
        if ($monthNum < 1 || $monthNum > 12) { $monthNum = (int)date('m'); }
        if ($yearNum < 2020 || $yearNum > 2100) { $yearNum = (int)date('Y'); }

        $daysInMonth = (int)date('t', mktime(0, 0, 0, $monthNum, 1, $yearNum));

        // Setup Date Range depending on Scope
        if ($scope === 'day') {
            $requestedDate = trim((string) ($_GET['recap_date'] ?? ''));
            if ($requestedDate === '') {
                $requestedDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            }
            $rangeStart = $requestedDate;
            $rangeEnd   = $requestedDate;
            $titleSuffix = date('d/m/Y', strtotime($requestedDate));
            $baseSuffix  = preg_replace('/[^A-Za-z0-9_-]+/', '-', $requestedDate);
        } elseif ($scope === 'week') {
            $weekStartInput = trim((string) ($_GET['week_start'] ?? $_GET['recap_date'] ?? ''));
            if ($weekStartInput === '') {
                $weekStartInput = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            }
            try {
                $weekStartDate = new DateTimeImmutable($weekStartInput);
            } catch (Throwable $e) {
                $weekStartDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $yearNum, $monthNum));
            }
            $weekStartDate = $weekStartDate->modify('monday this week');
            $weekEndDate   = $weekStartDate->modify('+6 days');
            
            $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $yearNum, $monthNum));
            $monthEnd   = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth));
            if ($weekStartDate < $monthStart) { $weekStartDate = $monthStart; }
            if ($weekEndDate   > $monthEnd)   { $weekEndDate   = $monthEnd;   }
            
            $rangeStart  = $weekStartDate->format('Y-m-d');
            $rangeEnd    = $weekEndDate->format('Y-m-d');
            $titleSuffix = date('d/m/Y', strtotime($rangeStart)) . ' - ' . date('d/m/Y', strtotime($rangeEnd));
            $baseSuffix  = $rangeStart . '-sd-' . $rangeEnd;
        } else { // month
            $rangeStart = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            $rangeEnd   = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth);
            $monthNamesIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $monthName = $monthNamesIndo[$monthNum] ?? date('F', mktime(0, 0, 0, $monthNum, 1, $yearNum));
            $titleSuffix = $monthName . ' ' . $yearNum;
            $baseSuffix  = sprintf('%04d-%02d', $yearNum, $monthNum);
        }

        // Collect dates in range
        $rangeDates = [];
        $cur = new DateTimeImmutable($rangeStart);
        $end = new DateTimeImmutable($rangeEnd);
        while ($cur <= $end) {
            $rangeDates[] = $cur->format('Y-m-d');
            $cur = $cur->modify('+1 day');
        }

        $scopeLabel = match ($scope) {
            'month' => 'Bulanan',
            'week'  => 'Mingguan',
            'day'   => 'Harian',
            default => 'Mingguan',
        };
        $mainTitle = 'Rekap Routine Monitoring ' . $scopeLabel . ' - ' . $titleSuffix;

        // Fetch Items and checking records using exact same logic as Web UI modal Rekap Harian
        $routineData = $this->buildRoutineMonitoringData($filters);
        $groupedItems = $routineData['grouped_items'] ?? [];
        $categories = $routineData['categories'] ?? [];

        $categoryOrder = [];
        foreach ($categories as $cat) {
            $catName = strtoupper((string) ($cat['category_name'] ?? ''));
            if ($catName !== '' && $catName !== 'UMUM' && !in_array($catName, $categoryOrder, true)) {
                $categoryOrder[] = $catName;
            }
        }
        if (empty($categoryOrder)) {
            $categoryOrder = ['GATE', 'CCTV', 'SERVER'];
        }

        // ─── Pure-PHP PDF builder (landscape A4) ─────────────────────────────
        $pageW = 842; $pageH = 595; $margin = 18.0;
        $blue  = '0.16 0.37 0.65';
        $textColor = '0.15 0.18 0.22';
        $hdrFill = $blue;
        $altFill = '0.97 0.98 1.00';
        $border  = '0.78 0.84 0.90';
        $green   = '0.00 0.69 0.31'; // #00B050
        $red     = '1.00 0.00 0.00'; // #FF0000
        $objects = [
            1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $streams = []; $current = []; $y = $pageH - $margin;

        $escape = function (string $t): string {
            $t = trim(preg_replace('/\s+/', ' ', $t) ?? $t);
            if (function_exists('iconv')) {
                $c = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $t);
                if ($c !== false && $c !== '') { $t = $c; }
            }
            $t = preg_replace('/[^ -~\x80-\xFF]/', '', $t) ?? $t;
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t === '' ? '-' : $t);
        };
        $addText = function (float $x, float $yP, string $txt, int $font = 1, float $sz = 7, string $col = '0 0 0') use (&$current, $escape) {
            $current[] = 'BT';
            $current[] = sprintf('/F%d %.1f Tf', $font, $sz);
            $current[] = $col . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yP);
            $current[] = '(' . $escape($txt) . ') Tj';
            $current[] = 'ET';
        };
        $addRect = function (float $x, float $yP, float $w, float $h, ?string $fill, string $stroke = '0 0 0', float $lw = 0.4) use (&$current) {
            $current[] = sprintf('%.2f w', $lw);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            $current[] = $stroke . ' RG';
            $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yP, $w, $h, $fill !== null ? 'B' : 'S');
        };
        $finishPage = function () use (&$streams, &$current) {
            if ($current) { $streams[] = implode("\n", $current); }
            $current = [];
        };

        $dateCount = count($rangeDates);
        $usableW = $pageW - 2 * $margin; // 806.0
        
        if ($scope === 'day') {
            $locColW = 100.0;
            $nameColW = 200.0;
            $ketColW = 220.0;
            $timeColW = 136.0;
            $dateColW = $usableW - ($locColW + $nameColW + $ketColW + $timeColW);
        } elseif ($scope === 'week') {
            $locColW = 90.0;
            $nameColW = 160.0;
            $ketColW = 200.0;
            $timeColW = 110.0;
            $dateColW = ($usableW - ($locColW + $nameColW + $ketColW + $timeColW)) / $dateCount;
        } else { // month
            $locColW = 80.0;
            $nameColW = 95.0;
            $ketColW = 90.0;
            $timeColW = 70.0;
            $dateColW = ($usableW - ($locColW + $nameColW + $ketColW + $timeColW)) / $dateCount;
        }

        $hdrH      = 18.0;
        $rowH      = 14.0;
        $titleH    = 40.0;

        $startPage = function () use (&$current, &$y, $pageH, $margin, $mainTitle, $addText, $blue, $textColor, $titleH) {
            $current = [];
            $y = $pageH - $margin;
            $addText($margin, $y - 4, $mainTitle, 2, 14, $blue);
            $addText($margin, $y - 20, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 8, $textColor);
            $y -= $titleH;
        };

        $drawTableHeaderForCategory = function (string $catLabel) use (
            &$y, $margin, $rangeDates, $addRect, $addText, $hdrFill, $hdrH,
            $locColW, $nameColW, $ketColW, $dateColW, $timeColW
        ) {
            $x = $margin;
            $rowY = $y - $hdrH;
            $isCg = in_array(strtoupper($catLabel), ['CCTV', 'GATE'], true);
            
            if ($isCg) {
                // Lokasi header
                $addRect($x, $rowY, $locColW, $hdrH, $hdrFill, $hdrFill, 0.7);
                $title = 'Lokasi';
                $offset = ($locColW - (strlen($title) * 6 * 0.6)) / 2;
                $addText($x + max(0.5, $offset), $rowY + 6, $title, 2, 6, '1 1 1');
                $x += $locColW;
                
                // Nama Checking header
                $addRect($x, $rowY, $nameColW, $hdrH, $hdrFill, $hdrFill, 0.7);
                $colTitle = (strtoupper($catLabel) === 'CCTV') ? 'Nama CCTV' : 'Nama Barang';
                $offset = ($nameColW - (strlen($colTitle) * 6 * 0.6)) / 2;
                $addText($x + max(0.5, $offset), $rowY + 6, $colTitle, 2, 6, '1 1 1');
                $x += $nameColW;
            } else {
                // Nama Checking header
                $totalW = $locColW + $nameColW;
                $addRect($x, $rowY, $totalW, $hdrH, $hdrFill, $hdrFill, 0.7);
                $colTitle = (strtoupper($catLabel) === 'SERVER') ? 'Nama Server' : 'Nama Checking';
                $offset = ($totalW - (strlen($colTitle) * 6 * 0.6)) / 2;
                $addText($x + max(0.5, $offset), $rowY + 6, $colTitle, 2, 6, '1 1 1');
                $x += $totalW;
            }

            // Keterangan header
            $addRect($x, $rowY, $ketColW, $hdrH, $hdrFill, $hdrFill, 0.7);
            $title = 'Keterangan';
            $offset = ($ketColW - (strlen($title) * 6 * 0.6)) / 2;
            $addText($x + max(0.5, $offset), $rowY + 6, $title, 2, 6, '1 1 1');
            $x += $ketColW;
            
            // Date column headers
            foreach ($rangeDates as $dk) {
                $addRect($x, $rowY, $dateColW, $hdrH, $hdrFill, $hdrFill, 0.7);
                
                $dayStr = date('d', strtotime($dk));
                $dayStrW = strlen($dayStr) * 5.5 * 0.6;
                $dayStrOffset = ($dateColW - $dayStrW) / 2;
                $addText($x + max(0.5, $dayStrOffset), $rowY + 10, $dayStr, 2, 5.5, '1 1 1');
                
                $dayAbbr = date('D', strtotime($dk));
                $dayMap = ['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min'];
                $dayName = $dayMap[$dayAbbr] ?? $dayAbbr;
                $dayNameW = strlen($dayName) * 4.5 * 0.55;
                $dayNameOffset = ($dateColW - $dayNameW) / 2;
                $addText($x + max(0.5, $dayNameOffset), $rowY + 3.5,  $dayName, 1, 4.5, '0.85 0.90 1.00');
                
                $x += $dateColW;
            }

            // Waktu Update header
            $addRect($x, $rowY, $timeColW, $hdrH, $hdrFill, $hdrFill, 0.7);
            $title = 'Waktu Update';
            $offset = ($timeColW - (strlen($title) * 6 * 0.6)) / 2;
            $addText($x + max(0.5, $offset), $rowY + 6, $title, 2, 6, '1 1 1');
            $x += $timeColW;

            $y = $rowY - 1;
        };

        // Render one category block
        $renderCategory = function (
            string $catLabel,
            array  $items,
            array  $rangeDates
        ) use (
            &$y, $margin, $hdrH, $rowH, $pageH, $titleH, $scope,
            $addRect, $addText, $blue, $textColor, $altFill, $border, $green, $red,
            $startPage, $drawTableHeaderForCategory, $finishPage, $locColW, $nameColW, $ketColW, $dateColW, $timeColW
        ): void {
            $isCg = in_array(strtoupper($catLabel), ['CCTV', 'GATE'], true);
            $usableW = $locColW + $nameColW + $ketColW + $dateColW * count($rangeDates) + $timeColW;

            // Draw category section heading
            $secH = 14.0;
            if (($y - ($secH + $hdrH + $rowH)) < $margin + 4) {
                $finishPage();
                $startPage();
            }
            $addRect($margin, $y - $secH, $usableW, $secH, '0.93 0.96 1.00', $border, 0.4);
            $addText($margin + 3, $y - $secH + 5, strtoupper($catLabel), 2, 7, $blue);
            $y -= ($secH + 1);
            
            // Helper to draw vertically merged Lokasi cells
            $drawMergedLocationCell = function (
                float $x,
                float $bottomY,
                float $w,
                float $h,
                string $locText
            ) use ($addRect, $addText, $textColor, $border) {
                // Background fill white, border color $border, line width 0.45
                $addRect($x, $bottomY, $w, $h, '1 1 1', $border, 0.45);
                
                // Adjust font size dynamically to fit cell width
                $fontSize = 6.0;
                $textLen = strlen($locText);
                $textWidth = $textLen * $fontSize * 0.55;
                if ($textWidth > $w - 4) {
                    $fontSize = ($w - 4) / ($textLen * 0.55);
                    if ($fontSize < 4.0) {
                        $fontSize = 4.0;
                    }
                    $textWidth = $textLen * $fontSize * 0.55;
                }
                
                // Center alignment
                $textOffset = ($w - $textWidth) / 2;
                $textY = $bottomY + ($h / 2) - ($fontSize / 2) + 0.5;
                $addText($x + max(2.0, $textOffset), $textY, $locText, 1, $fontSize, $textColor);
            };

            // Draw table header
            $drawTableHeaderForCategory($catLabel);

            $currentLocation = null;
            $groupStartY = $y;
            $lastRowY = $y;

            foreach ($items as $idx => $item) {
                list($locVal, $nameVal) = $this->getRoutineItemLocationAndName($item, $catLabel);

                if ($isCg) {
                    if ($currentLocation === null) {
                        $currentLocation = $locVal;
                        $groupStartY = $y;
                    } elseif ($locVal !== $currentLocation) {
                        if ($groupStartY > $lastRowY) {
                            $drawMergedLocationCell($margin, $lastRowY, $locColW, $groupStartY - $lastRowY, $currentLocation);
                        }
                        $currentLocation = $locVal;
                        $groupStartY = $y;
                    }

                    if (($y - $rowH) < $margin + 4) {
                        if ($groupStartY > $lastRowY) {
                            $drawMergedLocationCell($margin, $lastRowY, $locColW, $groupStartY - $lastRowY, $currentLocation);
                        }
                        $finishPage();
                        $startPage();
                        $drawTableHeaderForCategory($catLabel);
                        $groupStartY = $y;
                    }
                } else {
                    if (($y - $rowH) < $margin + 4) {
                        $finishPage();
                        $startPage();
                        $drawTableHeaderForCategory($catLabel);
                    }
                }

                $rowY = $y - $rowH;
                $bgFill = $idx % 2 === 0 ? $altFill : '1 1 1';
                
                $x = $margin;
                if ($isCg) {
                    // Skip Lokasi cell drawing here - it is deferred!
                    $x += $locColW;
                    
                    // Draw Nama Checking cell
                    $addRect($x, $rowY, $nameColW, $rowH, $bgFill, $border, 0.45);
                    $fontSizeName = 6;
                    if (strlen($nameVal) > 22) { $fontSizeName = 5; }
                    if (strlen($nameVal) > 30) { $fontSizeName = 4.5; }
                    $addText($x + 3, $rowY + 5, $nameVal, 1, $fontSizeName, $textColor);
                    $x += $nameColW;
                } else {
                    // Draw Nama Checking cell (full width locColW + nameColW)
                    $addRect($x, $rowY, $locColW + $nameColW, $rowH, $bgFill, $border, 0.45);
                    $fontSizeName = 6;
                    if (strlen($nameVal) > 30) { $fontSizeName = 5; }
                    if (strlen($nameVal) > 40) { $fontSizeName = 4.5; }
                    $addText($x + 3, $rowY + 5, $nameVal, 1, $fontSizeName, $textColor);
                    $x += ($locColW + $nameColW);
                }

                // Collect notes/keterangan during period as a numbered list
                $noteEntries = [];
                $hasAnyUpdate = false;
                foreach ($rangeDates as $dk) {
                    $note = trim((string) ($item['calendar'][$dk]['keterangan'] ?? ''));
                    $statusVal = (string) ($item['calendar'][$dk]['condition_status'] ?? '');
                    
                    $norm = '';
                    if ($statusVal === 'ON' || $statusVal === 'BAIK' || $statusVal === 'AKTIF') {
                        $norm = 'ON';
                    } elseif ($statusVal === 'OFF' || $statusVal === 'RUSAK' || $statusVal === 'TIDAK AKTIF' || $statusVal === 'BURUK') {
                        $norm = 'OFF';
                    }

                    if ($note !== '' || $norm === 'ON' || $norm === 'OFF') {
                        $hasAnyUpdate = true;
                    }

                    $dateLabel = date('d/m', strtotime($dk));
                    if ($note !== '') {
                        $noteEntries[] = "[{$dateLabel}] {$note}";
                    } elseif ($norm === 'OFF') {
                        $noteEntries[] = "[{$dateLabel}] Butuh Maintenance";
                    }
                }
                
                if (!$hasAnyUpdate) {
                    $keteranganText = '-';
                } elseif (empty($noteEntries)) {
                    $keteranganText = 'Aman';
                } else {
                    $keteranganText = '';
                    foreach ($noteEntries as $idx => $entry) {
                        $keteranganText .= ($idx + 1) . ". " . $entry . "\n";
                    }
                    $keteranganText = rtrim($keteranganText);
                }

                // Draw Keterangan cell with word-wrap and vertical centering
                $addRect($x, $rowY, $ketColW, $rowH, $bgFill, $border, 0.45);
                $fontSizeKet = 5.5;
                if ($scope === 'month') {
                    $fontSizeKet = 4.5;
                }
                
                $charWidth = $fontSizeKet * 0.55; 
                $maxChars = max(10, (int)($ketColW / $charWidth));
                $wrappedLines = explode("\n", wordwrap($keteranganText, $maxChars, "\n", true));
                
                if (count($wrappedLines) > 2) {
                    $fontSizeKet = 4.5;
                    $charWidth = $fontSizeKet * 0.55;
                    $maxChars = max(10, (int)($ketColW / $charWidth));
                    $wrappedLines = explode("\n", wordwrap($keteranganText, $maxChars, "\n", true));
                    if (count($wrappedLines) > 3) {
                        $wrappedLines = array_slice($wrappedLines, 0, 2);
                        $wrappedLines[] = '...';
                    }
                }
                
                $numLines = count($wrappedLines);
                $lineHeight = $fontSizeKet + 1.0;
                $startY = $rowY + ($rowH / 2) + (($numLines - 1) * $lineHeight / 2) - ($fontSizeKet / 2) + 0.3;
                
                foreach ($wrappedLines as $lineIdx => $lineText) {
                    $lineY = $startY - ($lineIdx * $lineHeight);
                    $addText($x + 3, $lineY, $lineText, 1, $fontSizeKet, $textColor);
                }
                $x += $ketColW;
                
                // Draw Date cells
                foreach ($rangeDates as $dk) {
                    $status = (string) ($item['calendar'][$dk]['condition_status'] ?? '');
                    
                    $normalizedStatus = '';
                    if (in_array(strtoupper($catLabel), ['CCTV', 'SERVER'], true)) {
                        if ($status === 'ON' || $status === 'BAIK' || $status === 'AKTIF') {
                            $normalizedStatus = 'ON';
                        } elseif ($status === 'OFF' || $status === 'RUSAK' || $status === 'TIDAK AKTIF' || $status === 'BURUK') {
                            $normalizedStatus = 'OFF';
                        }
                    } else { // GATE
                        if ($status === 'BAIK' || $status === 'AKTIF' || $status === 'ON') {
                            $normalizedStatus = 'AKTIF';
                        } elseif ($status === 'BURUK' || $status === 'RUSAK' || $status === 'OFF') {
                            $normalizedStatus = 'RUSAK';
                        }
                    }

                    if ($normalizedStatus === '') {
                        $cellFill = $bgFill;
                        $normSt = '-';
                        $cellTxtColor = '0.50 0.55 0.60';
                    } else {
                        $normSt = $normalizedStatus;
                        $cellFill = ($normSt === 'ON' || $normSt === 'AKTIF') ? $green : $red;
                        $cellTxtColor = '1 1 1';
                    }

                    $addRect($x, $rowY, $dateColW, $rowH, $cellFill, $border, 0.45);
                    
                    $fontSizeSt = 4.8;
                    if ($scope === 'month') {
                        $fontSizeSt = 3.6;
                    }
                    
                    $textLen = strlen($normSt);
                    $textWidth = $textLen * $fontSizeSt * 0.70;
                    if ($textWidth > $dateColW - 1.0) {
                        $fontSizeSt = ($dateColW - 1.0) / ($textLen * 0.70);
                        if ($fontSizeSt < 3.0) { $fontSizeSt = 3.0; }
                        $textWidth = $textLen * $fontSizeSt * 0.70;
                    }
                    
                    $textOffset = ($dateColW - $textWidth) / 2;
                    $addText($x + max(0.5, $textOffset), $rowY + 5, $normSt, $normSt === '-' ? 1 : 2, $fontSizeSt, $cellTxtColor);
                    
                    $x += $dateColW;
                }

                // Draw Waktu Update cell
                $updatedAtText = '-';
                $latestTime = 0;
                foreach ($rangeDates as $dk) {
                    $ua = trim((string) ($item['calendar'][$dk]['updated_at'] ?? ''));
                    if ($ua !== '') {
                        $ts = strtotime($ua);
                        if ($ts > $latestTime) {
                            $latestTime = $ts;
                        }
                    }
                }
                if ($latestTime > 0) {
                    $updatedAtText = date('d/m/Y H:i', $latestTime);
                }
                
                $addRect($x, $rowY, $timeColW, $rowH, $bgFill, $border, 0.45);
                $fontSizeTime = 6;
                if ($scope === 'month') { $fontSizeTime = 5; }
                
                $textLen = strlen($updatedAtText);
                $textWidth = $textLen * $fontSizeTime * 0.55;
                $timeTextOffset = ($timeColW - $textWidth) / 2;
                $addText($x + max(0.5, $timeTextOffset), $rowY + 5, $updatedAtText, 1, $fontSizeTime, $textColor);
                $x += $timeColW;

                $y = $rowY - 1;
                $lastRowY = $rowY;
            }
            if ($isCg && $currentLocation !== null && $groupStartY > $lastRowY) {
                $drawMergedLocationCell($margin, $lastRowY, $locColW, $groupStartY - $lastRowY, $currentLocation);
            }
            $y -= 4; // gap between categories
        };

        $startPage();

        $rendered = false;
        foreach ($categoryOrder as $catName) {
            $items = $groupedItems[$catName] ?? [];
            if (empty($items)) { continue; }
            
            // Sort items alphabetically (grouped by location if GATE or CCTV)
            usort($items, function ($a, $b) use ($catName) {
                list($locA, $nameA) = $this->getRoutineItemLocationAndName($a, $catName);
                list($locB, $nameB) = $this->getRoutineItemLocationAndName($b, $catName);
                
                if ($locA !== $locB) {
                    return strcasecmp($locA, $locB);
                }
                return strcasecmp($nameA, $nameB);
            });

            $rendered = true;
            $renderCategory($catName, $items, $rangeDates);
        }
        
        // Also handle any extra categories not in the default order
        foreach ($groupedItems as $catName => $items) {
            if (in_array($catName, $categoryOrder, true) || empty($items)) { continue; }
            
            // Sort items alphabetically
            usort($items, function ($a, $b) use ($catName) {
                list($locA, $nameA) = $this->getRoutineItemLocationAndName($a, $catName);
                list($locB, $nameB) = $this->getRoutineItemLocationAndName($b, $catName);
                
                if ($locA !== $locB) {
                    return strcasecmp($locA, $locB);
                }
                return strcasecmp($nameA, $nameB);
            });

            $rendered = true;
            $renderCategory($catName, $items, $rangeDates);
        }

        if (!$rendered) {
            $addText($margin, $y - 18, 'Belum ada data monitoring pada periode ini.', 1, 9, $textColor);
        }
        $finishPage();

        // ─── Assemble PDF ────────────────────────────────────────────────────
        $nextId = 3; $contentIds = []; $pageIds = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++; $pageId = $nextId++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[$pageId] = '';
            $contentIds[] = $contentId;
            $pageIds[]    = $pageId;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn ($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [ ' . $kids . ' ] >>';
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $size = $catalogId + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";

        $base = 'rekap-pivot-routine-monitoring-' . $scope . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $baseSuffix);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
    private function streamRoutineMonitoringPivotExcel(array $filters): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return;
        }

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            if (file_exists(__DIR__ . '/../../peminjaman_laptop/vendor/autoload.php')) {
                require_once __DIR__ . '/../../peminjaman_laptop/vendor/autoload.php';
            } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
        }

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            http_response_code(500);
            echo 'Library PhpSpreadsheet tidak tersedia.';
            exit;
        }

        $scope = strtolower(trim((string) ($_GET['recap_scope'] ?? 'week')));
        if (!in_array($scope, ['day', 'week', 'month'], true)) {
            $scope = 'week';
        }

        $yearNum = (int) ($filters['routine_year'] ?? $_GET['routine_year'] ?? date('Y'));
        $monthNum = (int) ($filters['routine_month'] ?? $_GET['routine_month'] ?? date('m'));
        if ($monthNum < 1 || $monthNum > 12) { $monthNum = (int)date('m'); }
        if ($yearNum < 2020 || $yearNum > 2100) { $yearNum = (int)date('Y'); }

        $daysInMonth = (int)date('t', mktime(0, 0, 0, $monthNum, 1, $yearNum));

        // Setup Date Range
        if ($scope === 'day') {
            $requestedDate = trim((string) ($_GET['recap_date'] ?? ''));
            if ($requestedDate === '') {
                $requestedDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            }
            $rangeStart = $requestedDate;
            $rangeEnd   = $requestedDate;
            $titleSuffix = date('d/m/Y', strtotime($requestedDate));
            $baseSuffix  = preg_replace('/[^A-Za-z0-9_-]+/', '-', $requestedDate);
        } elseif ($scope === 'week') {
            $weekStartInput = trim((string) ($_GET['week_start'] ?? $_GET['recap_date'] ?? ''));
            if ($weekStartInput === '') {
                $weekStartInput = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            }
            try {
                $weekStartDate = new DateTimeImmutable($weekStartInput);
            } catch (Throwable $e) {
                $weekStartDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $yearNum, $monthNum));
            }
            $weekStartDate = $weekStartDate->modify('monday this week');
            $weekEndDate   = $weekStartDate->modify('+6 days');
            
            $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $yearNum, $monthNum));
            $monthEnd   = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth));
            if ($weekStartDate < $monthStart) { $weekStartDate = $monthStart; }
            if ($weekEndDate   > $monthEnd)   { $weekEndDate   = $monthEnd;   }
            
            $rangeStart  = $weekStartDate->format('Y-m-d');
            $rangeEnd    = $weekEndDate->format('Y-m-d');
            $titleSuffix = date('d/m/Y', strtotime($rangeStart)) . ' - ' . date('d/m/Y', strtotime($rangeEnd));
            $baseSuffix  = $rangeStart . '-sd-' . $rangeEnd;
        } else { // month
            $rangeStart = sprintf('%04d-%02d-01', $yearNum, $monthNum);
            $rangeEnd   = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth);
            $monthNamesIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $monthName = $monthNamesIndo[$monthNum] ?? date('F', mktime(0, 0, 0, $monthNum, 1, $yearNum));
            $titleSuffix = $monthName . ' ' . $yearNum;
            $baseSuffix  = sprintf('%04d-%02d', $yearNum, $monthNum);
        }

        // Collect dates in range
        $rangeDates = [];
        $cur = new DateTimeImmutable($rangeStart);
        $end = new DateTimeImmutable($rangeEnd);
        while ($cur <= $end) {
            $rangeDates[] = $cur->format('Y-m-d');
            $cur = $cur->modify('+1 day');
        }
        $dateCount = count($rangeDates);

        $scopeLabel = match ($scope) {
            'month' => 'Bulanan',
            'week'  => 'Mingguan',
            'day'   => 'Harian',
            default => 'Mingguan',
        };
        $mainTitle = 'Rekap Routine Monitoring ' . $scopeLabel . ' - ' . $titleSuffix;

        $routineData = $this->buildRoutineMonitoringData($filters);
        $groupedItems = $routineData['grouped_items'] ?? [];
        $categories = $routineData['categories'] ?? [];

        $categoryOrder = [];
        foreach ($categories as $cat) {
            $catName = strtoupper((string) ($cat['category_name'] ?? ''));
            if ($catName !== '' && $catName !== 'UMUM' && !in_array($catName, $categoryOrder)) {
                $categoryOrder[] = $catName;
            }
        }
        if (empty($categoryOrder)) {
            $categoryOrder = ['GATE', 'CCTV', 'SERVER'];
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $headerYellowStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFD965']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000']
                ]
            ]
        ];

        if ($scope === 'day') {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Harian');

            $sheet->setCellValue('A1', $mainTitle);
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->mergeCells('A1:H1');

            $sheet->setCellValue('A2', 'Diexport');
            $sheet->setCellValue('B2', date('d-m-Y H:i:s'));

            $headers = ['No', 'Tanggal', 'Kategori', 'Checking', 'Kondisi', 'Keterangan', 'Checked By', 'Update'];
            $colIndex = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($colIndex . '4', $header);
                $sheet->getStyle($colIndex . '4')->getFont()->setBold(true);
                $colIndex++;
            }

            // Fetch records for the day
            $dayDate = $rangeStart;
            $rows = [];
            foreach ($categoryOrder as $catName) {
                $items = $groupedItems[$catName] ?? [];
                foreach ($items as $item) {
                    list($locVal, $nameVal) = $this->getRoutineItemLocationAndName($item, $catName);
                    $cell = $item['calendar'][$dayDate] ?? [];
                    if (empty($cell['condition_status'])) {
                        continue;
                    }
                    $rows[] = [
                        'tanggal' => date('d/m/Y', strtotime($dayDate)),
                        'kategori' => $catName,
                        'checking' => ($locVal !== '' && $catName !== 'SERVER') ? ($locVal . ' - ' . $nameVal) : $nameVal,
                        'kondisi' => $cell['condition_status'],
                        'keterangan' => $cell['keterangan'] ?? '-',
                        'checked_by' => $cell['checked_by_name'] ?? '-',
                        'update' => $cell['updated_at'] ?? '-',
                    ];
                }
            }

            $currentRow = 5;
            $no = 1;
            foreach ($rows as $r) {
                $sheet->setCellValue('A' . $currentRow, $no++);
                $sheet->setCellValue('B' . $currentRow, $r['tanggal']);
                $sheet->setCellValue('C' . $currentRow, $r['kategori']);
                $sheet->setCellValue('D' . $currentRow, $r['checking']);
                $sheet->setCellValue('E' . $currentRow, $r['kondisi']);
                $sheet->setCellValue('F' . $currentRow, $r['keterangan']);
                $sheet->setCellValue('G' . $currentRow, $r['checked_by']);
                $sheet->setCellValue('H' . $currentRow, $r['update']);

                $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $currentRow++;
            }

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $sheet->getStyle('A4:H' . ($currentRow - 1))->applyFromArray($borderStyle);

        } else {
            // Month / Week - Matrix
            $sheetIndex = 0;
            foreach ($categoryOrder as $catName) {
                $items = $groupedItems[$catName] ?? [];
                if (empty($items)) {
                    continue;
                }

                if ($sheetIndex > 0) {
                    $sheet = $spreadsheet->createSheet();
                } else {
                    $sheet = $spreadsheet->getActiveSheet();
                }
                $sheet->setTitle(substr($catName, 0, 31));

                $isCg = in_array($catName, ['CCTV', 'GATE'], true);

                if ($isCg) {
                    $sheet->mergeCells('A1:A2');
                    $sheet->setCellValue('A1', 'LOKASI');
                    
                    $sheet->mergeCells('B1:B2');
                    $colBHeader = ($catName === 'CCTV') ? 'NAMA CCTV' : 'NAMA BARANG';
                    $sheet->setCellValue('B1', $colBHeader);
                    
                    $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateCount + 2);
                    $sheet->mergeCells('C1:' . $lastColLetter . '1');
                    $sheet->setCellValue('C1', 'TANGGAL');
                    
                    $keteranganColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateCount + 3);
                    $sheet->mergeCells($keteranganColLetter . '1:' . $keteranganColLetter . '2');
                    $sheet->setCellValue($keteranganColLetter . '1', 'KETERANGAN');
                    
                    for ($d = 0; $d < $dateCount; $d++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($d + 3);
                        $dateStr = date('d/m/Y', strtotime($rangeDates[$d]));
                        $sheet->setCellValue($colLetter . '2', $dateStr);
                    }
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle('B1:B2')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle('C1:' . $lastColLetter . '1')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle('C2:' . $lastColLetter . '2')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle($keteranganColLetter . '1:' . $keteranganColLetter . '2')->applyFromArray($headerYellowStyle);
                } else { // SERVER / OTHER
                    $sheet->mergeCells('A1:A2');
                    $sheet->setCellValue('A1', $catName === 'SERVER' ? 'NAMA SERVER' : 'NAMA CHECKING');
                    
                    $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateCount + 1);
                    $sheet->mergeCells('B1:' . $lastColLetter . '1');
                    $sheet->setCellValue('B1', 'TANGGAL');
                    
                    $keteranganColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateCount + 2);
                    $sheet->mergeCells($keteranganColLetter . '1:' . $keteranganColLetter . '2');
                    $sheet->setCellValue($keteranganColLetter . '1', 'KETERANGAN');
                    
                    for ($d = 0; $d < $dateCount; $d++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($d + 2);
                        $dateStr = date('d/m/Y', strtotime($rangeDates[$d]));
                        $sheet->setCellValue($colLetter . '2', $dateStr);
                    }
                    
                    $sheet->getStyle('A1:A2')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle('B1:' . $lastColLetter . '1')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle('B2:' . $lastColLetter . '2')->applyFromArray($headerYellowStyle);
                    $sheet->getStyle($keteranganColLetter . '1:' . $keteranganColLetter . '2')->applyFromArray($headerYellowStyle);
                }

                $currentRow = 3;
                $rowHeights = [];
                $rowHeights[1] = 14.5;
                $rowHeights[2] = 14.5;

                foreach ($items as $item) {
                    list($itemLocation, $itemName) = $this->getRoutineItemLocationAndName($item, $catName);

                    if ($isCg) {
                        $sheet->setCellValue('A' . $currentRow, $itemLocation);
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                        
                        $sheet->setCellValue('B' . $currentRow, $itemName);
                        $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('B' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    } else {
                        $sheet->setCellValue('A' . $currentRow, $itemName);
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }

                    $offCount = 0;
                    $rowComments = [];

                    for ($d = 0; $d < $dateCount; $d++) {
                        $dateKey = $rangeDates[$d];
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($isCg ? $d + 3 : $d + 2);
                        
                        $cell = $item['calendar'][$dateKey] ?? [];
                        $status = strtoupper(trim((string) ($cell['condition_status'] ?? '')));
                        $keterangan = trim((string) ($cell['keterangan'] ?? ''));

                        $normalizedStatus = '';
                        if (in_array($catName, ['CCTV', 'SERVER'], true)) {
                            if ($status === 'ON' || $status === 'BAIK' || $status === 'AKTIF') {
                                $normalizedStatus = 'ON';
                            } elseif ($status === 'OFF' || $status === 'RUSAK' || $status === 'TIDAK AKTIF' || $status === 'BURUK') {
                                $normalizedStatus = 'OFF';
                                $offCount++;
                            }
                        } else { // GATE / OTHER
                            if ($status === 'BAIK' || $status === 'AKTIF' || $status === 'ON') {
                                $normalizedStatus = 'Aktif';
                            } elseif ($status === 'BURUK' || $status === 'RUSAK' || $status === 'OFF') {
                                $normalizedStatus = 'Rusak';
                                $offCount++;
                            }
                        }

                        if ($keterangan !== '') {
                            $rowComments[] = "Tgl " . date('d', strtotime($dateKey)) . ": " . $keterangan;
                        }

                        if ($normalizedStatus !== '') {
                            $sheet->setCellValue($colLetter . $currentRow, $normalizedStatus);
                            
                            if ($keterangan !== '') {
                                $sheet->getComment($colLetter . $currentRow)->getText()->createTextRun($keterangan);
                            }
                            
                            $isOk = ($normalizedStatus === 'ON' || $normalizedStatus === 'Aktif');
                            $bgColor = $isOk ? 'FF00B050' : 'FFFF0000';
                            
                            $sheet->getStyle($colLetter . $currentRow)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $bgColor]
                                ],
                                'font' => [
                                    'color' => ['argb' => 'FFFFFFFF'],
                                    'bold' => true
                                ],
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                                ]
                            ]);
                        }
                    }

                    $keteranganText = '';
                    if (in_array($catName, ['CCTV', 'SERVER'], true)) {
                        $keteranganText = "Mati: " . $offCount . " kali";
                        $rowHeights[$currentRow] = 14.5;
                    } else { // GATE / OTHER
                        $keteranganText = implode("\n", $rowComments);
                        if (!empty($rowComments)) {
                            $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setWrapText(true);
                            $rowHeights[$currentRow] = -1; // auto-adjust
                        } else {
                            $rowHeights[$currentRow] = 14.5;
                        }
                    }

                    $sheet->setCellValue($keteranganColLetter . $currentRow, $keteranganText);
                    $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setHorizontal(in_array($catName, ['CCTV', 'SERVER'], true) ? \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER : \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    
                    $currentRow++;
                }

                $lastRowIndex = $currentRow - 1;

                if ($isCg) {
                    // Merge consecutive locations in Column A
                    $mergeStart = 3;
                    for ($r = 3; $r <= $lastRowIndex; $r++) {
                        $currentLoc = $sheet->getCell('A' . $r)->getValue();
                        $nextLoc = ($r < $lastRowIndex) ? $sheet->getCell('A' . ($r + 1))->getValue() : null;
                        
                        if ($currentLoc !== $nextLoc) {
                            if ($r > $mergeStart) {
                                $sheet->mergeCells('A' . $mergeStart . ':A' . $r);
                            }
                            $mergeStart = $r + 1;
                        }
                    }
                }

                $sheet->getColumnDimension('A')->setAutoSize(true);
                if ($isCg) {
                    $sheet->getColumnDimension('B')->setAutoSize(true);
                }
                for ($d = 0; $d < $dateCount; $d++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($isCg ? $d + 3 : $d + 2);
                    $sheet->getColumnDimension($colLetter)->setWidth(13.0);
                }
                $sheet->getColumnDimension($keteranganColLetter)->setWidth(30.0);

                for ($r = 1; $r <= $lastRowIndex; $r++) {
                    $h = isset($rowHeights[$r]) ? $rowHeights[$r] : 14.5;
                    if ($h > 0) {
                        $sheet->getRowDimension($r)->setRowHeight($h);
                    }
                }

                $sheet->getStyle('A1:' . $keteranganColLetter . $lastRowIndex)->applyFromArray($borderStyle);
                $sheetIndex++;
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $base = 'rekap-pivot-routine-monitoring-' . $scope . '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $baseSuffix);
        
        while (ob_get_level() > 0) { ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
        ob_start();
        $writer->save('php://output');
        $xlsx = ob_get_clean();
        header('Content-Length: ' . strlen($xlsx));
        echo $xlsx;
        exit;
    }
    private function handleUserManagementAction(): void
    {
        // ---- EXPORT (GET) ----
        if (($_GET['action'] ?? '') === 'export' && AuthController::isAdminSpmt()) {
            $pdo = Database::getConnection();
            if ($pdo instanceof PDO) {
                $filters = [
                    'search' => trim((string) ($_GET['search'] ?? '')),
                    'status' => trim((string) ($_GET['status'] ?? '')),
                    'division' => trim((string) ($_GET['division'] ?? '')),
                ];
                $this->streamUserManagementExport($pdo, (string) ($_GET['format'] ?? 'pdf'), $filters);
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }


        if (!AuthController::isAdminSpmt()) {
            http_response_code(403);
            echo 'Akses ditolak. Halaman ini khusus admin.spmt.';
            exit;
        }

        $action = trim((string) ($_POST['user_action'] ?? ''));
        $userId = max(0, (int) ($_POST['user_id'] ?? 0));
        $currentUserId = (int) ($_SESSION['auth']['id'] ?? 0);

        try {
            if ($userId <= 0) {
                throw new RuntimeException('User tidak valid.');
            }

            $authModel = new AuthModel();

            if ($action === 'approve') {
                $authModel->updateUserStatus($userId, true);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'User berhasil divalidasi.'];
            } elseif ($action === 'suspend') {
                if ($userId === $currentUserId) {
                    throw new RuntimeException('Akun admin.spmt yang sedang login tidak boleh dinonaktifkan.');
                }
                $authModel->updateUserStatus($userId, false);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'User berhasil dinonaktifkan.'];
            } elseif ($action === 'role') {
                if ($userId === $currentUserId) {
                    throw new RuntimeException('Role akun admin.spmt yang sedang login tidak boleh diubah dari halaman ini.');
                }
                $role = trim((string) ($_POST['role'] ?? 'user'));
                $authModel->updateUserRole($userId, $role);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Role user berhasil diperbarui.'];
            } elseif ($action === 'reset_password') {
                $password = (string) ($_POST['new_password'] ?? '');
                $authModel->resetUserPassword($userId, $password);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password user berhasil direset.'];
            } elseif ($action === 'delete') {
                if ($userId === $currentUserId) {
                    throw new RuntimeException('Akun admin.spmt yang sedang login tidak boleh dihapus.');
                }
                $authModel->deleteUser($userId);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'User berhasil dihapus.'];
            } else {
                throw new RuntimeException('Aksi tidak dikenali.');
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengelola user: ' . $e->getMessage()];
        }

        header('Location: ' . $this->buildUserManagementReturnUrl());
        exit;
    }

    private function buildUserManagementReturnUrl(): string
    {
        $query = ['page' => 'user-management'];
        foreach (['search', 'status'] as $key) {
            $value = trim((string) ($_GET[$key] ?? $_POST[$key] ?? ''));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }
        return 'index.php?' . http_build_query($query);
    }

    private function streamUserManagementExport(PDO $pdo, string $format, array $filters = []): void
    {
        $rows = $this->fetchUserReportRows($pdo, $filters);
        $title = 'Data User - ' . date('d-m-Y');
        $base  = 'data_user_' . date('Ymd');

        while (ob_get_level() > 0) { ob_end_clean(); }

        if (strtolower($format) === 'xlsx') {
            $headers = ['No', 'Nama Lengkap', 'Username', 'Email', 'Role', 'Divisi', 'Unit Kerja', 'Status', 'Tanggal Daftar', 'Login Terakhir'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['no'], $r['nama'], $r['username'], $r['email'], $r['role'],
                    $r['divisi'], $r['unit'], $r['status'], $r['created_at'], $r['last_login'],
                ];
            }
            $xlsx = $this->buildGenericLaporanXlsx($title, $headers, $excelRows, [0, 4, 7]);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
            header('Content-Length: ' . strlen($xlsx));
            echo $xlsx;
            exit;
        }

        // PDF — landscape, 11 columns
        $columns = [
            ['key' => 'no',         'title' => 'No',            'width' => 26,  'chars' => 4],
            ['key' => 'nama',       'title' => 'Nama Lengkap',  'width' => 108, 'chars' => 18],
            ['key' => 'username',   'title' => 'Username',      'width' => 72,  'chars' => 11],
            ['key' => 'email',      'title' => 'Email',         'width' => 130, 'chars' => 22],
            ['key' => 'role',       'title' => 'Role',          'width' => 46,  'chars' => 7],
            ['key' => 'divisi',     'title' => 'Divisi',        'width' => 120, 'chars' => 20],
            ['key' => 'status',     'title' => 'Status',        'width' => 58,  'chars' => 9],
            ['key' => 'created_at', 'title' => 'Tgl Daftar',   'width' => 90,  'chars' => 14],
            ['key' => 'last_login', 'title' => 'Login Terakhir','width' => 90,  'chars' => 14],
        ];
        $pdf = $this->buildGenericReportPdf($title, $columns, $rows);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function handleComplaintAction(): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
                header('Location: ' . $this->buildComplaintUrl());
                exit;
            }
            return;
        }

        $this->ensureComplaintEmailVerificationColumn($pdo);
        $this->ensureItSupportNotificationReadColumn($pdo);
        $this->ensureComplaintEmailNotificationColumns($pdo);
        $this->ensureSettingsTable($pdo);

        // AJAX Sync request
        $actionGet = trim((string) ($_GET['action'] ?? ''));
        if ($actionGet === 'ajax_sync_gform') {
            header('Content-Type: application/json; charset=utf-8');
            $this->syncGoogleFormSubmissions($pdo, true);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $markId = (int) ($_GET['mark_notification_read'] ?? $_GET['focus_ticket'] ?? 0);
            if ($markId > 0) {
                $this->markItSupportNotificationRead($pdo, $markId);
            }
            if ((string) ($_GET['mark_all_notifications'] ?? '') === '1') {
                $this->markAllItSupportNotificationsRead($pdo);
            }
        }

        $action = trim((string) ($_GET['action'] ?? ''));
        if ($action === 'export') {
            $this->streamComplaintExport($pdo, $_SESSION['spmt_context'] ?? [], (string) ($_GET['format'] ?? 'pdf'));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        
        if ($action === 'update_gform_settings') {
            $url = trim((string) ($_POST['google_sheet_csv_url'] ?? ''));
            $this->setSetting($pdo, 'google_sheet_csv_url', $url);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengaturan Google Form berhasil diperbarui.'];
            header('Location: ' . $this->buildComplaintUrl());
            exit;
        }

        if ($action === 'sync_google_form') {
            $this->syncGoogleFormSubmissions($pdo, false);
            header('Location: ' . $this->buildComplaintUrl());
            exit;
        }

        if ($action !== 'update_it_support_status') {
            return;
        }

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $status = strtoupper(trim((string) ($_POST['status'] ?? '')));
        $notes = trim((string) ($_POST['catatan_penanganan'] ?? ''));
        $postedHandlerUserId = (int) ($_POST['handled_by_user_id'] ?? 0);
        $allowed = ['NOT YET', 'ON PROGRESS', 'DONE'];

        if ($ticketId <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Tiket tidak valid.'];
            header('Location: ' . $this->buildComplaintUrl());
            exit;
        }

        if (!in_array($status, $allowed, true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Status tiket tidak valid.'];
            header('Location: ' . $this->buildComplaintUrl());
            exit;
        }

        try {
            $handlerUserId = $postedHandlerUserId > 0 ? $postedHandlerUserId : ((int) ($_SESSION['auth']['id'] ?? 0) ?: null);

            $checkStmt = $pdo->prepare('SELECT id, ticket_no, status, catatan_penanganan, handled_by_user_id, email_pelapor, nama_pelapor, divisi, aset_yang_perlu_diperbaiki, lokasi_perbaikan, deskripsi_kerusakan FROM it_support_request WHERE id = :id LIMIT 1');
            $checkStmt->execute(['id' => $ticketId]);
            $existing = $checkStmt->fetch();
            if (!$existing) {
                throw new RuntimeException('Tiket tidak ditemukan.');
            }

            $oldStatus = strtoupper(trim((string) ($existing['status'] ?? 'NOT YET')));
            if ($oldStatus === '' || $oldStatus === '0') { $oldStatus = 'NOT YET'; }
            if ($oldStatus === '1') { $oldStatus = 'ON PROGRESS'; }
            if ($oldStatus === '2') { $oldStatus = 'DONE'; }
            $oldNotes = trim((string) ($existing['catatan_penanganan'] ?? ''));
            $newNotes = $notes;
            $hasStatusChanged = $oldStatus !== $status;
            $hasNotesChanged = $oldNotes !== $newNotes;
            $oldHandlerUserId = (int) ($existing['handled_by_user_id'] ?? 0);
            $hasHandlerChanged = $oldHandlerUserId !== (int) $handlerUserId;

            if (!$hasStatusChanged && !$hasNotesChanged && !$hasHandlerChanged) {
                $emailMessage = '';
                $sendEmail = isset($_POST['send_email_notification']) && $_POST['send_email_notification'] === '1';
                if ($sendEmail && ($status !== 'NOT YET' || $newNotes !== '')) {
                    $emailResult = $this->sendItSupportHandlingEmail($pdo, array_merge($existing, [
                        'status' => $status,
                        'catatan_penanganan' => $newNotes,
                        'handled_by_user_id' => $handlerUserId,
                    ]));
                    $emailMessage = ' ' . $emailResult['message'];
                }
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Tidak ada perubahan pada tiket.' . $emailMessage];
                header('Location: ' . $this->buildComplaintUrl());
                exit;
            }

            $this->ensureComplaintHistoryTable($pdo);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE it_support_request SET status = :status, catatan_penanganan = :catatan_penanganan, handled_by_user_id = :handled_by_user_id WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'catatan_penanganan' => $newNotes !== '' ? $newNotes : null,
                'handled_by_user_id' => $handlerUserId,
                'id' => $ticketId,
            ]);

            $historyStmt = $pdo->prepare('INSERT INTO it_support_request_history (request_id, ticket_no, old_status, new_status, old_catatan_penanganan, new_catatan_penanganan, changed_by_user_id, changed_at) VALUES (:request_id, :ticket_no, :old_status, :new_status, :old_notes, :new_notes, :changed_by_user_id, NOW())');
            $historyStmt->execute([
                'request_id' => $ticketId,
                'ticket_no' => (string) ($existing['ticket_no'] ?? ''),
                'old_status' => $oldStatus,
                'new_status' => $status,
                'old_notes' => $oldNotes !== '' ? $oldNotes : null,
                'new_notes' => $newNotes !== '' ? $newNotes : null,
                'changed_by_user_id' => $handlerUserId,
            ]);

            $pdo->commit();

            $emailMessage = '';
            $sendEmail = isset($_POST['send_email_notification']) && $_POST['send_email_notification'] === '1';
            if ($sendEmail && ($status !== 'NOT YET' || $newNotes !== '')) {
                $emailResult = $this->sendItSupportHandlingEmail($pdo, array_merge($existing, [
                    'status' => $status,
                    'catatan_penanganan' => $newNotes,
                    'handled_by_user_id' => $handlerUserId,
                ]));
                $emailMessage = ' ' . $emailResult['message'];
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status tiket IT Support berhasil diperbarui dan riwayat perubahan tersimpan.' . $emailMessage];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal memperbarui tiket: ' . $e->getMessage()];
        }

        header('Location: ' . $this->buildComplaintUrl());
        exit;
    }

    private function jsonGetUnreadAlertCount(): void
    {
        header("Content-Type: application/json; charset=utf-8");
        if (!AuthController::check()) {
            http_response_code(401);
            echo json_encode(["count" => 0, "error" => "unauthenticated"]);
            return;
        }
        if (!AuthController::canAccessAlertSystem()) {
            http_response_code(403);
            echo json_encode(["count" => 0, "error" => "forbidden"]);
            return;
        }
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            echo json_encode(["count" => 0]);
            return;
        }
        
        // Auto-run alert scanner on AJAX polling (throttled to once every 5 seconds)
        try {
            $lastAlertCheck = (int) $this->getSetting($pdo, 'last_alert_check_time', '0');
            if (time() - $lastAlertCheck > 5) {
                $this->setSetting($pdo, 'last_alert_check_time', (string) time());
                $this->buildAlertNotifications($pdo);
            }
        } catch (Throwable $e) {}

        $count = 0;
        try {
            $count = (int) ($pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE is_read = 0")->fetchColumn() ?? 0);
        } catch (Throwable $e) {}
        echo json_encode(["count" => $count]);
        exit;
    }

    private function jsonItSupportNotifications(): void
    {
        header("Content-Type: application/json; charset=utf-8");
        if (!AuthController::check() || !AuthController::canAccessItSupport()) {
            http_response_code(403);
            echo json_encode(["count" => 0, "items" => [], "error" => "forbidden"]);
            return;
        }
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            echo json_encode(["count" => 0, "items" => []]);
            return;
        }

        // Trigger silent background sync on notifications check (throttled to once every 5 seconds)
        $importedCount = 0;
        try {
            $lastSync = (int) $this->getSetting($pdo, 'last_gform_sync_time', '0');
            if (time() - $lastSync > 5) {
                $this->setSetting($pdo, 'last_gform_sync_time', (string) time());
                $importedCount = $this->syncGoogleFormSubmissions($pdo, false, true);
            }
        } catch (Throwable $e) {
            // Silently ignore errors
        }
        $this->ensureItSupportNotificationReadColumn($pdo);
        try {
            $whereUnread = "notification_read_at IS NULL AND UPPER(TRIM(COALESCE(status, 'NOT YET'))) = 'NOT YET'";
            $countStmt = $pdo->query("SELECT COUNT(*) FROM it_support_request WHERE " . $whereUnread);
            $count = $countStmt ? (int) $countStmt->fetchColumn() : 0;
            $sql = "SELECT id, ticket_no, nama_pelapor AS nama, divisi, aset_yang_perlu_diperbaiki AS barang, CONCAT(DATE_FORMAT(tanggal, '%d/%m/%Y'), ' ', TIME_FORMAT(jam, '%H:%i')) AS tanggal_dan_jam FROM it_support_request WHERE " . $whereUnread . " ORDER BY tanggal DESC, jam DESC, id DESC LIMIT 8";
            $stmt = $pdo->query($sql);
            $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $pendingCount = 0;
            $pendingItems = [];
            $isAdmin = AuthController::isAdminSpmt();
            if ($isAdmin) {
                $authModelForAdmin = new AuthModel();
                $pendingUserNotif = $authModelForAdmin->fetchPendingUsersForNotification();
                $pendingCount = (int) ($pendingUserNotif['count'] ?? 0);
                $pendingItems = $pendingUserNotif['items'] ?? [];
            }

            $canAccessAlert = AuthController::canAccessAlertSystem();
            $alertCount = 0;
            $alertItems = [];
            if ($canAccessAlert) {
                $alertCount = (int) ($pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE is_read = 0")->fetchColumn() ?? 0);
                $stmtAlert = $pdo->query("SELECT id, kategori, level, judul, keterangan, created_at FROM alert_notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
                $alertItems = $stmtAlert ? $stmtAlert->fetchAll(PDO::FETCH_ASSOC) : [];
            }

            echo json_encode([
                "count" => $count,
                "items" => $items ?: [],
                "pending_users_count" => $pendingCount,
                "pending_users_items" => $pendingItems ?: [],
                "can_access_alerts" => $canAccessAlert,
                "alerts_count" => $alertCount,
                "alerts_items" => $alertItems ?: [],
                "is_admin" => $isAdmin,
                "has_new_imports" => ($importedCount > 0)
            ]);
        } catch (Throwable $e) {
            echo json_encode(["count" => 0, "items" => []]);
        }
    }

    private function buildAccountReturnUrl(bool $openModal = false): string
    {
        $returnTo = trim((string) ($_POST["return_to"] ?? $_SERVER["HTTP_REFERER"] ?? "index.php?page=dashboard"));
        if ($returnTo === "" || preg_match("#^https?://#i", $returnTo)) {
            $returnTo = "index.php?page=dashboard";
        }
        if (strpos($returnTo, "/") === 0) {
            $returnTo = ltrim($returnTo, "/");
        }
        if (strpos($returnTo, "index.php") !== 0) {
            $returnTo = "index.php?page=dashboard";
        }
        if ($openModal) {
            $separator = strpos($returnTo, "?") === false ? "?" : "&";
            if (strpos($returnTo, "account_modal=1") === false) {
                $returnTo .= $separator . "account_modal=1";
            }
        }
        return $returnTo;
    }

    private function ensureItSupportNotificationReadColumn(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE it_support_request ADD COLUMN notification_read_at DATETIME NULL AFTER updated_at");
        } catch (Throwable $e) {
            // ignore jika kolom sudah ada
        }
    }

    private function markItSupportNotificationRead(PDO $pdo, int $ticketId): void
    {
        if ($ticketId <= 0) { return; }
        try {
            $stmt = $pdo->prepare("UPDATE it_support_request SET notification_read_at = COALESCE(notification_read_at, NOW()) WHERE id = :id LIMIT 1");
            $stmt->execute(["id" => $ticketId]);
        } catch (Throwable $e) {
            // ignore agar halaman detail tetap terbuka
        }
    }

    private function markAllItSupportNotificationsRead(PDO $pdo): void
    {
        try {
            $pdo->exec("UPDATE it_support_request SET notification_read_at = COALESCE(notification_read_at, NOW()) WHERE notification_read_at IS NULL AND UPPER(TRIM(COALESCE(status, \"NOT YET\"))) = \"NOT YET\"");
        } catch (Throwable $e) {
            // ignore agar halaman daftar tiket tetap terbuka
        }
    }

    private function ensureComplaintEmailVerificationColumn(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE it_support_request ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_pelapor");
        } catch (Throwable $e) {
            // ignore jika kolom sudah ada
        }
    }

    private function ensureComplaintEmailNotificationColumns(PDO $pdo): void
    {
        foreach ([
            "ALTER TABLE it_support_request ADD COLUMN handling_email_sent_at DATETIME NULL AFTER handled_by_user_id",
            "ALTER TABLE it_support_request ADD COLUMN handling_email_status VARCHAR(30) NULL AFTER handling_email_sent_at",
            "ALTER TABLE it_support_request ADD COLUMN handling_email_message VARCHAR(255) NULL AFTER handling_email_status"
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // ignore jika kolom sudah ada
            }
        }
    }

    private function sendItSupportHandlingEmail(PDO $pdo, array $ticket): array
    {
        $ticketId = (int) ($ticket['id'] ?? 0);
        $email = trim((string) ($ticket['email_pelapor'] ?? ''));
        if ($ticketId <= 0) {
            return ['sent' => false, 'message' => 'Email tidak diproses karena tiket tidak valid.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->updateItSupportEmailStatus($pdo, $ticketId, 'INVALID_EMAIL', 'Email pelapor tidak valid.');
            return ['sent' => false, 'message' => 'Email tidak dikirim karena alamat pelapor tidak valid.'];
        }

        $ticketNo = (string) ($ticket['ticket_no'] ?? ('TICKET-' . $ticketId));
        $status = strtoupper(trim((string) ($ticket['status'] ?? 'ON PROGRESS')));
        $notes = trim((string) ($ticket['catatan_penanganan'] ?? ''));
        $nama = trim((string) ($ticket['nama_pelapor'] ?? 'Pelapor'));
        $aset = trim((string) ($ticket['aset_yang_perlu_diperbaiki'] ?? '-'));
        $lokasi = trim((string) ($ticket['lokasi_perbaikan'] ?? '-'));
        $handlerName = trim((string) ($_SESSION['auth']['nama_lengkap'] ?? $_SESSION['auth']['username'] ?? 'Tim IT Support'));

        $subject = 'Update Penanganan IT Support - ' . $ticketNo;
        $body = "Halo {$nama},\n\n";
        $body .= "Tiket IT Support Anda sudah diproses.\n\n";
        $body .= "No. Tiket : {$ticketNo}\n";
        $body .= "Status    : {$status}\n";
        $body .= "Aset      : {$aset}\n";
        $body .= "Lokasi    : {$lokasi}\n";
        $body .= "PIC       : {$handlerName}\n\n";
        $body .= "Penanganan:\n" . ($notes !== '' ? $notes : '-') . "\n\n";
        $body .= "Silakan cek kembali kondisi perangkat. Jika masih bermasalah, hubungi kembali tim IT Support.\n\n";
        $body .= "Terima kasih.\nTim IT Support SPMT";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: IT Support SPMT <no-reply@spmt.local>',
            'Reply-To: no-reply@spmt.local',
        ];

        $sent = false;
        if (function_exists('mail')) {
            $sent = @mail($email, $subject, $body, implode("\r\n", $headers));
        }

        if ($sent) {
            $this->updateItSupportEmailStatus($pdo, $ticketId, 'SENT', 'Email penanganan terkirim ke pelapor.');
            return ['sent' => true, 'message' => 'Email penanganan berhasil dikirim ke pelapor.'];
        }

        $logDir = dirname(__DIR__, 2) . '/public/uploads/it-support';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/email-notifications.log';
        $log = "[" . date('Y-m-d H:i:s') . "] TO: {$email} | SUBJECT: {$subject}\n{$body}\n---\n";
        @file_put_contents($logFile, $log, FILE_APPEND);

        $this->updateItSupportEmailStatus($pdo, $ticketId, 'QUEUED_LOG', 'Fungsi mail server belum aktif; email dicatat di log.');
        return ['sent' => false, 'message' => 'Email belum dapat dikirim oleh server, tetapi notifikasi sudah dicatat untuk diproses.'];
    }

    private function updateItSupportEmailStatus(PDO $pdo, int $ticketId, string $status, string $message): void
    {
        try {
            $stmt = $pdo->prepare('UPDATE it_support_request SET handling_email_sent_at = NOW(), handling_email_status = :status, handling_email_message = :message WHERE id = :id LIMIT 1');
            $stmt->execute([
                'status' => $status,
                'message' => mb_substr($message, 0, 250),
                'id' => $ticketId,
            ]);
        } catch (Throwable $e) {
            // ignore agar proses simpan tiket tetap berhasil
        }
    }

    private function ensureComplaintHistoryTable(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS it_support_request_history (
  id BIGINT NOT NULL AUTO_INCREMENT,
  request_id BIGINT NOT NULL,
  ticket_no VARCHAR(30) NOT NULL,
  old_status ENUM('NOT YET','ON PROGRESS','DONE') NOT NULL,
  new_status ENUM('NOT YET','ON PROGRESS','DONE') NOT NULL,
  old_catatan_penanganan TEXT NULL,
  new_catatan_penanganan TEXT NULL,
  changed_by_user_id BIGINT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_it_support_history_request (request_id),
  KEY idx_it_support_history_ticket (ticket_no),
  KEY idx_it_support_history_changed_by (changed_by_user_id),
  KEY idx_it_support_history_changed_at (changed_at),
  CONSTRAINT fk_it_support_history_request FOREIGN KEY (request_id) REFERENCES it_support_request (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_it_support_history_changed_by FOREIGN KEY (changed_by_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    private function buildComplaintUrl(): string
    {
        $query = ['page' => 'data-keluhan'];
        $keys = ['complaint_status', 'complaint_division', 'complaint_search', 'complaint_date_from', 'complaint_date_to'];
        foreach ($keys as $key) {
            $value = trim((string) ($_GET[$key] ?? $_SESSION['spmt_context'][$key] ?? ''));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }

        return 'index.php?' . http_build_query($query);
    }


    private function streamComplaintExport(PDO $pdo, array $filters, string $format): void
    {
        $rows = $this->model->exportComplaintRows($pdo, $filters);
        $title = $this->buildComplaintExportTitle($filters);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($title));
        $base = trim((string) $base, '_');
        if ($base === '') {
            $base = 'it_support_request_export';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (strtolower($format) === 'xlsx') {
            $xlsx = $this->buildComplaintExcelXlsx($title, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
            header('Content-Length: ' . strlen($xlsx));
            echo $xlsx;
            exit;
        }

        $pdf = $this->buildComplaintPdf($title, $rows);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildComplaintExportTitle(array $filters): string
    {
        $parts = ['IT Support Request'];

        $status = strtoupper(trim((string) ($filters['complaint_status'] ?? '')));
        $division = trim((string) ($filters['complaint_division'] ?? ''));
        $dateFrom = trim((string) ($filters['complaint_date_from'] ?? ''));
        $dateTo = trim((string) ($filters['complaint_date_to'] ?? ''));
        $search = trim((string) ($filters['complaint_search'] ?? ''));

        if ($status !== '') {
            $parts[] = $status;
        }
        if ($division !== '') {
            $parts[] = $division;
        }
        if ($dateFrom !== '' || $dateTo !== '') {
            $parts[] = ($dateFrom !== '' ? $dateFrom : 'awal') . ' s.d. ' . ($dateTo !== '' ? $dateTo : 'sekarang');
        }
        if ($search !== '') {
            $parts[] = 'Filter';
        }

        return implode(' - ', $parts);
    }

    private function buildComplaintExcelXlsx(string $title, array $rows): string
    {
        return $this->buildComplaintExcelXlsxWithImages($title, $rows);
    }

    private function buildComplaintExcelXlsxWithImages(string $title, array $rows): string
    {
        $baseDir = rtrim(str_replace('\\', '/', __DIR__ . '/../../'), '/');

        // ── Resolve absolute filesystem path for an image stored in DB ─────────
        // DB stores paths like:
        //   "public/uploads/it-support/gform_xxx.jpg"       → direct relative
        //   "public/assets/images/complaint-1.png"           → normalizeReportAssetPath strips public/assets/ → "images/complaint-1.png" (wrong)
        // We bypass normalizeReportAssetPath and always try the RAW DB value first.
        $resolveImagePath = function (string $rawDbPath) use ($baseDir): string {
            $rawDbPath = trim(str_replace('\\', '/', $rawDbPath));
            if ($rawDbPath === '' || $rawDbPath === '-') {
                return '';
            }
            // Already absolute on the filesystem
            if (file_exists($rawDbPath)) {
                return $rawDbPath;
            }
            // Try directly under project root (handles "public/..." paths correctly)
            $candidate = $baseDir . '/' . ltrim($rawDbPath, '/');
            if (file_exists($candidate)) {
                return $candidate;
            }
            // Also try without leading "public/" in case a different prefix is used
            $candidate2 = $baseDir . '/public/' . ltrim($rawDbPath, '/');
            if (file_exists($candidate2)) {
                return $candidate2;
            }
            return '';
        };

        // ── Detect submission source ───────────────────────────────────────────
        $detectSource = function (array $row): string {
            $img = (string) ($row['doc_image_raw'] ?? $row['doc_image'] ?? '');
            if (strpos($img, 'gform_') !== false) {
                return 'Google Form';
            }
            if (strpos($img, 'it_support_') !== false) {
                return 'Lokal';
            }
            $email = strtolower((string) ($row['email_plain'] ?? $row['email'] ?? ''));
            if (strpos($email, '@spmt') !== false || strpos($email, '@pelindo') !== false) {
                return 'Lokal';
            }
            return 'Lokal';
        };

        // ── Column layout ──────────────────────────────────────────────────────
        // No | Gambar | Ticket | Tanggal & Jam | Status | Nama | Email | Divisi | Barang | Lokasi | Deskripsi | Catatan Penanganan | Ditangani Oleh | Sumber
        $colWidths = [6, 18, 18, 22, 18, 22, 30, 18, 22, 20, 38, 38, 22, 12];
        $headerRow    = 4;   // row 4 = column headers
        $dataStartRow = 5;   // first data row
        $rowHtPt      = 60;  // data row height in points

        // ── Resolve raw DB image paths (before normalizeReportAssetPath stripped them)
        // Re-query the raw dokumentasi_kerusakan field so we get the original path
        // We do this by fetching it ourselves via the already-passed $rows,
        // but doc_image in $rows already went through normalizeReportAssetPath.
        // So we need to reconstruct: if doc_image looks like "images/complaint-1.png"
        // it was stripped from "public/assets/images/complaint-1.png" — we fix that here.
        $fixDocImagePath = function (string $docImage): string {
            if ($docImage === '' || $docImage === '-') {
                return '';
            }
            // Already starts with public/ → untouched by normalizeReportAssetPath → use as-is
            if (strpos($docImage, 'public/') === 0) {
                return $docImage;
            }
            // Looks like it had public/assets/ stripped. Add it back to try.
            return 'public/assets/' . ltrim($docImage, '/');
        };

        // ── Collect image data ─────────────────────────────────────────────────
        $images   = [];
        $mediaIdx = 0;
        $rowData  = array_values($rows);

        foreach ($rowData as $idx => $row) {
            $excelRow  = $dataStartRow + $idx;
            $docImage  = (string) ($row['doc_image'] ?? '');
            $fixedPath = $fixDocImagePath($docImage);
            $absPath   = $resolveImagePath($fixedPath);
            if ($absPath === '') {
                continue;
            }
            $rawData = @file_get_contents($absPath);
            if ($rawData === false || strlen($rawData) < 8) {
                continue;
            }
            // Detect MIME to pick extension
            $ext   = 'jpg';
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_buffer($finfo, $rawData) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime === 'image/png') {
                $ext = 'png';
            } elseif ($mime === 'image/gif') {
                $ext = 'gif';
            }

            // Normalisasi/Resize gambar ke lebar 100px x tinggi 75px (4:3) dengan GD
            if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
                $srcImg = @imagecreatefromstring($rawData);
                if ($srcImg !== false) {
                    $origWidth  = imagesx($srcImg);
                    $origHeight = imagesy($srcImg);
                    if ($origWidth > 0 && $origHeight > 0) {
                        $targetW = 100;
                        $targetH = 75;
                        $newImg  = imagecreatetruecolor($targetW, $targetH);

                        if ($ext === 'png' || $ext === 'gif') {
                            imagealphablending($newImg, false);
                            imagesavealpha($newImg, true);
                            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                            imagefilledrectangle($newImg, 0, 0, $targetW, $targetH, $transparent);
                        } else {
                            $white = imagecolorallocate($newImg, 255, 255, 255);
                            imagefill($newImg, 0, 0, $white);
                        }

                        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origWidth, $origHeight);

                        ob_start();
                        if ($ext === 'png') {
                            imagepng($newImg);
                        } elseif ($ext === 'gif') {
                            imagegif($newImg);
                        } else {
                            imagejpeg($newImg, null, 90);
                        }
                        $resizedData = ob_get_clean();
                        if ($resizedData !== false && strlen($resizedData) > 0) {
                            $rawData = $resizedData;
                        }
                        imagedestroy($newImg);
                    }
                    imagedestroy($srcImg);
                }
            }

            $mediaIdx++;
            $images[$excelRow] = [
                'ext'      => $ext,
                'data'     => $rawData,
                'mediaIdx' => $mediaIdx,
            ];
        }

        // ── OOXML style index map ──────────────────────────────────────────────
        // s=0  normal data cell:   Calibri 11, wrap, vcenter, thin black border
        // s=1  header cell:        Calibri 11 bold, center h+v, wrap, yellow bg, thin border
        // s=2  title cell (row1):  Calibri 13 bold, no border
        // s=3  meta cell (row2):   Calibri 10 normal
        // s=4  status DONE:        green bg, dark green bold, center, thin border
        // s=5  status ON PROGRESS: amber bg, dark orange bold, center, thin border
        // s=6  status NOT YET:     pink bg, dark red bold, center, thin border
        // s=7  normal center:      Calibri 11, center h, vcenter, thin border

        // We'll build the styles XML manually
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // Fonts: 0=normal, 1=bold-11, 2=bold-14-title, 3=normal-10, 4=bold-green, 5=bold-orange, 6=bold-red
            . '<fonts count="7">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'                                         // 0 normal
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'                                     // 1 bold header
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'                                     // 2 bold title (14)
            . '<font><sz val="10"/><name val="Calibri"/></font>'                                         // 3 normal-10 meta
            . '<font><b/><sz val="11"/><color rgb="FF1D6F42"/><name val="Calibri"/></font>'              // 4 dark green
            . '<font><b/><sz val="11"/><color rgb="FF974706"/><name val="Calibri"/></font>'              // 5 dark orange
            . '<font><b/><sz val="11"/><color rgb="FF9C1A1A"/><name val="Calibri"/></font>'              // 6 dark red
            . '</fonts>'
            // Fills: 0=none, 1=gray125(required), 2=yellow header, 3=green status, 4=amber status, 5=pink status
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFD965"/></patternFill></fill>'   // 2 yellow
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE1F5EE"/></patternFill></fill>'   // 3 light green
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFAEEDA"/></patternFill></fill>'   // 4 light amber
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCEBEB"/></patternFill></fill>'   // 5 light pink
            . '</fills>'
            // Borders: 0=none, 1=thin-black all sides
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FF000000"/></left>'
            . '<right style="thin"><color rgb="FF000000"/></right>'
            . '<top style="thin"><color rgb="FF000000"/></top>'
            . '<bottom style="thin"><color rgb="FF000000"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            // Cell XFs (s= values):
            . '<cellXfs count="9">'
            // s=0 default: no border, standard Calibri 11
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // s=1 header: yellow bg, bold, center h+v, border
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=2 title row1: bold-14, no border, center h+v
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=3 meta row2: normal-10, no border, center h+v
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=4 DONE: green bg, dark green bold, center, border
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=5 ON PROGRESS: amber bg, orange bold, center, border
            . '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=6 NOT YET: pink bg, red bold, center, border
            . '<xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=7 normal center: border, center h, vcenter
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // s=8 normal data: border, wrap, vcenter
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        // ── XML helpers ────────────────────────────────────────────────────────
        $xmlEsc = fn(string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $colName = function (int $n): string {
            $letters = '';
            while ($n > 0) {
                $n--;
                $letters = chr(65 + ($n % 26)) . $letters;
                $n = (int) ($n / 26);
            }
            return $letters;
        };

        // Status → style index
        $statusStyle = function (string $status): int {
            $s = strtoupper(trim($status));
            if ($s === 'DONE') {
                return 4;
            }
            if ($s === 'ON PROGRESS') {
                return 5;
            }
            return 6; // NOT YET and anything else
        };

        // ── Build worksheet rows ───────────────────────────────────────────────
        $xmlRowParts = [];

        // Row 1: title (merged, centered, bold-14)
        $xmlRowParts[] = '<row r="1" ht="26" customHeight="1">'
            . '<c r="A1" t="inlineStr" s="2"><is><t>' . $xmlEsc($title) . '</t></is></c>'
            . '</row>';

        // Row 2: export date (merged, centered, normal-10)
        $xmlRowParts[] = '<row r="2" ht="18" customHeight="1">'
            . '<c r="A2" t="inlineStr" s="3"><is><t>Diexport: ' . $xmlEsc(date('d-m-Y H:i:s')) . '</t></is></c>'
            . '</row>';

        // Row 3: empty
        $xmlRowParts[] = '<row r="3"></row>';

        // Row 4: header
        $headers = ['No', 'Gambar', 'Ticket', 'Tanggal & Jam', 'Status', 'Nama', 'Email', 'Divisi', 'Barang', 'Lokasi', 'Deskripsi', 'Catatan Penanganan', 'Ditangani Oleh', 'Sumber'];
        $headerCells = '';
        foreach ($headers as $ci => $hdr) {
            $ref = $colName($ci + 1) . '4';
            $headerCells .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . $xmlEsc($hdr) . '</t></is></c>';
        }
        $xmlRowParts[] = '<row r="4" ht="22" customHeight="1">' . $headerCells . '</row>';

        // Data rows
        foreach ($rowData as $idx => $row) {
            $excelRow = $dataStartRow + $idx;
            $hasImage = isset($images[$excelRow]);
            $sumber   = $detectSource($row);
            $docImage = (string) ($row['doc_image'] ?? '');

            // Status style
            $statusVal = (string) ($row['status'] ?? '-');
            $sStyle    = $statusStyle($statusVal);

            // Gambar cell: always render with style s="7" (center with border) to ensure border is drawn,
            // even if image is embedded over it.
            $gambarText = '';
            if (!$hasImage) {
                $fixedP = $fixDocImagePath($docImage);
                $absP   = $resolveImagePath($fixedP);
                $gambarText = ($docImage !== '' && $docImage !== '-' && $absP === '') ? 'Gambar tidak tersedia' : '-';
            }
            $gambarCellStr = '<c r="B' . $excelRow . '" t="inlineStr" s="7"><is><t>' . $xmlEsc($gambarText) . '</t></is></c>';

            $cellParts = [
                '<c r="A' . $excelRow . '" t="inlineStr" s="7"><is><t>' . $xmlEsc((string)($idx + 1)) . '</t></is></c>',
                $gambarCellStr,
                '<c r="C' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['ticket_no'] ?? '-')) . '</t></is></c>',
                '<c r="D' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['datetime_plain'] ?? '-')) . '</t></is></c>',
                '<c r="E' . $excelRow . '" t="inlineStr" s="' . $sStyle . '"><is><t>' . $xmlEsc($statusVal) . '</t></is></c>',
                '<c r="F' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['name'] ?? '-')) . '</t></is></c>',
                '<c r="G' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['email_plain'] ?? ($row['email'] ?? '-'))) . '</t></is></c>',
                '<c r="H' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['division'] ?? '-')) . '</t></is></c>',
                '<c r="I' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['item'] ?? '-')) . '</t></is></c>',
                '<c r="J' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['location'] ?? '-')) . '</t></is></c>',
                '<c r="K' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc((string)($row['description'] ?? '-')) . '</t></is></c>',
                '<c r="L' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc(($row['catatan_penanganan'] ?? '') !== '' ? (string)$row['catatan_penanganan'] : '-') . '</t></is></c>',
                '<c r="M' . $excelRow . '" t="inlineStr" s="8"><is><t>' . $xmlEsc(($row['handled_by_name'] ?? '') !== '' ? (string)$row['handled_by_name'] : '-') . '</t></is></c>',
                '<c r="N' . $excelRow . '" t="inlineStr" s="7"><is><t>' . $xmlEsc($sumber) . '</t></is></c>',
            ];

            $xmlRowParts[] = '<row r="' . $excelRow . '" customHeight="1" ht="' . $rowHtPt . '">'
                . implode('', array_filter($cellParts, fn($c) => $c !== ''))
                . '</row>';
        }

        // ── Column widths XML ──────────────────────────────────────────────────
        $colDefsXml = '';
        foreach ($colWidths as $i => $w) {
            $colDefsXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $totalRows   = $dataStartRow + count($rowData) - 1;
        $lastColLtr  = $colName(count($colWidths));
        $hasAnyImage = !empty($images);
        $drawingTag  = $hasAnyImage ? '<drawing r:id="rId1"/>' : '';

        // ── Build sheet XML with freeze pane on row 5 ─────────────────────────
        $mergeCellsTag = '<mergeCells count="2">'
            . '<mergeCell ref="A1:N1"/>'
            . '<mergeCell ref="A2:N2"/>'
            . '</mergeCells>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $lastColLtr . $totalRows . '"/>'
            . '<sheetViews>'
            .   '<sheetView workbookViewId="0">'
            .     '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'
            .     '<selection pane="bottomLeft" activeCell="A5" sqref="A5"/>'
            .   '</sheetView>'
            . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $colDefsXml . '</cols>'
            . '<sheetData>' . implode('', $xmlRowParts) . '</sheetData>'
            . $mergeCellsTag
            . $drawingTag
            . '</worksheet>';

        // ── Drawing XML (image anchors) ────────────────────────────────────────
        $drawingXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing"'
            . ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        $drawingRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($images as $excelRow => $img) {
            $rId  = 'rId' . $img['mediaIdx'];
            $col  = 1;               // Column B = 0-based index 1
            $row0 = $excelRow - 1;   // 0-based row index

            $cx = 100 * 9525;  // 100px in EMUs (952500)
            $cy = 75 * 9525;   // 75px in EMUs (714375)
            $colOff = 200000;  // Center horizontally inside Column B (width 18)
            $rowOff = 20000;   // Center vertically inside 60pt row height

            $drawingXml .= '<xdr:oneCellAnchor>'
                . '<xdr:from>'
                .   '<xdr:col>' . $col . '</xdr:col><xdr:colOff>' . $colOff . '</xdr:colOff>'
                .   '<xdr:row>' . $row0 . '</xdr:row><xdr:rowOff>' . $rowOff . '</xdr:rowOff>'
                . '</xdr:from>'
                . '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>'
                . '<xdr:pic>'
                .   '<xdr:nvPicPr>'
                .     '<xdr:cNvPr id="' . $img['mediaIdx'] . '" name="Gambar' . $img['mediaIdx'] . '"/>'
                .     '<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>'
                .   '</xdr:nvPicPr>'
                .   '<xdr:blipFill>'
                .     '<a:blip r:embed="' . $rId . '"/>'
                .     '<a:stretch><a:fillRect/></a:stretch>'
                .   '</xdr:blipFill>'
                .   '<xdr:spPr>'
                .     '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
                .     '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
                .   '</xdr:spPr>'
                . '</xdr:pic>'
                . '<xdr:clientData/>'
                . '</xdr:oneCellAnchor>';

            $drawingRels .= '<Relationship Id="' . $rId . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"'
                . ' Target="../media/image' . $img['mediaIdx'] . '.' . $img['ext'] . '"/>';
        }

        $drawingXml  .= '</xdr:wsDr>';
        $drawingRels .= '</Relationships>';

        // ── [Content_Types].xml ────────────────────────────────────────────────
        $mediaDefaultTypes = '';
        $seenExts = [];
        foreach ($images as $img) {
            if (!isset($seenExts[$img['ext']])) {
                $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
                $mediaDefaultTypes .= '<Default Extension="' . $img['ext'] . '" ContentType="' . ($mimeMap[$img['ext']] ?? 'image/jpeg') . '"/>';
                $seenExts[$img['ext']] = true;
            }
        }

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $mediaDefaultTypes
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . ($hasAnyImage ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $sheetRels = $hasAnyImage
            ? ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
                . '</Relationships>')
            : '';

        // ── Assemble ZIP ───────────────────────────────────────────────────────
        $files = [
            '[Content_Types].xml'        => $contentTypesXml,
            '_rels/.rels'                => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
                . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
                . '</Relationships>',
            'docProps/core.xml'          => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
                . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
                . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<dc:title>DataKeluhan</dc:title><dc:creator>Sistem Inventory</dc:creator>'
                . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
                . '</cp:coreProperties>',
            'docProps/app.xml'           => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
                . '<Application>Microsoft Excel</Application></Properties>',
            'xl/workbook.xml'            => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="DataKeluhan" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml'              => $stylesXml,
            'xl/worksheets/sheet1.xml'   => $sheetXml,
        ];

        if ($hasAnyImage) {
            $files['xl/drawings/drawing1.xml']            = $drawingXml;
            $files['xl/drawings/_rels/drawing1.xml.rels'] = $drawingRels;
            foreach ($images as $img) {
                $files['xl/media/image' . $img['mediaIdx'] . '.' . $img['ext']] = $img['data'];
            }
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = $sheetRels;
        }

        return $this->buildZipBinary($files);
    }

    private function buildComplaintPdf(string $title, array $rows): string
    {
        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 33;
        $blue = '0.18 0.41 0.67';
        $lightBlue = '0.90 0.95 0.99';
        $textColor = '0.12 0.18 0.25';
        $borderColor = '0.75 0.81 0.89';

        $objects = [];
        $objects[1] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[2] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        // ── Resolve image absolute path and create PDF Image XObjects ───────────
        $baseDir = rtrim(str_replace('\\', '/', __DIR__ . '/../../'), '/');

        $resolveImagePath = function (string $rawDbPath) use ($baseDir): string {
            $rawDbPath = trim(str_replace('\\', '/', $rawDbPath));
            if ($rawDbPath === '' || $rawDbPath === '-') {
                return '';
            }
            if (file_exists($rawDbPath)) {
                return $rawDbPath;
            }
            $candidate = $baseDir . '/' . ltrim($rawDbPath, '/');
            if (file_exists($candidate)) {
                return $candidate;
            }
            $candidate2 = $baseDir . '/public/' . ltrim($rawDbPath, '/');
            if (file_exists($candidate2)) {
                return $candidate2;
            }
            return '';
        };

        $fixDocImagePath = function (string $docImage): string {
            if ($docImage === '' || $docImage === '-') {
                return '';
            }
            if (strpos($docImage, 'public/') === 0) {
                return $docImage;
            }
            return 'public/assets/' . ltrim($docImage, '/');
        };

        $detectSource = function (array $row): string {
            $img = (string) ($row['doc_image_raw'] ?? $row['doc_image'] ?? '');
            if (strpos($img, 'gform_') !== false) {
                return 'Google Form';
            }
            if (strpos($img, 'it_support_') !== false) {
                return 'Lokal';
            }
            $email = strtolower((string) ($row['email_plain'] ?? $row['email'] ?? ''));
            if (strpos($email, '@spmt') !== false || strpos($email, '@pelindo') !== false) {
                return 'Lokal';
            }
            return 'Lokal';
        };

        $imgObjMap = [];
        foreach (array_values($rows) as $idx => $row) {
            $docImage  = (string) ($row['doc_image'] ?? '');
            $fixedPath = $fixDocImagePath($docImage);
            $absPath   = $resolveImagePath($fixedPath);
            if ($absPath === '') {
                continue;
            }
            $rawData = @file_get_contents($absPath);
            if ($rawData === false || strlen($rawData) < 8) {
                continue;
            }

            if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
                $srcImg = @imagecreatefromstring($rawData);
                if ($srcImg !== false) {
                    $origWidth  = imagesx($srcImg);
                    $origHeight = imagesy($srcImg);
                    if ($origWidth > 0 && $origHeight > 0) {
                        $targetW = 100;
                        $targetH = 75;
                        $newImg  = imagecreatetruecolor($targetW, $targetH);
                        $white = imagecolorallocate($newImg, 255, 255, 255);
                        imagefill($newImg, 0, 0, $white);
                        imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origWidth, $origHeight);

                        ob_start();
                        imagejpeg($newImg, null, 80);
                        $jpgData = ob_get_clean();
                        imagedestroy($newImg);

                        if ($jpgData !== false && strlen($jpgData) > 0) {
                            $objId = count($objects) + 1;
                            $objects[$objId] = "<< /Type /XObject /Subtype /Image /Width $targetW /Height $targetH /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($jpgData) . " >>\nstream\n" . $jpgData . "\nendstream";
                            $imgObjMap[$idx] = [
                                'objId' => $objId,
                                'name' => 'Img' . $objId
                            ];
                        }
                    }
                    imagedestroy($srcImg);
                }
            }
        }

        $streams = [];
        $current = [];
        $y = $pageHeight - $margin;

        $flushPage = function () use (&$streams, &$current) {
            $streams[] = implode("\n", $current);
            $current = [];
        };

        $addText = function (float $x, float $y, string $text, int $fontId = 1, int $fontSize = 10, string $color = '0 0 0') use (&$current) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
            $current[] = 'BT';
            $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize);
            $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
            $current[] = '(' . $safe . ') Tj';
            $current[] = 'ET';
        };

        $addRect = function (float $x, float $y, float $w, float $h, ?string $fill = null, ?string $stroke = null, float $lineWidth = 1.0) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            if ($stroke !== null) { $current[] = $stroke . ' RG'; }
            $current[] = sprintf('%.2f %.2f %.2f %.2f re', $x, $y, $w, $h);
            $current[] = ($fill !== null && $stroke !== null) ? 'B' : (($fill !== null) ? 'f' : 'S');
        };

        $wrapText = function (string $text, int $maxChars): array {
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));
            if ($text === '' || $text === '-') {
                return ['-'];
            }
            $words = preg_split('/\s+/u', $text) ?: [];
            $lines = [];
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : ($line . ' ' . $word);
                if (mb_strlen($candidate) <= $maxChars) {
                    $line = $candidate;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                }
                if (mb_strlen($word) <= $maxChars) {
                    $line = $word;
                    continue;
                }
                $chunks = mb_str_split($word, max(4, $maxChars - 1));
                foreach ($chunks as $chunkIndex => $chunk) {
                    if ($chunkIndex < count($chunks) - 1) {
                        $lines[] = $chunk;
                    } else {
                        $line = $chunk;
                    }
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            return array_slice($lines ?: ['-'], 0, 3);
        };

        $renderHeader = function () use (&$y, $pageHeight, $margin, $pageWidth, $blue, $lightBlue, $textColor, $borderColor, $title, $addRect, $addText) {
            $y = $pageHeight - $margin;
            $addRect($margin, $y - 44, $pageWidth - ($margin * 2), 44, $blue, null, 0);
            $addText($margin + 12, $y - 18, 'EXPORT DATA KELUHAN', 2, 16, '1 1 1');
            $addText($margin + 12, $y - 34, $title, 1, 9, '1 1 1');
            $y -= 60;
            $addRect($margin, $y - 18, $pageWidth - ($margin * 2), 18, $lightBlue, $borderColor, 0.8);
            $addText($margin + 10, $y - 12, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 8, $textColor);
            $y -= 28;
        };

        $columns = [
            ['title' => 'NO', 'width' => 20, 'chars' => 3],
            ['title' => 'GAMBAR', 'width' => 46, 'chars' => 8],
            ['title' => 'TICKET', 'width' => 62, 'chars' => 13],
            ['title' => 'TGL/JAM', 'width' => 65, 'chars' => 14],
            ['title' => 'STATUS', 'width' => 45, 'chars' => 9],
            ['title' => 'NAMA', 'width' => 75, 'chars' => 16],
            ['title' => 'DIVISI', 'width' => 45, 'chars' => 9],
            ['title' => 'BARANG', 'width' => 50, 'chars' => 10],
            ['title' => 'LOKASI', 'width' => 45, 'chars' => 9],
            ['title' => 'DESKRIPSI', 'width' => 110, 'chars' => 24],
            ['title' => 'CATATAN PENANGANAN', 'width' => 110, 'chars' => 24],
            ['title' => 'DITANGANI OLEH', 'width' => 65, 'chars' => 13],
            ['title' => 'SUMBER', 'width' => 38, 'chars' => 7],
        ];

        $drawTableHeader = function () use (&$y, $margin, $columns, $addRect, $addText, $blue, $borderColor) {
            $headerHeight = 22;
            $x = $margin;
            foreach ($columns as $column) {
                $addRect($x, $y - $headerHeight, $column['width'], $headerHeight, $blue, $borderColor, 0.7);
                $title = $column['title'];
                $fontSize = 6.5;
                if ($title === 'CATATAN PENANGANAN') {
                    $addText($x + 2, $y - 10, 'CATATAN', 2, 6, '1 1 1');
                    $addText($x + 2, $y - 18, 'PENANGANAN', 2, 6, '1 1 1');
                } else if ($title === 'DITANGANI OLEH') {
                    $addText($x + 2, $y - 10, 'DITANGANI', 2, 6, '1 1 1');
                    $addText($x + 2, $y - 18, 'OLEH', 2, 6, '1 1 1');
                } else {
                    $addText($x + 2, $y - 14, $title, 2, $fontSize, '1 1 1');
                }
                $x += $column['width'];
            }
            $y -= $headerHeight;
        };

        $renderHeader();
        $drawTableHeader();

        foreach (array_values($rows) as $index => $row) {
            $sumber = $detectSource($row);
            $hasImage = isset($imgObjMap[$index]);

            $docImage = (string) ($row['doc_image'] ?? '');
            $fixedP = $fixDocImagePath($docImage);
            $absP   = $resolveImagePath($fixedP);
            $gambarText = ($docImage !== '' && $docImage !== '-' && $absP === '') ? 'Gambar tidak tersedia' : '-';

            $cells = [
                (string) ($index + 1),
                $gambarText,
                (string) ($row['ticket_no'] ?? '-'),
                (string) ($row['datetime_plain'] ?? '-'),
                (string) ($row['status'] ?? '-'),
                (string) ($row['name'] ?? '-'),
                (string) ($row['division'] ?? '-'),
                (string) ($row['item'] ?? '-'),
                (string) ($row['location'] ?? '-'),
                (string) ($row['description'] ?? '-'),
                (string) (($row['catatan_penanganan'] ?? '') !== '' ? $row['catatan_penanganan'] : '-'),
                (string) (($row['handled_by_name'] ?? '') !== '' ? $row['handled_by_name'] : '-'),
                (string) $sumber,
            ];

            $wrapped = [];
            $maxLines = 1;
            foreach ($columns as $i => $column) {
                if ($i === 1 && $hasImage) {
                    $wrapped[$i] = [];
                } else {
                    $wrapped[$i] = $wrapText($cells[$i] ?? '-', (int) $column['chars']);
                    $maxLines = max($maxLines, count($wrapped[$i]));
                }
            }

            $rowHeight = max(24, 8 + ($maxLines * 11));
            if ($hasImage) {
                $rowHeight = max($rowHeight, 40);
            }

            if ($y - $rowHeight < 40) {
                $flushPage();
                $renderHeader();
                $drawTableHeader();
            }

            $x = $margin;
            foreach ($columns as $i => $column) {
                $addRect($x, $y - $rowHeight, $column['width'], $rowHeight, '1 1 1', $borderColor, 0.5);
                
                if ($i === 1 && $hasImage) {
                    $img = $imgObjMap[$index];
                    $imgW = 40;
                    $imgH = 30;
                    $dx = ($column['width'] - $imgW) / 2;
                    $dy = ($rowHeight - $imgH) / 2;
                    $imgX = $x + $dx;
                    $imgY = ($y - $rowHeight) + $dy;

                    $current[] = 'q';
                    $current[] = sprintf('%.2f 0 0 %.2f %.2f %.2f cm', $imgW, $imgH, $imgX, $imgY);
                    $current[] = '/' . $img['name'] . ' Do';
                    $current[] = 'Q';
                } else {
                    $lineY = $y - 12;
                    foreach ($wrapped[$i] as $line) {
                        $addText($x + 2, $lineY, $line, 1, 6.5, $textColor);
                        $lineY -= 9;
                    }
                }
                $x += $column['width'];
            }

            $y -= $rowHeight;
        }

        if (empty($rows)) {
            $addRect($margin, $y - 24, $pageWidth - ($margin * 2), 24, '1 1 1', $borderColor, 0.6);
            $addText($margin + 10, $y - 15, 'Tidak ada tiket yang cocok dengan filter export.', 1, 9, $textColor);
        }

        $flushPage();

        $pageObjectNumbers = [];
        $contentObjectNumbers = [];
        foreach ($streams as $stream) {
            $contentObjectNumbers[] = count($objects) + 1;
            $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $pageObjectNumbers[] = count($objects) + 1;
            $objects[] = '';
        }

        $xobjectDict = '';
        if (!empty($imgObjMap)) {
            $xobjectDict = ' /XObject <<';
            foreach ($imgObjMap as $img) {
                $xobjectDict .= ' /' . $img['name'] . ' ' . $img['objId'] . ' 0 R';
            }
            $xobjectDict .= ' >>';
        }

        $pagesObjectNumber = count($objects) + 1;
        foreach ($pageObjectNumbers as $index => $pageObjectNumber) {
            $objects[$pageObjectNumber] = '<< /Type /Page /Parent ' . $pagesObjectNumber . ' 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 1 0 R /F2 2 0 R >>' . $xobjectDict . ' >> /Contents ' . $contentObjectNumbers[$index] . ' 0 R >>';
        }

        $kids = implode(' ', array_map(static fn ($n) => $n . ' 0 R', $pageObjectNumbers));
        $objects[$pagesObjectNumber] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageObjectNumbers) . ' >>';
        $catalogObjectNumber = count($objects) + 1;
        $objects[$catalogObjectNumber] = '<< /Type /Catalog /Pages ' . $pagesObjectNumber . ' 0 R >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        for ($i = 1; $i <= count($objects); $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root ' . $catalogObjectNumber . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function handleNewInventoryAction(string $page): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string) ($_POST['action'] ?? '');
        if (!in_array($action, ['save_pc_new', 'save_other_new', 'save_cctv_inventaris_new', 'save_printer_inventaris_new'], true)) {
            return;
        }

        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
            header('Location: index.php?page=' . urlencode($page));
            exit;
        }

        if ($action === 'save_cctv_inventaris_new') {
            $this->insertCctvInventaris($pdo, $_POST);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Inventaris CCTV baru berhasil ditambahkan.'];
            header('Location: index.php?page=inventory-other&inv_tab=cctv');
            exit;
        }

        // Printer Inventaris
        if ($action === 'save_printer_inventaris_new') {
            $savedPath = $this->saveUploadedImage($_FILES['printer_gambar_file'] ?? null, 'printer');
            $this->insertPrinterInventaris($pdo, $_POST, $savedPath);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Inventaris Printer baru berhasil ditambahkan.'];
            header('Location: index.php?page=inventory-other&inv_tab=printer');
            exit;
        }

        try {
            $divisionCode = trim((string) ($_POST['division_code'] ?? ''));
            $division = $this->fetchInventoryDivisionByCode($pdo, $divisionCode);
            $inventoryDb = (string) ($division['inventory_db_name'] ?? '');
            if (!$division || !$this->isSafeIdentifier($inventoryDb)) {
                throw new RuntimeException('Divisi inventaris tidak valid.');
            }

            if ($action === 'save_pc_new') {
                $payload = $_POST;
                if (trim((string) ($payload['unit_kerja'] ?? '')) === '') {
                    $payload['unit_kerja'] = trim((string) ($_POST['division_label'] ?? $division['division_label'] ?? ''));
                }
                $this->ensurePcSchema($pdo, $inventoryDb);
                $this->insertPcRow($pdo, $inventoryDb, $payload);
                $pageKey = $this->buildPageKeyFromInput($payload);
                $this->model->logInventoryUpdate($pdo, $divisionCode, $pageKey, $inventoryDb, 'create', 'pc', (string) ($payload['id_inventaris'] ?? ''));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'PC berhasil diinput ke database terkait. Tabel detail inventaris otomatis bertambah.'];
                $lastPage = $this->resolveUserPageNumberByPageKey($pdo, $divisionCode, $pageKey, $this->resolveLastUserPageNumber($pdo, $divisionCode));
                header('Location: ' . $this->buildDetailUrl([
                    'division_code'    => $divisionCode,
                    'display_division' => (string) ($division['division_label'] ?? ''),
                    'user_page'        => $lastPage,
                    'user'             => (string) ($payload['user'] ?? ''),
                    'focus_item'       => $this->buildPcFocusItemFromInput($payload),
                    'after_add_inventory' => '1',
                ]));
                exit;
            }

            // --- PERANGKAT LAIN: Mode A (linked) atau Mode B (standalone) ---
            $inputMode = trim((string) ($_POST['input_mode'] ?? 'linked'));
            $isStandalone = ($inputMode === 'standalone');

            $this->ensurePcSchema($pdo, $inventoryDb);
            $this->ensureInventoryOtherSchema($pdo, $inventoryDb);

            if ($isStandalone) {
                // Mode B — Barang Mandiri: tidak terikat ke PC manapun
                $focusItem = $this->insertStandaloneOtherRow($pdo, $inventoryDb, $_POST, $_FILES);
                $this->model->logInventoryUpdate($pdo, $divisionCode, '', $inventoryDb, 'create', 'perangkat_lain', (string) ($_POST['other_id_inventaris'] ?? ''));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Perangkat mandiri baru berhasil diinput ke divisi ' . strtoupper((string) ($division['division_label'] ?? $divisionCode)) . '.'];
                header('Location: ' . $this->buildDetailUrl([
                    'division_code'    => $divisionCode,
                    'display_division' => (string) ($division['division_label'] ?? ''),
                    'after_add_inventory' => '1',
                ]));
                exit;
            }

            // Mode A — Terhubung ke PC
            $pcRowId = max(0, (int) ($_POST['pc_row_id'] ?? 0));
            if ($pcRowId <= 0) {
                throw new RuntimeException('Pilih PC terlebih dahulu untuk Mode Terhubung ke PC.');
            }
            // Ambil data PC by id untuk auto-fill user & unit_kerja
            $linkedPcStmt = $pdo->prepare(sprintf('SELECT * FROM `%s`.pc WHERE id = :id LIMIT 1', $inventoryDb));
            $linkedPcStmt->execute(['id' => $pcRowId]);
            $linkedPc = $linkedPcStmt->fetch(PDO::FETCH_ASSOC);
            if (!$linkedPc) {
                throw new RuntimeException('PC yang dipilih tidak ditemukan.');
            }
            $pcUser     = trim((string) ($linkedPc['user'] ?? ''));
            $pcUnitKerja = trim((string) ($linkedPc['unit_kerja'] ?? ''));
            $pageKey = 'user:' . mb_strtolower($pcUser);

            $focusItem = $this->insertOtherRow($pdo, $inventoryDb, $pageKey, $_POST, $_FILES, $pcRowId, $pcUser, $pcUnitKerja);
            $this->model->logInventoryUpdate($pdo, $divisionCode, $pageKey, $inventoryDb, 'create', 'perangkat_lain', (string) ($_POST['other_id_inventaris'] ?? ''));
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Perangkat baru berhasil diinput ke database terkait. Tabel detail inventaris otomatis bertambah.'];
            $lastPage = $this->resolveUserPageNumberByPageKey($pdo, $divisionCode, $pageKey, $this->resolveLastUserPageNumber($pdo, $divisionCode));
            header('Location: ' . $this->buildDetailUrl([
                'division_code'    => $divisionCode,
                'display_division' => (string) ($division['division_label'] ?? ''),
                'user_page'        => $lastPage,
                'user'             => $pcUser,
                'focus_item'       => 'other:' . $focusItem,
                'after_add_inventory' => '1',
            ]));
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Input inventaris gagal: ' . $e->getMessage()];
        }

        header('Location: index.php?page=' . urlencode($page));
        exit;
    }

    private function resolveUserPageNumberByPageKey(PDO $pdo, string $divisionCode, string $pageKey, int $fallbackPage = 1): int
    {
        $pageKey = trim($pageKey);
        if ($pageKey === '') {
            return max(1, $fallbackPage);
        }

        $division = $this->model->getDivisionByCode($pdo, trim($divisionCode));
        $inventoryDb = (string) ($division['inventory_db_name'] ?? '');
        if (!$division || !$this->isSafeIdentifier($inventoryDb)) {
            return max(1, $fallbackPage);
        }

        try {
            $sql = sprintf('SELECT * FROM `%s`.pc ORDER BY CASE WHEN `inventory_order` IS NULL THEN 0 ELSE 1 END ASC, `inventory_order` ASC, COALESCE(NULLIF(`user`, ""), NULLIF(`computer_name`, ""), NULLIF(`id_inventaris`, ""), "") ASC, COALESCE(NULLIF(`computer_name`, ""), "") ASC, COALESCE(NULLIF(`id_inventaris`, ""), "") ASC', $inventoryDb);
            $stmt = $pdo->query($sql);
            $seen = [];
            $pageNo = 0;
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                $rowPageKey = $this->buildPageKeyFromRow((array) $row);
                if ($rowPageKey === '' || isset($seen[$rowPageKey])) {
                    continue;
                }
                $seen[$rowPageKey] = true;
                $pageNo++;
                if ($rowPageKey === $pageKey) {
                    return max(1, $pageNo);
                }
            }
        } catch (Throwable $e) {
        }

        return max(1, $fallbackPage);
    }

    private function resolveLastUserPageNumber(PDO $pdo, string $divisionCode): int
    {
        $division = $this->model->getDivisionByCode($pdo, trim($divisionCode));
        $inventoryDb = (string) ($division['inventory_db_name'] ?? '');
        if (!$division || !$this->isSafeIdentifier($inventoryDb)) {
            return 1;
        }
        try {
            $stmt = $pdo->query(sprintf('SELECT COUNT(*) AS total FROM `%s`.pc', $inventoryDb));
            $row = $stmt ? $stmt->fetch() : null;
            return max(1, (int) ($row['total'] ?? 1));
        } catch (Throwable $e) {
            return 1;
        }
    }

    private function buildInventoryFormData(): array
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return ['divisions' => [], 'users' => []];
        }
        return $this->model->getInventoryFormOptions($pdo);
    }

    /**
     * AJAX: GET ?ajax=get_pc_list&division_code=XXX
     * Return JSON array of PC rows untuk dropdown Mode A.
     */
    private function jsonGetPcList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $divisionCode = trim((string) ($_GET['division_code'] ?? ''));
        if ($divisionCode === '') {
            echo json_encode([]);
            exit;
        }
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            echo json_encode([]);
            exit;
        }
        try {
            $division = $this->fetchInventoryDivisionByCode($pdo, $divisionCode);
            $inventoryDb = trim((string) ($division['inventory_db_name'] ?? ''));
            if (!$division || !$this->isSafeIdentifier($inventoryDb)) {
                echo json_encode([]);
                exit;
            }
            $this->ensurePcSchema($pdo, $inventoryDb);
            $sql = sprintf(
                'SELECT `id`, `user`, `computer_name`, `unit_kerja` FROM `%s`.pc ORDER BY COALESCE(NULLIF(`user`,""), NULLIF(`computer_name`,""), "") ASC',
                $inventoryDb
            );
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $result = [];
            foreach ($rows as $row) {
                $user = trim((string) ($row['user'] ?? ''));
                $computerName = trim((string) ($row['computer_name'] ?? ''));
                $label = $user !== '' && $computerName !== ''
                    ? $user . ' — ' . $computerName
                    : ($user !== '' ? $user : ($computerName !== '' ? $computerName : 'PC #' . $row['id']));
                $result[] = [
                    'id'            => (int) $row['id'],
                    'user'          => $user,
                    'computer_name' => $computerName,
                    'unit_kerja'    => trim((string) ($row['unit_kerja'] ?? '')),
                    'label'         => $label,
                ];
            }
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            echo json_encode([]);
        }
        exit;
    }

    /**
     * AJAX: POST ?page=routine-monitoring&ajax=save_routine_monitoring_cell
     * Save a single routine monitoring cell asynchronously.
     */
    private function jsonSaveRoutineMonitoringCell(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            exit;
        }

        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
            exit;
        }

        try {
            $itemId = max(0, (int) ($_POST['item_id'] ?? 0));
            $monitorDate = trim((string) ($_POST['monitor_date'] ?? ''));
            $statusRaw = trim((string) ($_POST['condition_status'] ?? ''));
            $keterangan = isset($_POST['keterangan']) ? trim((string) $_POST['keterangan']) : null;

            if ($itemId <= 0 || $monitorDate === '') {
                throw new RuntimeException('Parameter tidak valid.');
            }

            // Fetch the item details
            $itemStmt = $pdo->prepare('SELECT item_name FROM routine_monitoring_items WHERE id = :id LIMIT 1');
            $itemStmt->execute(['id' => $itemId]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found in routine_monitoring_items, check if it's a CCTV in cctv_inventaris
            if (!$item && $itemId >= 10000) {
                $actualId = $itemId - 10000;
                $cctvStmt = $pdo->prepare('SELECT nama_cctv FROM cctv_inventaris WHERE id = :id LIMIT 1');
                $cctvStmt->execute(['id' => $actualId]);
                $cctv = $cctvStmt->fetch(PDO::FETCH_ASSOC);
                if ($cctv) {
                    $item = ['item_name' => $cctv['nama_cctv']];
                }
            }

            if (!$item) {
                throw new RuntimeException('Item checking tidak ditemukan.');
            }

            $itemName = trim((string) ($item['item_name'] ?? ''));
            $auth = $_SESSION['auth'] ?? [];

            if ($statusRaw === '') {
                // Delete if empty
                $stmt = $pdo->prepare('DELETE FROM routine_monitoring WHERE period_type = :period_type AND period_key = :period_key AND item_id = :item_id');
                $stmt->execute([
                    'period_type' => 'daily',
                    'period_key'  => $monitorDate,
                    'item_id'     => $itemId,
                ]);
            } else {
                // Upsert
                $stmt = $pdo->prepare('INSERT INTO routine_monitoring 
                    (period_type, period_key, monitor_date, item_id, item_name, condition_status, keterangan, checked_by_user_id, checked_by_name) 
                    VALUES (:period_type, :period_key, :monitor_date, :item_id, :item_name, :condition_status, :keterangan, :checked_by_user_id, :checked_by_name) 
                    ON DUPLICATE KEY UPDATE 
                        item_name = VALUES(item_name), 
                        condition_status = VALUES(condition_status), 
                        keterangan = VALUES(keterangan), 
                        checked_by_user_id = VALUES(checked_by_user_id), 
                        checked_by_name = VALUES(checked_by_name), 
                        updated_at = CURRENT_TIMESTAMP');
                
                $stmt->execute([
                    'period_type'       => 'daily',
                    'period_key'        => $monitorDate,
                    'monitor_date'      => $monitorDate,
                    'item_id'           => $itemId,
                    'item_name'         => $itemName,
                    'condition_status'  => $this->normalizeRoutineStatus($statusRaw),
                    'keterangan'        => $keterangan !== '' ? $keterangan : null,
                    'checked_by_user_id' => (int) ($auth['id'] ?? 0) ?: null,
                    'checked_by_name'   => (string) ($auth['nama_lengkap'] ?? $auth['username'] ?? ''),
                ]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Perubahan berhasil disimpan otomatis.']);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }


    private function handleLogBarangAction(array $filters): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return;
        }

        $this->ensureLogBarangSchema($pdo);

        $action = (string) ($_GET['action'] ?? '');
        if ($action === 'export') {
            $this->streamLogBarangExport($pdo, $filters, (string) ($_GET['format'] ?? 'pdf'));
        }
        if ($action === 'download_po') {
            $this->streamLogBarangPdfFile((string) ($_GET['file'] ?? ''));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $postAction = (string) ($_POST['action'] ?? '');
        if (!in_array($postAction, ['save_log_barang', 'edit_log_barang', 'delete_log_barang', 'complete_transfer'], true)) {
            return;
        }

        if (AuthController::role() === 'user' && in_array($postAction, ['edit_log_barang', 'delete_log_barang', 'complete_transfer'], true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Akses ditolak. Sebagai User, Anda hanya diperbolehkan menambah log dan tidak bisa mengedit, menghapus, atau menyerahkan barang.'];
            header('Location: ' . $this->buildLogBarangUrl($filters));
            exit;
        }

        try {
            if ($postAction === 'save_log_barang') {
                $this->insertLogBarang($pdo, $_POST, $_FILES);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Log barang baru berhasil ditambahkan.'];
            } elseif ($postAction === 'edit_log_barang') {
                $this->updateLogBarang($pdo, $_POST, $_FILES);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Log barang berhasil diperbarui.'];
            } elseif ($postAction === 'complete_transfer') {
                $this->completeTransfer($pdo, $_POST);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Barang berhasil diserahkan ke Divisi Peminta.'];
            } else {
                $this->deleteLogBarang($pdo, (int) ($_POST['id'] ?? 0));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Log barang berhasil dihapus.'];
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Proses log barang gagal: ' . $e->getMessage()];
        }

        header('Location: ' . $this->buildLogBarangUrl($filters));
        exit;
    }

    private function ensureLogBarangSchema(PDO $pdo): void
    {
        // Table structure is now fully created and managed by migration.
    }

    private function insertLogBarang(PDO $pdo, array $payload, array $files): void
    {
        $satuan = trim((string) ($payload['satuan'] ?? ''));
        if ($satuan === '') {
            throw new RuntimeException('Satuan wajib diisi.');
        }
        $currUserId = (int) ($_SESSION['auth']['id'] ?? $_SESSION['auth']['user_id'] ?? 1);
        $picId = (int) ($payload['pic_id'] ?? $currUserId);
        if ($picId <= 0) {
            throw new RuntimeException('PIC wajib dipilih.');
        }
        
        $hargaInput = trim((string) ($payload['harga'] ?? ''));
        if ($hargaInput === '') {
            throw new RuntimeException('Harga wajib diisi.');
        }
        $harga = (double) $hargaInput;
        if ($harga < 0) {
            throw new RuntimeException('Harga tidak boleh negatif.');
        }

        $stmt = $pdo->prepare('INSERT INTO log_barang (log_no, tanggal_masuk, waktu_input_masuk, tanggal_keluar, waktu_input_keluar, nama_barang, qty, satuan, harga, pic_id, no_po, dokumen_po, divisi_pengelola, divisi_peminta, keterangan, dibuat_oleh_user_id, created_at) VALUES (:log_no, :tanggal_masuk, :waktu_input_masuk, NULL, NULL, :nama_barang, :qty, :satuan, :harga, :pic_id, :no_po, :pdf, :divisi_pengelola, :divisi_peminta, :keterangan, :user_id, NOW())');
        $stmt->execute([
            'log_no' => $this->generateLogNo($pdo),
            'tanggal_masuk' => $this->requiredDate((string) ($payload['tanggal'] ?? '')),
            'waktu_input_masuk' => date('H:i:s'),
            'nama_barang' => $this->requiredText((string) ($payload['nama_barang'] ?? ''), 'Nama barang wajib diisi.'),
            'qty' => max(1, (int) ($payload['qty'] ?? 1)),
            'satuan' => $satuan,
            'harga' => $harga,
            'pic_id' => $picId,
            'no_po' => $this->clean((string) ($payload['no_po'] ?? '')),
            'pdf' => $this->handleLogPdfUpload($files, 'surat_pemesanan_pdf'),
            'divisi_pengelola' => $this->clean((string) ($payload['divisi'] ?? '')),
            'divisi_peminta' => $this->clean((string) ($payload['divisi_terkait'] ?? '')),
            'keterangan' => $this->clean((string) ($payload['keterangan'] ?? '')),
            'user_id' => $currUserId ?: null,
        ]);
    }

    private function completeTransfer(PDO $pdo, array $payload): void
    {
        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID log barang tidak valid.');
        }
        $tanggalKeluar = $this->requiredDate((string) ($payload['tanggal_keluar'] ?? ''));
        
        $stmt = $pdo->prepare('UPDATE log_barang SET 
            tanggal_keluar = :tanggal_keluar, 
            waktu_input_keluar = :waktu_input_keluar 
            WHERE id = :id AND (tanggal_keluar IS NULL OR tanggal_keluar = \'0000-00-00\')');
        $stmt->execute([
            'id' => $id,
            'tanggal_keluar' => $tanggalKeluar,
            'waktu_input_keluar' => date('H:i:s'),
        ]);
    }

    private function updateLogBarang(PDO $pdo, array $payload, array $files): void
    {
        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID log barang tidak valid.');
        }
        $stmt = $pdo->prepare('SELECT * FROM log_barang WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetch();
        if (!$current) {
            throw new RuntimeException('Data log barang tidak ditemukan.');
        }
        $pdfPath = trim((string) ($current['dokumen_po'] ?? ''));
        if (!empty($payload['remove_pdf']) && $pdfPath !== '') {
            $this->deleteLogPdfFile($pdfPath);
            $pdfPath = '';
        }
        $newPdfPath = $this->handleLogPdfUpload($files, 'surat_pemesanan_pdf', false);
        if ($newPdfPath !== null) {
            if ($pdfPath !== '') {
                $this->deleteLogPdfFile($pdfPath);
            }
            $pdfPath = $newPdfPath;
        }

        $satuan = trim((string) ($payload['satuan'] ?? ''));
        if ($satuan === '') {
            throw new RuntimeException('Satuan wajib diisi.');
        }
        $currUserId = (int) ($_SESSION['auth']['id'] ?? $_SESSION['auth']['user_id'] ?? 1);
        $picId = (int) ($payload['pic_id'] ?? $currUserId);
        if ($picId <= 0) {
            throw new RuntimeException('PIC wajib dipilih.');
        }
        
        $hargaInput = trim((string) ($payload['harga'] ?? ''));
        if ($hargaInput === '') {
            throw new RuntimeException('Harga wajib diisi.');
        }
        $harga = (double) $hargaInput;
        if ($harga < 0) {
            throw new RuntimeException('Harga tidak boleh negatif.');
        }

        $tanggalMasuk = $this->requiredDate((string) ($payload['tanggal_masuk'] ?? $payload['tanggal'] ?? ''));
        $tanggalKeluarInput = trim((string) ($payload['tanggal_keluar'] ?? ''));
        $tanggalKeluar = ($tanggalKeluarInput !== '' && $tanggalKeluarInput !== '0000-00-00') ? $tanggalKeluarInput : null;
        
        $waktuMasuk = $current['waktu_input_masuk'] ?: date('H:i:s');
        $waktuKeluar = $current['waktu_input_keluar'];
        if ($tanggalKeluar !== null) {
            if (empty($waktuKeluar) || $waktuKeluar === '00:00:00') {
                $waktuKeluar = date('H:i:s');
            }
        } else {
            $waktuKeluar = null;
        }

        $update = $pdo->prepare('UPDATE log_barang SET 
            tanggal_masuk = :tanggal_masuk, 
            waktu_input_masuk = :waktu_input_masuk,
            tanggal_keluar = :tanggal_keluar, 
            waktu_input_keluar = :waktu_input_keluar,
            nama_barang = :nama_barang, 
            qty = :qty, 
            satuan = :satuan, 
            harga = :harga, 
            pic_id = :pic_id, 
            no_po = :no_po, 
            dokumen_po = :pdf, 
            divisi_pengelola = :divisi_pengelola, 
            divisi_peminta = :divisi_peminta, 
            keterangan = :keterangan 
            WHERE id = :id');
        $update->execute([
            'id' => $id,
            'tanggal_masuk' => $tanggalMasuk,
            'waktu_input_masuk' => $waktuMasuk,
            'tanggal_keluar' => $tanggalKeluar,
            'waktu_input_keluar' => $waktuKeluar,
            'nama_barang' => $this->requiredText((string) ($payload['nama_barang'] ?? ''), 'Nama barang wajib diisi.'),
            'qty' => max(1, (int) ($payload['qty'] ?? 1)),
            'satuan' => $satuan,
            'harga' => $harga,
            'pic_id' => $picId,
            'no_po' => $this->clean((string) ($payload['no_po'] ?? '')),
            'pdf' => $pdfPath !== '' ? $pdfPath : null,
            'divisi_pengelola' => $this->clean((string) ($payload['divisi'] ?? '')),
            'divisi_peminta' => $this->clean((string) ($payload['divisi_terkait'] ?? '')),
            'keterangan' => $this->clean((string) ($payload['keterangan'] ?? '')),
        ]);
    }

    private function deleteLogBarang(PDO $pdo, int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('ID log barang tidak valid.');
        }
        $stmt = $pdo->prepare('SELECT dokumen_po FROM log_barang WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Data log barang tidak ditemukan.');
        }
        $delete = $pdo->prepare('DELETE FROM log_barang WHERE id = :id');
        $delete->execute(['id' => $id]);
        $this->deleteLogPdfFile((string) ($row['dokumen_po'] ?? ''));
    }

    private function handleLogPdfUpload(array $files, string $field, bool $required = true): ?string
    {
        $file = $files[$field] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                return null;
            }
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file PDF gagal.');
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $mime = strtolower((string) @mime_content_type((string) ($file['tmp_name'] ?? '')));
        if ($ext !== 'pdf' && $mime !== 'application/pdf') {
            throw new RuntimeException('File surat pemesanan harus PDF.');
        }
        $dir = dirname(__DIR__, 2) . '/public/uploads/log-barang';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder upload PDF tidak bisa dibuat.');
        }
        $targetName = 'po_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $target = $dir . '/' . $targetName;
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            if (!@copy((string) $file['tmp_name'], $target)) {
                throw new RuntimeException('File PDF gagal disimpan.');
            }
        }
        return 'uploads/log-barang/' . $targetName;
    }

    private function deleteLogPdfFile(string $relativePath): void
    {
        $relativePath = ltrim(trim($relativePath), '/');
        if ($relativePath === '') {
            return;
        }
        $fullPath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function generateLogNo(PDO $pdo): string
    {
        $prefix = 'LOG-' . date('Ymd') . '-';
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM log_barang WHERE log_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        $next = ((int) ($stmt->fetch()['total'] ?? 0)) + 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeLogStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, ['MASUK', 'KELUAR'], true)) {
            throw new RuntimeException('Status log barang tidak valid.');
        }
        return $status;
    }

    private function requiredText(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($message);
        }
        return $value;
    }

    private function requiredDate(string $value): string
    {
        $value = trim($value);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            throw new RuntimeException('Tanggal log barang tidak valid.');
        }
        return $value;
    }

    private function buildLogBarangUrl(array $filters = [], array $extra = []): string
    {
        $query = [
            'page' => 'log-barang',
            'log_year' => (string) ($filters['log_year'] ?? 0),
            'log_month' => (string) ($filters['log_month'] ?? 0),
            'log_date' => (string) ($filters['log_date'] ?? ''),
            'log_status' => (string) ($filters['log_status'] ?? ''),
            'log_sort' => (string) ($filters['log_sort'] ?? 'newest'),
            'log_search' => (string) ($filters['log_search'] ?? ''),
        ];
        foreach ($extra as $key => $value) {
            $query[$key] = $value;
        }
        return 'index.php?' . http_build_query($query);
    }

    private function streamLogBarangPdfFile(string $relativePath): void
    {
        $relativePath = ltrim(trim($relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            http_response_code(404);
            exit('File PDF tidak ditemukan.');
        }
        $fullPath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('File PDF tidak ditemukan.');
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    private function streamLogBarangExport(PDO $pdo, array $filters, string $format): void
    {
        $rows = $this->model->exportLogRows($pdo, $filters);
        $title = $this->buildLogBarangExportTitle($filters);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($title));
        $base = trim((string) $base, '_');
        if ($base === '') {
            $base = 'log_barang_export';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (strtolower($format) === 'xlsx') {
            $xlsx = $this->buildLogBarangExcelXlsx($title, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
            header('Content-Length: ' . strlen($xlsx));
            echo $xlsx;
            exit;
        }

        $pdf = $this->buildLogBarangPdf($title, $rows, $filters);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildLogBarangExportTitle(array $filters): string
    {
        $year = (int) ($filters['log_year'] ?? date('Y'));
        $month = (int) ($filters['log_month'] ?? 0);
        $date = trim((string) ($filters['log_date'] ?? ''));
        $status = strtoupper(trim((string) ($filters['log_status'] ?? '')));
        $search = trim((string) ($filters['log_search'] ?? ''));

        $parts = ['Log Barang'];

        if ($date !== '') {
            try {
                $dt = new DateTimeImmutable($date);
                $parts[] = $dt->format('d-m-Y');
            } catch (Throwable $e) {
                $parts[] = $date;
            }
        } elseif ($month > 0) {
            $parts[] = $this->monthName($month) . ' ' . $year;
        } else {
            $parts[] = 'Semua Bulan ' . $year;
        }

        if ($status !== '') {
            $parts[] = $status;
        }
        if ($search !== '') {
            $parts[] = 'Filter';
        }

        return implode(' - ', $parts);
    }

    private function monthName(int $month): string
    {
        static $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$month] ?? (string) $month;
    }

    private function buildLogBarangExcelXlsx(string $title, array $rows): string
    {
        $configFile = __DIR__ . '/../config/config.php';
        if (is_file($configFile)) {
            require_once $configFile;
        }
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/Sistem_Inventory/';
        $baseUrl = rtrim($baseUrl, '/\\') . '/';

        $sheetRows = [
            [$title],
            ['Diexport: ' . date('d-m-Y H:i:s')],
            [],
            ['No', 'Tanggal Masuk', 'Tanggal Keluar', 'Nama Barang', 'Qty', 'Satuan', 'Harga', 'Status', 'Divisi Pengelola', 'Divisi Peminta', 'No. PO', 'Dokumen', 'PIC', 'Log No', 'Keterangan'],
        ];

        $number = 1;
        foreach ($rows as $row) {
            $priceVal = (double) ($row['harga'] ?? 0);
            $priceStr = $priceVal > 0 ? 'Rp ' . number_format($priceVal, 0, ',', '.') : '-';

            $divisionStr = trim((string) ($row['division'] ?? ''));
            if ($divisionStr === '' || $divisionStr === '-') {
                $divisionStr = '-';
            }

            $divTerkaitStr = trim((string) ($row['divisi_terkait'] ?? ''));
            if ($divTerkaitStr === '' || $divTerkaitStr === '-') {
                $divTerkaitStr = '-';
            }

            $noPo = trim((string) ($row['no_po'] ?? ''));
            if ($noPo === '' || $noPo === '-') {
                $noPo = '-';
            }

            $docStr = '-';
            $pdfPath = trim((string) ($row['pdf'] ?? ''));
            if ($pdfPath !== '') {
                $poUrl = $baseUrl . 'index.php?page=log-barang&action=download_po&file=' . rawurlencode($pdfPath);
                $docStr = '=HYPERLINK("' . $poUrl . '", "Lihat Dokumen")';
                if ($noPo !== '-') {
                    $noPo = '=HYPERLINK("' . $poUrl . '", "' . $noPo . '")';
                }
            }

            $keteranganStr = trim((string) ($row['keterangan'] ?? ''));
            if ($keteranganStr === '' || $keteranganStr === '-') {
                $keteranganStr = '-';
            }

            $satuanStr = trim((string) ($row['satuan'] ?? ''));
            if ($satuanStr === '' || $satuanStr === '-') {
                $satuanStr = '-';
            }

            $picStr = trim((string) ($row['pic_nama'] ?? ''));
            if ($picStr === '' || $picStr === '-') {
                $picStr = '-';
            }

            $sheetRows[] = [
                (string) $number++,
                (string) ($row['date'] ?? '-'),
                (string) ($row['date_keluar'] ?? '-'),
                (string) ($row['item'] ?? '-'),
                (string) ($row['qty'] ?? '0'),
                $satuanStr,
                $priceStr,
                (string) ($row['status'] ?? '-'),
                $divisionStr,
                $divTerkaitStr,
                $noPo,
                $docStr,
                $picStr,
                (string) ($row['log_no'] ?? '-'),
                $keteranganStr,
            ];
        }

        $colWidths = [];
        $minWidths = [6, 14, 14, 25, 8, 10, 16, 12, 18, 18, 16, 16, 18, 20, 25];
        for ($i = 0; $i < 15; $i++) {
            $colWidths[$i] = $minWidths[$i];
        }

        foreach ($sheetRows as $rIndex => $row) {
            if ($rIndex < 3) continue;
            foreach (array_values($row) as $cIndex => $val) {
                if (str_starts_with((string)$val, '=HYPERLINK')) {
                    if (preg_match('/",\s*"([^"]+)"/', (string)$val, $matches)) {
                        $len = strlen($matches[1]);
                    } else {
                        $len = strlen((string)$val);
                    }
                } else {
                    $len = strlen((string)$val);
                }
                if ($len > $colWidths[$cIndex]) {
                    $colWidths[$cIndex] = min(50, $len + 4);
                }
            }
        }

        $xmlRows = [];
        foreach ($sheetRows as $rIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $cIndex => $value) {
                $ref = $this->excelColumnName($cIndex + 1) . ($rIndex + 1);

                if ($rIndex === 0) {
                    $style = ' s="6"';
                    $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                } elseif ($rIndex === 1) {
                    $style = ' s="7"';
                    $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                } elseif ($rIndex === 2) {
                    continue;
                } elseif ($rIndex === 3) {
                    $style = ' s="1"';
                    $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                } else {
                    if ($cIndex === 0 || $cIndex === 1 || $cIndex === 2 || $cIndex === 13) {
                        $style = ' s="8"';
                    } elseif ($cIndex === 4) {
                        $style = ' s="4"';
                    } elseif ($cIndex === 6) {
                        $style = ' s="5"';
                    } elseif ($cIndex === 7) {
                        $statusVal = strtoupper(trim((string) $value));
                        if ($statusVal === 'MASUK') {
                            $style = ' s="2"';
                        } else {
                            $style = ' s="0"';
                        }
                    } else {
                        $style = ' s="0"';
                    }

                    if ($value === null || $value === '') {
                        continue;
                    }

                    if (str_starts_with((string)$value, '=HYPERLINK')) {
                        $formula = substr((string)$value, 1);
                        $label = $value;
                        if (preg_match('/",\s*"([^"]+)"/', $formula, $matches)) {
                            $label = $matches[1];
                        }
                        $cells[] = '<c r="' . $ref . '" t="str"' . $style . '><f>' . $this->xml($formula) . '</f><v>' . $this->xml($label) . '</v></c>';
                    } else {
                        $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
                    }
                }
            }
            if ($rIndex === 2) {
                $xmlRows[] = '<row r="' . ($rIndex + 1) . '"/>';
            } else {
                $xmlRows[] = '<row r="' . ($rIndex + 1) . '">' . implode('', $cells) . '</row>';
            }
        }

        $cols = '';
        for ($i = 0; $i < count($colWidths); $i++) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $colWidths[$i] . '" customWidth="1"/>';
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:O' . max(4, count($sheetRows)) . '"/>'
            . '<sheetViews>'
            . '<sheetView workbookViewId="0">'
            . '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView>'
            . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '<mergeCells count="2">'
            . '<mergeCell ref="A1:O1"/>'
            . '<mergeCell ref="A2:O2"/>'
            . '</mergeCells>'
            . '</worksheet>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="4">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
        <font><b/><sz val="14"/><name val="Calibri"/></font>
    </fonts>
    <fills count="5">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFFD965"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF00B050"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFF0000"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFD3D3D3"/></left>
            <right style="thin"><color rgb="FFD3D3D3"/></right>
            <top style="thin"><color rgb="FFD3D3D3"/></top>
            <bottom style="thin"><color rgb="FFD3D3D3"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="9">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="2" fillId="4" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <xf numFmtId="0" fontId="3" fillId="0" borderId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->xml($title) . '</dc:title><dc:creator>Admin</dc:creator><cp:lastModifiedBy>Admin</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified></cp:coreProperties>',
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Microsoft Excel</Application></Properties>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Log Barang" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $stylesXml,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        return $this->buildZipBinary($files);
    }

    private function buildLogBarangPdf(string $title, array $rows, array $filters = []): string
    {
        $columns = [
            ['title' => 'No', 'key' => 'no', 'width' => 20, 'align' => 'C'],
            ['title' => 'Tanggal Masuk', 'key' => 'date', 'width' => 42, 'align' => 'C'],
            ['title' => 'Tanggal Keluar', 'key' => 'time', 'width' => 42, 'align' => 'C'],
            ['title' => 'Nama Barang', 'key' => 'item', 'width' => 85, 'align' => 'L'],
            ['title' => 'Qty', 'key' => 'qty', 'width' => 20, 'align' => 'C'],
            ['title' => 'Satuan', 'key' => 'satuan', 'width' => 32, 'align' => 'L'],
            ['title' => 'Harga', 'key' => 'harga', 'width' => 60, 'align' => 'R'],
            ['title' => 'Status', 'key' => 'status', 'width' => 38, 'align' => 'C'],
            ['title' => 'Divisi Pengelola', 'key' => 'division', 'width' => 55, 'align' => 'L'],
            ['title' => 'Divisi Peminta', 'key' => 'divisi_terkait', 'width' => 55, 'align' => 'L'],
            ['title' => 'No. PO', 'key' => 'no_po', 'width' => 55, 'align' => 'L'],
            ['title' => 'Dokumen', 'key' => 'doc', 'width' => 38, 'align' => 'C'],
            ['title' => 'PIC', 'key' => 'pic', 'width' => 62, 'align' => 'L'],
            ['title' => 'Log No', 'key' => 'log_no', 'width' => 68, 'align' => 'C'],
            ['title' => 'Keterangan', 'key' => 'keterangan', 'width' => 104, 'align' => 'L'],
        ];

        $charMap = [
            'no' => 4,
            'date' => 10,
            'time' => 10,
            'item' => 18,
            'qty' => 4,
            'satuan' => 7,
            'harga' => 12,
            'status' => 8,
            'division' => 11,
            'divisi_terkait' => 11,
            'no_po' => 11,
            'doc' => 8,
            'pic' => 12,
            'log_no' => 14,
            'keterangan' => 20,
        ];

        $normalizeText = function (string $text): string {
            $text = trim(preg_replace('/\s+/', ' ', str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text)) ?? $text);
            if ($text === '') {
                return '-';
            }
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
                if ($converted !== false && $converted !== '') {
                    $text = $converted;
                }
            }
            $text = preg_replace('/[^ -~€-ÿ]/', '', $text) ?? $text;
            return $text === '' ? '-' : $text;
        };

        $escape = function (string $text) use ($normalizeText): string {
            $text = $normalizeText($text);
            return str_replace(["\\", "(", ")"], ["\\\\", "\(", "\)"], $text);
        };

        $textLen = function (string $text) use ($normalizeText): int {
            $text = $normalizeText($text);
            return strlen($text);
        };

        $textSub = function (string $text, int $start, ?int $length = null) use ($normalizeText): string {
            $text = $normalizeText($text);
            return $length === null ? substr($text, $start) : substr($text, $start, $length);
        };

        $wrapText = function (string $text, int $maxChars) use ($normalizeText, $textLen, $textSub): array {
            $text = $normalizeText($text);
            if ($textLen($text) <= $maxChars) {
                return [$text];
            }
            $words = preg_split('/\s+/', $text) ?: [$text];
            $lines = [];
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if ($textLen($candidate) <= $maxChars) {
                    $line = $candidate;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                while ($textLen($word) > $maxChars) {
                    $lines[] = $textSub($word, 0, $maxChars);
                    $word = $textSub($word, $maxChars);
                }
                $line = $word;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            return $lines ?: ['-'];
        };

        $wrappedHeaders = [];
        $maxHeaderLines = 1;
        foreach ($columns as $column) {
            $key = $column['key'];
            $wrappedHeaders[$key] = $wrapText($column['title'], $charMap[$key] ?? 12);
            $maxHeaderLines = max($maxHeaderLines, count($wrappedHeaders[$key]));
        }

        $year = (int) ($filters['log_year'] ?? date('Y'));
        $month = (int) ($filters['log_month'] ?? 0);
        $dateVal = trim((string) ($filters['log_date'] ?? ''));
        if ($dateVal !== '') {
            $periode = $dateVal;
        } elseif ($month > 0) {
            $periode = $this->monthName($month) . ' ' . $year;
        } else {
            $periode = 'Semua Bulan ' . $year;
        }
        $periodeText = 'Periode: ' . $periode;
        if (!empty($filters['log_status'])) {
            $periodeText .= ' | Status: ' . $filters['log_status'];
        }
        if (!empty($filters['log_division'])) {
            $periodeText .= ' | Divisi: ' . $filters['log_division'];
        }

        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 33;
        $top = $pageHeight - $margin;
        $bottomMargin = 40;
        $tableX = $margin;
        $lineHeight = 9;
        $cellPaddingX = 3;
        $cellPaddingY = 3;
        $rowGap = 2;

        $blue = '0.18 0.41 0.67';
        $lightBlue = '0.90 0.95 0.99';
        $textColor = '0.12 0.18 0.25';
        $borderColor = '0.75 0.81 0.89';
        $altFill = '0.96 0.97 0.99';

        $objects = [];
        $objects[1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $streams = [];
        $current = [];
        $y = $top;

        $addText = function (float $x, float $yPos, string $text, int $fontId = 1, float $fontSize = 7.0, string $color = '0 0 0') use (&$current, $escape) {
            $current[] = 'BT';
            $current[] = sprintf('/F%d %.1f Tf', $fontId, $fontSize);
            $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yPos);
            $current[] = '(' . $escape($text) . ') Tj';
            $current[] = 'ET';
        };

        $addRect = function (float $x, float $yPos, float $w, float $h, ?string $fill = null, ?string $stroke = null, float $lineWidth = 0.5) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) {
                $current[] = $fill . ' rg';
            }
            if ($stroke !== null) {
                $current[] = $stroke . ' RG';
            }
            $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yPos, $w, $h, ($fill !== null && $stroke !== null) ? 'B' : ($fill !== null ? 'f' : 'S'));
        };

        $startPage = function () use (&$current, &$y, $top, $title, $margin, $pageWidth, $addRect, $addText, $blue, $lightBlue, $textColor, $borderColor, $periodeText) {
            $current = [];
            $y = $top;
            
            // Render Header Banner
            $addRect($margin, $y - 44, $pageWidth - ($margin * 2), 44, $blue, null, 0);
            $addText($margin + 12, $y - 18, strtoupper($title), 2, 14, '1 1 1');
            $addText($margin + 12, $y - 32, 'Sistem Inventory & Monitoring', 1, 8.5, '1 1 1');
            $y -= 60;
 
            // Render Info Bar
            $infoText = 'Diexport: ' . date('d-m-Y H:i:s') . '   |   ' . $periodeText;
            $addRect($margin, $y - 18, $pageWidth - ($margin * 2), 18, $lightBlue, $borderColor, 0.8);
            $addText($margin + 10, $y - 12, $infoText, 1, 8, $textColor);
            $y -= 28;
        };

        $drawHeader = function () use (&$y, $columns, $tableX, $wrappedHeaders, $maxHeaderLines, $addRect, $addText, $blue, $borderColor) {
            $headerHeight = max(18, ($maxHeaderLines * 9) + 6);
            $rowY = $y - $headerHeight;
            $x = $tableX;
            foreach ($columns as $column) {
                $key = $column['key'];
                $addRect($x, $rowY, $column['width'], $headerHeight, $blue, $borderColor, 0.7);
                
                $lines = $wrappedHeaders[$key];
                $lineCount = count($lines);
                $lineY = $rowY + ($headerHeight / 2) + (($lineCount - 1) * 4.5) - 3;
                foreach ($lines as $line) {
                    $lineWidth = strlen($line) * 3.5;
                    $textX = $x + ($column['width'] / 2) - ($lineWidth / 2);
                    $addText($textX, $lineY, $line, 2, 7.0, '1 1 1');
                    $lineY -= 9;
                }
                $x += $column['width'];
            }
            $y = $rowY - 2;
        };

        $finishPage = function () use (&$streams, &$current) {
            if (!empty($current)) {
                $streams[] = implode("\n", $current);
            }
            $current = [];
        };

        $startPage();
        $drawHeader();

        $number = 1;
        foreach ($rows as $index => $row) {
            $priceVal = (double) ($row['harga'] ?? 0);
            $priceStr = $priceVal > 0 ? 'Rp ' . number_format($priceVal, 0, ',', '.') : '-';

            $noPoVal = trim((string) ($row['no_po'] ?? ''));
            if ($noPoVal === '' || $noPoVal === '-') {
                $noPoStr = '-';
            } else {
                $noPoStr = $noPoVal;
            }

            $docStr = '-';
            $pdfPath = trim((string) ($row['pdf'] ?? ''));
            if ($pdfPath !== '') {
                $docStr = 'Ada';
            }

            $divisionStr = trim((string) ($row['division'] ?? ''));
            if ($divisionStr === '') $divisionStr = '-';
            $divTerkaitStr = trim((string) ($row['divisi_terkait'] ?? ''));
            if ($divTerkaitStr === '') $divTerkaitStr = '-';

            $keteranganStr = trim((string) ($row['keterangan'] ?? ''));
            if ($keteranganStr === '') $keteranganStr = '-';

            $satuanStr = trim((string) ($row['satuan'] ?? ''));
            if ($satuanStr === '') $satuanStr = '-';

            $picStr = trim((string) ($row['pic_nama'] ?? ''));
            if ($picStr === '') $picStr = '-';

            $cells = [
                'no' => (string) $number++,
                'date' => (string) ($row['date'] ?? '-'),
                'time' => (string) ($row['date_keluar'] ?? '-'),
                'item' => (string) ($row['item'] ?? '-'),
                'qty' => (string) ($row['qty'] ?? '0'),
                'satuan' => $satuanStr,
                'harga' => $priceStr,
                'status' => (string) ($row['status'] ?? '-'),
                'division' => $divisionStr,
                'divisi_terkait' => $divTerkaitStr,
                'no_po' => $noPoStr,
                'doc' => $docStr,
                'pic' => $picStr,
                'log_no' => (string) ($row['log_no'] ?? '-'),
                'keterangan' => $keteranganStr,
            ];

            $wrapped = [];
            $maxLines = 1;
            foreach ($columns as $column) {
                $key = $column['key'];
                $wrapped[$key] = $wrapText($cells[$key], $charMap[$key] ?? 12);
                $maxLines = max($maxLines, count($wrapped[$key]));
            }

            $rowHeight = max(16, ($maxLines * $lineHeight) + ($cellPaddingY * 2));
            if (($y - $rowHeight) < $bottomMargin) {
                $finishPage();
                $startPage();
                $drawHeader();
            }

            $rowY = $y - $rowHeight;
            $x = $tableX;
            foreach ($columns as $column) {
                $key = $column['key'];
                
                $cellFill = $index % 2 === 0 ? $altFill : '1 1 1';
                $fontColor = $textColor;
                $fontId = 1;
                
                if ($key === 'status') {
                    $statusVal = strtoupper(trim((string)$cells[$key]));
                    if ($statusVal === 'MASUK') {
                        $cellFill = '0.0 0.69 0.31';
                        $fontColor = '1 1 1';
                        $fontId = 2;
                    } elseif ($statusVal === 'SELESAI') {
                        $cellFill = '0.1 0.45 0.9';
                        $fontColor = '1 1 1';
                        $fontId = 2;
                    }
                }

                $addRect($x, $rowY, $column['width'], $rowHeight, $cellFill, $borderColor, 0.5);
                
                $lineY = $rowY + $rowHeight - 10;
                foreach ($wrapped[$key] as $line) {
                    $lineWidth = strlen($line) * 3.5;
                    $textX = $x + $cellPaddingX;
                    if (($column['align'] ?? 'L') === 'C') {
                        $textX = $x + max(2, ($column['width'] - $lineWidth) / 2);
                    } elseif (($column['align'] ?? 'L') === 'R') {
                        $textX = $x + $column['width'] - $lineWidth - $cellPaddingX;
                    }
                    $addText($textX, $lineY, $line, $fontId, 7.0, $fontColor);
                    $lineY -= $lineHeight;
                }
                $x += $column['width'];
            }
            $y = $rowY - $rowGap;
        }

        $totalText = 'Total Transaksi: ' . count($rows) . ' data';
        if (($y - 24) < $bottomMargin) {
            $finishPage();
            $startPage();
            $drawHeader();
        }
        $addRect($margin, $y - 18, $pageWidth - ($margin * 2), 18, $lightBlue, $borderColor, 0.8);
        $addText($margin + 10, $y - 12, $totalText, 2, 8, $textColor);
        $y -= 28;

        $finishPage();
        if (empty($streams)) {
            $startPage();
            $drawHeader();
            $finishPage();
        }

        $nextId = 3;
        $contentIds = [];
        $pageIds = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>
stream
" . $stream . "
endstream";
            $objects[$pageId] = "";
            $contentIds[] = $contentId;
            $pageIds[] = $pageId;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn ($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = "<< /Type /Pages /Count " . count($pageIds) . " /Kids [ {$kids} ] >>";
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $size = $catalogId + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function handleLaporanAction(array $filters): void
    {
        $action = trim((string) ($_GET['action'] ?? ''));
        if ($action !== 'report_export') {
            return;
        }
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            http_response_code(500);
            echo 'Koneksi database tidak tersedia.';
            exit;
        }
        $this->streamLaporanExport($pdo, (string) ($_GET['type'] ?? ''), (string) ($_GET['format'] ?? 'pdf'), $filters);
    }

    private function normalizeLaporanType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['inventory', 'complaint', 'log', 'routine', 'user'], true) ? $type : 'inventory';
    }

    private function laporanTitle(string $type): string
    {
        $normalized = $this->normalizeLaporanType($type);
        if ($normalized === 'complaint') {
            return 'Laporan Keluhan';
        }
        if ($normalized === 'log') {
            return 'Laporan Log Barang';
        }
        if ($normalized === 'routine') {
            return 'Laporan Routine Monitoring';
        }
        if ($normalized === 'user') {
            return 'Laporan User';
        }
        return 'Laporan Data Inventaris';
    }

    private function streamLaporanExport(PDO $pdo, string $type, string $format, array $filters = []): void
    {
        $type = $this->normalizeLaporanType($type);
        $format = strtolower(trim($format)) === 'xlsx' ? 'xlsx' : 'pdf';
        $title = $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type);
        $base = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($title)), '_') ?: 'laporan_export';
        while (ob_get_level() > 0) { ob_end_clean(); }

        if ($type === 'inventory') {
            $sheets = $this->fetchAllInventoryReportSheets($pdo, $filters);
            if ($format === 'xlsx') {
                if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                    if (file_exists(__DIR__ . '/../../peminjaman_laptop/vendor/autoload.php')) {
                        require_once __DIR__ . '/../../peminjaman_laptop/vendor/autoload.php';
                    } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                        require_once __DIR__ . '/../../vendor/autoload.php';
                    }
                }
                if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                    http_response_code(500);
                    echo 'PhpSpreadsheet library tidak tersedia.';
                    exit;
                }

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                
                // Create sheets
                $sheetSpjm = $spreadsheet->getActiveSheet();
                $sheetSpjm->setTitle('SPJM');

                $sheetSpmt = $spreadsheet->createSheet();
                $sheetSpmt->setTitle('SPMT');

                $sheetSubreg = $spreadsheet->createSheet();
                $sheetSubreg->setTitle('SUBREG');

                $sheetMap = [
                    'SPJM' => $sheetSpjm,
                    'SPMT' => $sheetSpmt,
                    'SUBREG' => $sheetSubreg,
                ];

                // Set default font to Arial 10 for all sheets
                $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

                // Helper to setup sheet headers
                $setupSheet = function ($sheet) {
                    $colWidths = [
                        'A' => 5,   // NO
                        'B' => 18,  // UNIT KERJA
                        'C' => 20,  // JENIS PERANGKAT
                        'D' => 20,  // COMPUTER NAME
                        'E' => 20,  // USER
                        'F' => 15,  // PROSESSOR
                        'G' => 15,  // RAM
                        'H' => 15,  // KAPASITAS HARDDISK
                        'I' => 15,  // IP ADDRESS
                        'J' => 18,  // SISTEM OPERASI
                        'K' => 12,  // LICENSED
                        'L' => 22,  // MICROSOFT OFFICE
                        'M' => 12,  // LICENSED
                        'N' => 25,  // INVENTARIS PC
                        'O' => 25,  // INVENTARIS MONITOR
                        'P' => 25,  // INVENTARIS KEYBOARD
                        'Q' => 25,  // INVENTARIS MOUSE
                        'R' => 30,  // INVENTARIS LAINNYA
                        'S' => 20,  // DOKUMENTASI
                    ];
                    foreach ($colWidths as $col => $width) {
                        $sheet->getColumnDimension($col)->setWidth($width);
                    }

                    // Header heights
                    $sheet->getRowDimension(1)->setRowHeight(30);
                    $sheet->getRowDimension(2)->setRowHeight(30);

                    // Header values
                    $sheet->setCellValue('A1', 'NO');
                    $sheet->setCellValue('B1', 'UNIT KERJA');
                    $sheet->setCellValue('C1', 'JENIS PERANGKAT');
                    $sheet->setCellValue('D1', 'COMPUTER NAME');
                    $sheet->setCellValue('E1', 'USER');
                    $sheet->setCellValue('F1', 'SPESIFIKASI');
                    $sheet->setCellValue('I1', 'IP ADDRESS');
                    $sheet->setCellValue('J1', 'SISTEM OPERASI');
                    $sheet->setCellValue('K1', 'LICENSED');
                    $sheet->setCellValue('L1', 'MICROSOFT OFFICE');
                    $sheet->setCellValue('M1', 'LICENSED');
                    $sheet->setCellValue('N1', 'INVENTARIS PC');
                    $sheet->setCellValue('O1', 'INVENTARIS MONITOR');
                    $sheet->setCellValue('P1', 'INVENTARIS KEYBOARD');
                    $sheet->setCellValue('Q1', 'INVENTARIS MOUSE');
                    $sheet->setCellValue('R1', 'INVENTARIS LAINNYA');
                    $sheet->setCellValue('S1', 'DOKUMENTASI');

                    $sheet->setCellValue('F2', 'PROSESSOR');
                    $sheet->setCellValue('G2', 'RAM');
                    $sheet->setCellValue('H2', 'KAPASITAS HARDDISK');

                    // Merge headers
                    $merges = [
                        'A1:A2', 'B1:B2', 'C1:C2', 'D1:D2', 'E1:E2', 
                        'F1:H1', 
                        'I1:I2', 'J1:J2', 'K1:K2', 'L1:L2', 'M1:M2', 
                        'N1:N2', 'O1:O2', 'P1:P2', 'Q1:Q2', 'R1:R2', 'S1:S2'
                    ];
                    foreach ($merges as $mergeRange) {
                        $sheet->mergeCells($mergeRange);
                    }

                    // Style headers
                    $headerStyle = [
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'size' => 10,
                            'name' => 'Arial'
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'wrapText' => true
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '1F3864']
                        ]
                    ];
                    $sheet->getStyle('A1:S2')->applyFromArray($headerStyle);

                    // Freeze rows 1 & 2
                    $sheet->freezePane('A3');
                };

                // Setup headers for all sheets
                $setupSheet($sheetSpjm);
                $setupSheet($sheetSpmt);
                $setupSheet($sheetSubreg);

                // Fetch divisions
                $f = $this->buildLaporanFilters($filters);
                [$from, $to] = $this->laporanDateBounds($filters);

                $divSql = "SELECT division_code, division_label, division_group_name, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY CASE WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%spmt%' THEN 0 WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%subreg%' THEN 1 ELSE 2 END ASC, division_label ASC, id ASC";
                $stmt = $pdo->query($divSql);
                $divisions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                if ($f['division'] !== '') {
                    $selectedDivision = $f['division'];
                    $divisions = array_values(array_filter($divisions, function (array $row) use ($selectedDivision): bool {
                        return $this->reportDivisionMatches($row, $selectedDivision);
                    }));
                }

                $currentRows = ['SPJM' => 3, 'SPMT' => 3, 'SUBREG' => 3];
                $globalNos = ['SPJM' => 1, 'SPMT' => 1, 'SUBREG' => 1];
                $sheetDivIndices = ['SPJM' => 0, 'SPMT' => 0, 'SUBREG' => 0];

                foreach ($divisions as $division) {
                    $db = (string) ($division['inventory_db_name'] ?? '');
                    if (!$this->isSafeIdentifier($db)) {
                        continue;
                    }
                    $label = (string) ($division['division_label'] ?? $db);

                    // Determine sheet group
                    $dbLower = strtolower($db);
                    $codeLower = strtolower((string)($division['division_code'] ?? ''));
                    $labelLower = strtolower($label);
                    
                    if (strpos($dbLower, 'spjm') !== false || strpos($codeLower, 'spjm') !== false || strpos($labelLower, 'spjm') !== false) {
                        $grp = 'SPJM';
                    } elseif (strpos($dbLower, 'subreg') !== false || strpos($codeLower, 'subreg') !== false || strpos($labelLower, 'subreg') !== false) {
                        $grp = 'SUBREG';
                    } else {
                        $grp = 'SPMT';
                    }

                    $activeSheet = $sheetMap[$grp];
                    $currentRow = &$currentRows[$grp];
                    $globalNo = &$globalNos[$grp];
                    $divIdx = &$sheetDivIndices[$grp];

                    // Fetch PCs for this division
                    $dateCol = $this->reportTableDateColumn($pdo, $db, 'pc'); 
                    $where = []; 
                    $params = [];
                    if ($dateCol !== '') { 
                        if ($from !== '') { $where[] = "DATE(`$dateCol`) >= ?"; $params[] = $from; } 
                        if ($to !== '') { $where[] = "DATE(`$dateCol`) <= ?"; $params[] = $to; } 
                    }
                    $sql = sprintf('SELECT * FROM `%s`.`pc` %s ORDER BY `user` ASC, id_inventaris ASC', $db, $where ? ('WHERE ' . implode(' AND ', $where)) : '');
                    $stmt = $pdo->prepare($sql); 
                    $stmt->execute($params);
                    $pcs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

                    if (empty($pcs)) {
                        continue;
                    }

                    // Draw Division Separator Row
                    $activeSheet->getRowDimension($currentRow)->setRowHeight(24);
                    $activeSheet->mergeCells("A{$currentRow}:S{$currentRow}");
                    
                    $char = chr(65 + $divIdx); // A, B, C...
                    $separatorText = $char . "    " . strtoupper($label);
                    $activeSheet->setCellValue("A{$currentRow}", $separatorText);

                    // Style Division Separator Row
                    $separatorStyle = [
                        'font' => [
                            'bold' => true,
                            'size' => 10,
                            'name' => 'Arial'
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D9E1F2']
                        ]
                    ];
                    $activeSheet->getStyle("A{$currentRow}:S{$currentRow}")->applyFromArray($separatorStyle);
                    $currentRow++;
                    $divIdx++;

                    // Fetch Perangkat Lain for this division
                    try {
                        $sqlPL = sprintf('SELECT * FROM `%s`.`perangkat_lain`', $db);
                        $stmtPL = $pdo->query($sqlPL);
                        $allPerangkatLain = $stmtPL ? $stmtPL->fetchAll(PDO::FETCH_ASSOC) : [];
                    } catch (Throwable $e) {
                        $allPerangkatLain = [];
                    }

                    // Draw PC data rows
                    foreach ($pcs as $pc) {
                        $activeSheet->getRowDimension($currentRow)->setRowHeight(20);

                        // Find matching perangkat_lain rows
                        $related = [];
                        $pcId = isset($pc['id']) && (int)$pc['id'] > 0 ? (int)$pc['id'] : null;
                        $pcUser = trim((string)($pc['user'] ?? ''));
                        $pcCompName = trim((string)($pc['computer_name'] ?? ''));
                        $pcUnit = trim((string)($pc['unit_kerja'] ?? ''));

                        foreach ($allPerangkatLain as $pl) {
                            $matched = false;
                            $plPcRowId = isset($pl['pc_row_id']) && $pl['pc_row_id'] !== null ? (int)$pl['pc_row_id'] : null;
                            
                            if ($plPcRowId !== null && $pcId !== null) {
                                if ($plPcRowId === $pcId) {
                                    $matched = true;
                                }
                            } else {
                                if ($pcUser !== '') {
                                    if (trim((string)($pl['user'] ?? '')) === $pcUser && ($pl['pc_row_id'] === null || ($pl['edit_source'] ?? '') !== 'standalone')) {
                                        $matched = true;
                                    }
                                } else {
                                    $fallback = $pcCompName;
                                    $plVal = trim((string)($pl['user'] ?? ''));
                                    if ($plVal === '') {
                                        $plVal = trim((string)($pl['unit_kerja'] ?? ''));
                                    }
                                    if ($plVal === $fallback && ($pl['pc_row_id'] === null || ($pl['edit_source'] ?? '') !== 'standalone')) {
                                        $matched = true;
                                    }
                                }
                            }
                            if ($matched) {
                                $related[] = $pl;
                            }
                        }

                        // Group related assets
                        $monitors = [];
                        $keyboards = [];
                        $mice = [];
                        $others = [];
                        foreach ($related as $pl) {
                            $jp = strtolower(trim((string)($pl['jenis_perangkat'] ?? '')));
                            $idInv = trim((string)($pl['id_inventaris'] ?? ''));
                            if ($idInv === '') {
                                $idInv = '-';
                            }
                            if (strpos($jp, 'monitor') !== false) {
                                $monitors[] = $idInv;
                            } elseif (strpos($jp, 'keyboard') !== false) {
                                $keyboards[] = $idInv;
                            } elseif (strpos($jp, 'mouse') !== false) {
                                $mice[] = $idInv;
                            } else {
                                $others[] = $idInv . ' (' . ($pl['jenis_perangkat'] ?? '-') . ')';
                            }
                        }
                        $monitorStr = $monitors ? implode(', ', $monitors) : '-';
                        $keyboardStr = $keyboards ? implode(', ', $keyboards) : '-';
                        $mouseStr = $mice ? implode(', ', $mice) : '-';
                        $otherStr = $others ? implode(', ', $others) : '-';

                        // Fill cells
                        $activeSheet->setCellValue("A{$currentRow}", $globalNo++);
                        $activeSheet->setCellValue("B{$currentRow}", ($pc['unit_kerja'] ?? '') !== '' ? $pc['unit_kerja'] : '-');
                        $activeSheet->setCellValue("C{$currentRow}", ($pc['merk_perangkat'] ?? '') !== '' ? $pc['merk_perangkat'] : '-');
                        $activeSheet->setCellValue("D{$currentRow}", ($pc['computer_name'] ?? '') !== '' ? $pc['computer_name'] : '-');
                        $activeSheet->setCellValue("E{$currentRow}", ($pc['user'] ?? '') !== '' ? $pc['user'] : '-');
                        $activeSheet->setCellValue("F{$currentRow}", ($pc['processor'] ?? '') !== '' ? $pc['processor'] : '-');
                        $activeSheet->setCellValue("G{$currentRow}", ($pc['ram'] ?? '') !== '' ? $pc['ram'] : '-');
                        $activeSheet->setCellValue("H{$currentRow}", ($pc['kapasitas_harddisk'] ?? '') !== '' ? $pc['kapasitas_harddisk'] : '-');
                        $activeSheet->setCellValue("I{$currentRow}", ($pc['ip_address'] ?? '') !== '' ? $pc['ip_address'] : '-');
                        $activeSheet->setCellValue("J{$currentRow}", ($pc['sistem_operasi'] ?? '') !== '' ? $pc['sistem_operasi'] : '-');
                        $activeSheet->setCellValue("K{$currentRow}", ($pc['licensed_windows'] ?? '') !== '' ? $pc['licensed_windows'] : '-');
                        $activeSheet->setCellValue("L{$currentRow}", ($pc['microsoft_office'] ?? '') !== '' ? $pc['microsoft_office'] : '-');
                        $activeSheet->setCellValue("M{$currentRow}", ($pc['licensed_office'] ?? '') !== '' ? $pc['licensed_office'] : '-');
                        $activeSheet->setCellValue("N{$currentRow}", ($pc['id_inventaris'] ?? '') !== '' ? $pc['id_inventaris'] : '-');
                        $activeSheet->setCellValue("O{$currentRow}", $monitorStr);
                        $activeSheet->setCellValue("P{$currentRow}", $keyboardStr);
                        $activeSheet->setCellValue("Q{$currentRow}", $mouseStr);
                        $activeSheet->setCellValue("R{$currentRow}", $otherStr);
                        $activeSheet->setCellValue("S{$currentRow}", ($pc['computer_name'] ?? '') !== '' ? $pc['computer_name'] : '-');

                        // Style data row
                        $rowStyle = [
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => ['rgb' => 'D3D3D3']
                                ]
                            ]
                        ];
                        // Alternating row color
                        if ($currentRow % 2 === 0) {
                            $rowStyle['fill'] = [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F2F2']
                            ];
                        } else {
                            $rowStyle['fill'] = [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFFF']
                            ];
                        }
                        $activeSheet->getStyle("A{$currentRow}:S{$currentRow}")->applyFromArray($rowStyle);

                        // Alignments
                        // Center columns: A, I, K, M, N, O, P, Q, R, S
                        $centerCols = ['A', 'I', 'K', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
                        foreach ($centerCols as $col) {
                            $activeSheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        }
                        // Left columns: B, C, D, E, F, G, H, J, L
                        $leftCols = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'L'];
                        foreach ($leftCols as $col) {
                            $activeSheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                        }

                        // Status Licensed Styling
                        // Column K (Windows License) and Column M (Office License)
                        foreach (['K', 'M'] as $col) {
                            $val = strtoupper(trim((string)$activeSheet->getCell($col . $currentRow)->getValue()));
                            if ($val === 'LICENSED') {
                                $activeSheet->getStyle($col . $currentRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'E2EFDA']
                                    ],
                                    'font' => [
                                        'color' => ['rgb' => '375623'],
                                        'bold' => true
                                    ]
                                ]);
                            } elseif ($val === 'UNLICENSED' || $val === 'TIDAK LICENSED' || $val === 'NO') {
                                $activeSheet->getStyle($col . $currentRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'FCE4D6']
                                    ],
                                    'font' => [
                                        'color' => ['rgb' => 'C00000'],
                                        'bold' => true
                                    ]
                                ]);
                            }
                        }

                        // General cell vertical center alignment for the row
                        $activeSheet->getStyle("A{$currentRow}:S{$currentRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                        $currentRow++;
                    }
                }

                // Set active sheet index to the first sheet that has rows
                $activeIdx = 0;
                if ($currentRows['SPMT'] > 3) {
                    $activeIdx = 1;
                } elseif ($currentRows['SUBREG'] > 3) {
                    $activeIdx = 2;
                }
                $spreadsheet->setActiveSheetIndex($activeIdx);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                ob_start();
                $writer->save('php://output');
                $xlsx = ob_get_clean();

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
                header('Content-Length: ' . strlen($xlsx));
                echo $xlsx;
                exit;
            }
            $pdf = $this->buildInventoryGroupedReportPdf($title, $sheets);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        if ($type === 'complaint') {
            $rows = $this->fetchAllComplaintReportRows($pdo, $filters);
            if ($format === 'xlsx') {
                $xlsx = $this->buildComplaintExcelXlsx($title, $rows);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
                header('Content-Length: ' . strlen($xlsx));
                echo $xlsx;
                exit;
            }
            $pdf = $this->buildComplaintPdf($title, $rows);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        if ($type === 'routine') {
            $rows = $this->fetchRoutineMonitoringReportRows($pdo, $filters);
            if ($format === 'xlsx') {
                if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    if (file_exists(__DIR__ . '/../../peminjaman_laptop/vendor/autoload.php')) {
                        require_once __DIR__ . '/../../peminjaman_laptop/vendor/autoload.php';
                    } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                        require_once __DIR__ . '/../../vendor/autoload.php';
                    }
                }
                
                $f = $this->buildLaporanFilters($filters);
                $categoryUpper = strtoupper($f['category']);
                if (in_array($categoryUpper, ['CCTV', 'GATE', 'SERVER'], true) && $f['month'] !== 'all') {
                    $monthNum = (int)$f['month'];
                    $yearNum = (int)$f['year'];
                    $monthNamesIndo = [
                        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 
                        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 
                        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
                    ];
                    $monthName = $monthNamesIndo[$monthNum] ?? 'BULAN';
                    $daysInMonth = (int)date('t', mktime(0, 0, 0, $monthNum, 1, $yearNum));
                    
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle("CEK STATUS " . $categoryUpper . "(" . $monthName . ")");
                    
                    $pivotItems = [];
                    if ($categoryUpper === 'CCTV') {
                        $cctvStmt = $pdo->query("SELECT id, lokasi, nama_cctv FROM cctv_inventaris WHERE status = 'AKTIF' ORDER BY lokasi ASC, nama_cctv ASC");
                        $cctvItems = $cctvStmt ? $cctvStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                        foreach ($cctvItems as $cctv) {
                            $pivotItems[] = [
                                'query_id' => (int)$cctv['id'] + 10000,
                                'location' => (string)($cctv['lokasi'] ?? '-'),
                                'name' => $cctv['nama_cctv']
                            ];
                        }
                    } elseif ($categoryUpper === 'SERVER') {
                        $serverStmt = $pdo->query("SELECT id, item_name FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ''), item_group)) = 'SERVER' AND is_active = 1 ORDER BY sort_order ASC, item_name ASC");
                        $serverItems = $serverStmt ? $serverStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                        foreach ($serverItems as $srv) {
                            $pivotItems[] = [
                                'query_id' => (int)$srv['id'],
                                'location' => '',
                                'name' => (string)$srv['item_name']
                            ];
                        }
                    } else { // GATE
                        $gateStmt = $pdo->query("SELECT id, item_name FROM routine_monitoring_items WHERE UPPER(COALESCE(NULLIF(category_field, ''), item_group)) = 'GATE' AND is_active = 1 ORDER BY sort_order ASC, item_name ASC");
                        $gateItems = $gateStmt ? $gateStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                        foreach ($gateItems as $gate) {
                            $parts = explode(' - ', (string)$gate['item_name'], 2);
                            $loc = $parts[0] ?? '-';
                            $dispName = $parts[1] ?? (string)$gate['item_name'];
                            $pivotItems[] = [
                                'query_id' => (int)$gate['id'],
                                'location' => $loc,
                                'name' => $dispName,
                                'original_item_name' => $gate['item_name']
                            ];
                        }
                    }
                    
                    $startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
                    $endDate = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth);
                    $checksStmt = $pdo->prepare("SELECT item_id, item_name, monitor_date, condition_status, keterangan FROM routine_monitoring WHERE period_type = 'daily' AND monitor_date BETWEEN :start_date AND :end_date");
                    $checksStmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
                    $rawChecks = $checksStmt ? $checksStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                    
                    $checks = [];
                    foreach ($rawChecks as $row) {
                        $itemId = (int)$row['item_id'];
                        $name = strtolower(trim((string)$row['item_name']));
                        $date = (string)$row['monitor_date'];
                        $status = strtoupper(trim((string)$row['condition_status']));
                        $keterangan = trim((string)$row['keterangan']);
                        
                        $checks['id_' . $itemId][$date] = [
                            'status' => $status,
                            'keterangan' => $keterangan
                        ];
                        if ($name !== '') {
                            $checks['name_' . $name][$date] = [
                                'status' => $status,
                                'keterangan' => $keterangan
                            ];
                        }
                    }
                    
                    $headerYellowStyle = [
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFFFD965']
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ]
                    ];
                    
                    if (in_array($categoryUpper, ['CCTV', 'GATE'], true)) {
                        $sheet->mergeCells('A1:A2');
                        $sheet->setCellValue('A1', 'LOKASI');
                        
                        $sheet->mergeCells('B1:B2');
                        $colBHeader = ($categoryUpper === 'CCTV') ? 'NAMA CCTV' : ($categoryUpper === 'GATE' ? 'NAMA BARANG' : 'DATA BARANG');
                        $sheet->setCellValue('B1', $colBHeader);
                        
                        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 2);
                        $sheet->mergeCells('C1:' . $lastColLetter . '1');
                        $sheet->setCellValue('C1', 'TANGGAL');
                        
                        $keteranganColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 3);
                        $sheet->mergeCells($keteranganColLetter . '1:' . $keteranganColLetter . '2');
                        $sheet->setCellValue($keteranganColLetter . '1', 'KETERANGAN');
                        
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($d + 2);
                            $dateStr = sprintf('%02d/%02d/%04d', $d, $monthNum, $yearNum);
                            $sheet->setCellValue($colLetter . '2', $dateStr);
                        }
                        
                        $sheet->getStyle('A1:A2')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle('B1:B2')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle('C1:' . $lastColLetter . '1')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle('C2:' . $lastColLetter . '2')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle($keteranganColLetter . '1:' . $keteranganColLetter . '2')->applyFromArray($headerYellowStyle);
                    } else { // SERVER
                        $sheet->mergeCells('A1:A2');
                        $sheet->setCellValue('A1', 'NAMA SERVER');
                        
                        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 1);
                        $sheet->mergeCells('B1:' . $lastColLetter . '1');
                        $sheet->setCellValue('B1', 'TANGGAL');
                        
                        $keteranganColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 2);
                        $sheet->mergeCells($keteranganColLetter . '1:' . $keteranganColLetter . '2');
                        $sheet->setCellValue($keteranganColLetter . '1', 'KETERANGAN');
                        
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($d + 1);
                            $dateStr = sprintf('%02d/%02d/%04d', $d, $monthNum, $yearNum);
                            $sheet->setCellValue($colLetter . '2', $dateStr);
                        }
                        
                        $sheet->getStyle('A1:A2')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle('B1:' . $lastColLetter . '1')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle('B2:' . $lastColLetter . '2')->applyFromArray($headerYellowStyle);
                        $sheet->getStyle($keteranganColLetter . '1:' . $keteranganColLetter . '2')->applyFromArray($headerYellowStyle);
                    }
                    
                    $rowHeights = [];
                    $rowHeights[1] = 14.5;
                    $rowHeights[2] = 14.5;
                    
                    $currentRow = 3;
                    foreach ($pivotItems as $item) {
                        $itemLocation = $item['location'];
                        $itemName = $item['name'];
                        $itemQueryId = $item['query_id'];
                        
                        if (in_array($categoryUpper, ['CCTV', 'GATE'], true)) {
                            $sheet->setCellValue('A' . $currentRow, $itemLocation);
                            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                            
                            $sheet->setCellValue('B' . $currentRow, $itemName);
                            $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle('B' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                        } else { // SERVER
                            $sheet->setCellValue('A' . $currentRow, $itemName);
                            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                            $sheet->getStyle('A' . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                        }
                        
                        $offCount = 0;
                        
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(in_array($categoryUpper, ['CCTV', 'GATE'], true) ? $d + 2 : $d + 1);
                            $dateKey = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $d);
                            
                            $idKey = 'id_' . $itemQueryId;
                            $originalName = isset($item['original_item_name']) ? $item['original_item_name'] : $itemName;
                            $nameKey = 'name_' . strtolower(trim($originalName));
                            
                            $status = '';
                            $keterangan = '';
                            if (isset($checks[$idKey][$dateKey])) {
                                $status = $checks[$idKey][$dateKey]['status'];
                                $keterangan = $checks[$idKey][$dateKey]['keterangan'];
                            } elseif (isset($checks[$nameKey][$dateKey])) {
                                $status = $checks[$nameKey][$dateKey]['status'];
                                $keterangan = $checks[$nameKey][$dateKey]['keterangan'];
                            }
                            
                            $normalizedStatus = '';
                            if (in_array($categoryUpper, ['CCTV', 'SERVER'], true)) {
                                if ($status === 'ON' || $status === 'BAIK' || $status === 'AKTIF') {
                                    $normalizedStatus = 'ON';
                                } elseif ($status === 'OFF' || $status === 'RUSAK' || $status === 'TIDAK AKTIF' || $status === 'BURUK') {
                                    $normalizedStatus = 'OFF';
                                    $offCount++;
                                }
                            } else { // GATE
                                if ($status === 'BAIK' || $status === 'AKTIF' || $status === 'ON') {
                                    $normalizedStatus = 'Aktif';
                                } elseif ($status === 'BURUK' || $status === 'RUSAK' || $status === 'OFF') {
                                    $normalizedStatus = 'Rusak';
                                    $offCount++;
                                }
                            }
                            
                            if ($normalizedStatus !== '') {
                                $displayVal = $normalizedStatus;
                                $sheet->setCellValue($colLetter . $currentRow, $displayVal);
                                
                                if ($keterangan !== '') {
                                    $sheet->getComment($colLetter . $currentRow)->getText()->createTextRun($keterangan);
                                }
                                
                                $isOk = ($normalizedStatus === 'ON' || $normalizedStatus === 'Aktif');
                                $bgColor = $isOk ? 'FF00B050' : 'FFFF0000';
                                
                                $sheet->getStyle($colLetter . $currentRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['argb' => $bgColor]
                                    ],
                                    'font' => [
                                        'color' => ['argb' => 'FFFFFFFF'],
                                        'bold' => true
                                    ],
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                                    ]
                                ]);
                            }
                        }
                        
                        $keteranganText = '';
                        if (in_array($categoryUpper, ['CCTV', 'SERVER'], true)) {
                            $keteranganText = "Mati: " . $offCount . " kali";
                            $rowHeights[$currentRow] = 14.5;
                        } else { // GATE
                            $rowComments = [];
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                $dateKey = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $d);
                                $idKey = 'id_' . $itemQueryId;
                                $originalName = isset($item['original_item_name']) ? $item['original_item_name'] : $itemName;
                                $nameKey = 'name_' . strtolower(trim($originalName));
                                
                                $keterangan = '';
                                if (isset($checks[$idKey][$dateKey])) {
                                    $keterangan = $checks[$idKey][$dateKey]['keterangan'];
                                } elseif (isset($checks[$nameKey][$dateKey])) {
                                    $keterangan = $checks[$nameKey][$dateKey]['keterangan'];
                                }
                                
                                if ($keterangan !== '') {
                                    $rowComments[] = "Tgl " . $d . ": " . $keterangan;
                                }
                            }
                            $keteranganText = implode("\n", $rowComments);
                            if (!empty($rowComments)) {
                                $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setWrapText(true);
                                $rowHeights[$currentRow] = -1; // Let Excel auto-adjust height for multi-line comments
                            } else {
                                $rowHeights[$currentRow] = 14.5;
                            }
                        }
                        $sheet->setCellValue($keteranganColLetter . $currentRow, $keteranganText);
                        $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setHorizontal(in_array($categoryUpper, ['CCTV', 'SERVER'], true) ? \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER : \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle($keteranganColLetter . $currentRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                        
                        $currentRow++;
                    }
                    
                    $lastRowIndex = $currentRow - 1;
                    
                    if (in_array($categoryUpper, ['CCTV', 'GATE'], true)) {
                        // Merge consecutive locations in Column A
                        $mergeStart = 3;
                        for ($r = 3; $r <= $lastRowIndex; $r++) {
                            $currentLoc = $sheet->getCell('A' . $r)->getValue();
                            $nextLoc = ($r < $lastRowIndex) ? $sheet->getCell('A' . ($r + 1))->getValue() : null;
                            
                            if ($currentLoc !== $nextLoc) {
                                if ($r > $mergeStart) {
                                    $sheet->mergeCells('A' . $mergeStart . ':A' . $r);
                                }
                                $mergeStart = $r + 1;
                            }
                        }
                    }
                    
                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    if (in_array($categoryUpper, ['CCTV', 'GATE'], true)) {
                        $sheet->getColumnDimension('B')->setAutoSize(true);
                    }
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(in_array($categoryUpper, ['CCTV', 'GATE'], true) ? $d + 2 : $d + 1);
                        $sheet->getColumnDimension($colLetter)->setWidth(13.0);
                    }
                    $sheet->getColumnDimension($keteranganColLetter)->setWidth(30.0); // Make it slightly wider for comments text
                    
                    for ($r = 1; $r <= $lastRowIndex; $r++) {
                        $h = isset($rowHeights[$r]) ? $rowHeights[$r] : 14.5;
                        if ($h > 0) {
                            $sheet->getRowDimension($r)->setRowHeight($h);
                        }
                    }
                    
                    $borderStyle = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000']
                            ]
                        ]
                    ];
                    $sheet->getStyle('A1:' . $keteranganColLetter . $lastRowIndex)->applyFromArray($borderStyle);
                } else {
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Routine Monitoring');

                    // Default font: Arial 10
                    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

                    // ---- Column widths ----
                    $rmColWidths = ['A' => 6, 'B' => 16, 'C' => 14, 'D' => 36, 'E' => 16, 'F' => 36, 'G' => 22, 'H' => 22];
                    foreach ($rmColWidths as $col => $width) {
                        $sheet->getColumnDimension($col)->setWidth($width);
                    }

                    // ---- Row 1: Title (navy text, bold) ----
                    $sheet->mergeCells('A1:H1');
                    $sheet->setCellValue('A1', $title);
                    $sheet->getStyle('A1')->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 13, 'name' => 'Arial', 'color' => ['rgb' => '1F3864']],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension(1)->setRowHeight(26);

                    // ---- Row 2: Export date (italic, gray) ----
                    $sheet->mergeCells('A2:H2');
                    $sheet->setCellValue('A2', 'Diexport: ' . date('d-m-Y H:i:s'));
                    $sheet->getStyle('A2')->applyFromArray([
                        'font'      => ['italic' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '595959']],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension(2)->setRowHeight(16);

                    // ---- Row 3: Spacer ----
                    $sheet->getRowDimension(3)->setRowHeight(6);

                    // ---- Row 4: Header (navy background, white bold) ----
                    $rmHeaders = $this->routineReportHeaders();
                    $rmHeaderCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    foreach ($rmHeaderCols as $idx => $col) {
                        $sheet->setCellValue($col . '4', $rmHeaders[$idx] ?? '');
                    }
                    $sheet->getStyle('A4:H4')->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'wrapText'   => true,
                        ],
                        'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
                    ]);
                    $sheet->getRowDimension(4)->setRowHeight(28);

                    // Freeze header rows
                    $sheet->freezePane('A5');

                    // ---- Group rows by kategori ----
                    $groupedRows = [];
                    foreach ($rows as $row) {
                        $kat = strtoupper(trim((string)($row['kategori'] ?? '-')));
                        $groupedRows[$kat][] = $row;
                    }

                    // Category color map: [background, text color, icon label]
                    $catColors = [
                        'CCTV'   => ['bg' => '1A5276', 'fg' => 'FFFFFF', 'icon' => '📷'],
                        'GATE'   => ['bg' => '1E8449', 'fg' => 'FFFFFF', 'icon' => '🚪'],
                        'SERVER' => ['bg' => '6C3483', 'fg' => 'FFFFFF', 'icon' => '🖥'],
                    ];
                    $defaultCatColor = ['bg' => '2E4053', 'fg' => 'FFFFFF', 'icon' => '📋'];

                    // Define preferred order
                    $categoryOrder = ['CCTV', 'GATE', 'SERVER'];
                    // Add any extra categories not in preferred order
                    foreach (array_keys($groupedRows) as $kat) {
                        if (!in_array($kat, $categoryOrder, true)) {
                            $categoryOrder[] = $kat;
                        }
                    }

                    $barisSekarang = 5;
                    $globalNo      = 1;

                    foreach ($categoryOrder as $kat) {
                        if (!isset($groupedRows[$kat]) || empty($groupedRows[$kat])) {
                            continue;
                        }
                        $catRows  = $groupedRows[$kat];
                        $catMeta  = $catColors[$kat] ?? $defaultCatColor;
                        $catCount = count($catRows);

                        // ---- Category separator row ----
                        $sheet->mergeCells('A' . $barisSekarang . ':H' . $barisSekarang);
                        $sheet->setCellValue('A' . $barisSekarang, '  ' . $kat . '   (' . $catCount . ' data)');
                        $sheet->getStyle('A' . $barisSekarang . ':H' . $barisSekarang)->applyFromArray([
                            'font'      => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['rgb' => $catMeta['fg']]],
                            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $catMeta['bg']]],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                            'borders'   => [
                                'top'    => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $catMeta['bg']]],
                                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $catMeta['bg']]],
                            ],
                        ]);
                        $sheet->getRowDimension($barisSekarang)->setRowHeight(22);
                        $barisSekarang++;

                        // ---- Data rows for this category ----
                        $catDataNo = 1; // per-category numbering in No column
                        foreach ($catRows as $row) {
                            $isEven  = ($catDataNo % 2 === 0);
                            $rowFill = $isEven ? 'EEF4FB' : 'FFFFFF';

                            $sheet->setCellValue('A' . $barisSekarang, $globalNo);
                            $sheet->setCellValue('B' . $barisSekarang, (string)($row['tanggal'] ?? '-'));
                            $sheet->setCellValue('C' . $barisSekarang, strtoupper((string)($row['kategori'] ?? '-')));
                            $sheet->setCellValue('D' . $barisSekarang, (string)($row['checking'] ?? '-'));
                            $sheet->setCellValue('E' . $barisSekarang, ucwords(strtolower((string)($row['kondisi'] ?? '-'))));
                            $sheet->setCellValue('F' . $barisSekarang, (string)($row['keterangan'] ?? '-'));
                            $sheet->setCellValue('G' . $barisSekarang, (string)($row['checked_by'] ?? '-'));
                            $sheet->setCellValue('H' . $barisSekarang, (string)($row['update'] ?? '-'));

                            // Base style
                            $sheet->getStyle('A' . $barisSekarang . ':H' . $barisSekarang)->applyFromArray([
                                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowFill]],
                                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
                                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                            ]);

                            // Center: No, Tanggal, Kategori, Kondisi, Update
                            foreach (['A', 'B', 'C', 'E', 'H'] as $centerCol) {
                                $sheet->getStyle($centerCol . $barisSekarang)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            }
                            // Left: Nama Checking, Keterangan, Dicek Oleh
                            foreach (['D', 'F', 'G'] as $leftCol) {
                                $sheet->getStyle($leftCol . $barisSekarang)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                            }
                            $sheet->getStyle('F' . $barisSekarang)->getAlignment()->setWrapText(true);

                            // ---- Status highlight: Kondisi column (E) ----
                            $kondisiVal = strtoupper(trim((string)($row['kondisi'] ?? '')));
                            if (in_array($kondisiVal, ['BAIK', 'ON', 'AKTIF', 'AMAN', 'ACTIVE'], true)) {
                                $sheet->getStyle('E' . $barisSekarang)->applyFromArray([
                                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                                    'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                                ]);
                            } elseif (in_array($kondisiVal, ['KURANG BAIK', 'WARNING', 'PENDING'], true)) {
                                $sheet->getStyle('E' . $barisSekarang)->applyFromArray([
                                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                                    'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                                ]);
                            } elseif (in_array($kondisiVal, ['BURUK', 'OFF', 'TIDAK AKTIF', 'RUSAK'], true)) {
                                $sheet->getStyle('E' . $barisSekarang)->applyFromArray([
                                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                                    'font' => ['bold' => true, 'color' => ['rgb' => '721C24']],
                                ]);
                            }

                            $sheet->getRowDimension($barisSekarang)->setRowHeight(20);
                            $barisSekarang++;
                            $catDataNo++;
                            $globalNo++;
                        }

                        // Spacer row after each category
                        $sheet->getRowDimension($barisSekarang)->setRowHeight(8);
                        $barisSekarang++;
                    }
                }
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
                ob_start();
                $writer->save('php://output');
                $xlsx = ob_get_clean();
                header('Content-Length: ' . strlen($xlsx));
                echo $xlsx;
                exit;
            }
            $pdf = $this->buildRoutineMonitoringGroupedPdf($title, $rows);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        if ($type === 'user') {
            $rows = $this->fetchUserReportRows($pdo, $filters);
            if ($format === 'xlsx') {
                if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    if (file_exists(__DIR__ . '/../../peminjaman_laptop/vendor/autoload.php')) {
                        require_once __DIR__ . '/../../peminjaman_laptop/vendor/autoload.php';
                    } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                        require_once __DIR__ . '/../../vendor/autoload.php';
                    }
                }
                $spreadsheet = $this->buildUserReportXlsx($title, $rows);
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
                ob_start();
                $writer->save('php://output');
                $xlsx = ob_get_clean();
                header('Content-Length: ' . strlen($xlsx));
                echo $xlsx;
                exit;
            }
            $pdf = $this->buildUserReportPdf($title, $rows);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        $rows = $this->fetchAllLogReportRows($pdo, $filters);
        if ($format === 'xlsx') {
            $xlsx = $this->buildLogBarangExcelXlsx($title, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
            header('Content-Length: ' . strlen($xlsx));
            echo $xlsx;
            exit;
        }
        $pdf = $this->buildLogBarangPdf($title, $rows, $filters);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildLaporanViewData(PDO $pdo, string $type, array $filters = []): array
    {
        $type = $this->normalizeLaporanType($type);
        if ($type === 'inventory') {
            $pages = [];
            foreach ($this->fetchAllInventoryReportSheets($pdo, $filters) as $sheet) {
                $groups = [];
                foreach ($this->inventorySheetUserGroups($sheet) as $group) {
                    $groups[] = [
                        'title' => 'User: ' . (string) (($group['summary']['user'] ?? '') ?: ($group['title'] ?? '-')),
                        'summary' => (array) ($group['summary'] ?? []),
                        'headers' => ['No', 'Gambar', 'ID Inventaris', 'Jenis Perangkat', 'Merk', 'Unit Kerja', 'Status'],
                        'rows' => $this->mapInventoryRowsForDetailView((array) ($group['rows'] ?? [])),
                    ];
                }
                if (!$groups) {
                    $groups[] = [
                        'title' => 'Tidak ada data',
                        'summary' => [],
                        'headers' => ['No', 'Gambar', 'ID Inventaris', 'Jenis Perangkat', 'Merk', 'Unit Kerja', 'Status'],
                        'rows' => [],
                    ];
                }
                $pages[] = [
                    'title' => 'Laporan Data Inventaris - ' . (string) ($sheet['division'] ?? 'Data Inventaris'),
                    'groups' => $groups,
                ];
            }
            return ['type' => $type, 'title' => $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type), 'pages' => $pages];
        }
        if ($type === 'complaint') {
            $rows = $this->fetchAllComplaintReportRows($pdo, $filters);
            return ['type' => $type, 'title' => $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type), 'sections' => [[
                'title' => 'Seluruh Data Keluhan', 'subtitle' => '',
                'headers' => ['No', 'Gambar', 'Ticket', 'Tanggal & Jam', 'Status', 'Nama', 'Email', 'Divisi', 'Barang', 'Lokasi', 'Deskripsi', 'Catatan'],
                'rows' => array_map(function (array $row, int $i) {
                    return [(string) ($i + 1), ['type' => 'image', 'src' => (string) ($row['doc_image'] ?? ''), 'alt' => (string) ($row['ticket_no'] ?? 'Dokumentasi')], (string) ($row['ticket_no'] ?? '-'), (string) ($row['datetime_plain'] ?? '-'), (string) ($row['status'] ?? '-'), (string) ($row['name'] ?? '-'), (string) ($row['email_plain'] ?? '-'), (string) ($row['division'] ?? '-'), (string) ($row['item'] ?? '-'), (string) ($row['location'] ?? '-'), (string) ($row['description'] ?? '-'), (string) (($row['catatan_penanganan'] ?? '') ?: '-')];
                }, $rows, array_keys($rows)),
            ]]];
        }
        if ($type === 'routine') {
            $rows = $this->fetchRoutineMonitoringReportRows($pdo, $filters);
            return ['type' => $type, 'title' => $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type), 'sections' => [[
                'title' => 'Seluruh Data Routine Monitoring', 'subtitle' => '',
                'headers' => $this->routineReportHeaders(),
                'rows' => $this->routineReportRowsForOutput($rows),
            ]]];
        }
        if ($type === 'user') {
            $rows = $this->fetchUserReportRows($pdo, $filters);
            return ['type' => $type, 'title' => $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type), 'sections' => [[
                'title' => 'Seluruh Data User', 'subtitle' => '',
                'headers' => $this->userReportHeaders(),
                'rows' => $this->userReportRowsForOutput($rows),
            ]]];
        }
        $rows = $this->fetchAllLogReportRows($pdo, $filters);
        return ['type' => $type, 'title' => $this->laporanTitle($type) . $this->buildLaporanTitleSuffix($filters, $type), 'sections' => [[
            'title' => 'Seluruh Data Log Barang', 'subtitle' => '',
            'headers' => ['No', 'Tanggal', 'Nama Barang', 'Qty', 'No. PO', 'Status', 'Divisi', 'Log No', 'Keterangan'],
            'rows' => array_map(function (array $row) {
                return [(string) ($row['no'] ?? ''), (string) ($row['date'] ?? '-'), (string) ($row['item'] ?? '-'), (string) ($row['qty'] ?? '1'), (string) ($row['no_po'] ?? '-'), (string) ($row['status'] ?? '-'), (string) ($row['division'] ?? '-'), (string) ($row['log_no'] ?? '-'), (string) (($row['keterangan'] ?? '') ?: '-')];
            }, $rows),
        ]]];
    }

    private function splitInventorySheetRowsForReport(array $sheet): array
    {
        $pc = [];
        $other = [];
        foreach (array_slice((array) ($sheet['rows'] ?? []), 4) as $row) {
            $type = strtoupper(trim((string) ($row[3] ?? '')));
            if ($type === 'PC') {
                $pc[] = $row;
            } else {
                $other[] = $row;
            }
        }
        return ['pc' => $pc, 'other' => $other];
    }
    private function inventorySheetUserGroups(array $sheet): array
    {
        $dataRows = array_values(array_slice((array) ($sheet['rows'] ?? []), 4));
        $groups = [];
        $order = [];
        $makeKey = static function (array $row): string {
            $user = trim((string) ($row[8] ?? ''));
            if ($user !== '') { return 'user:' . strtolower($user); }
            $computer = trim((string) ($row[7] ?? ''));
            if ($computer !== '') { return 'computer:' . strtolower($computer); }
            $unit = trim((string) ($row[17] ?? ''));
            return 'unit:' . strtolower($unit !== '' ? $unit : 'tanpa_user');
        };
        foreach ($dataRows as $row) {
            $row = (array) $row;
            $key = $makeKey($row);
            if (!isset($groups[$key])) {
                $groups[$key] = ['pc' => null, 'rows' => [], 'title' => trim((string) (($row[8] ?? '') ?: (($row[7] ?? '') ?: (($row[17] ?? '') ?: 'Tanpa User'))))];
                $order[] = $key;
            }
            if (strtoupper(trim((string) ($row[3] ?? ''))) === 'PC' && $groups[$key]['pc'] === null) {
                $groups[$key]['pc'] = $row;
            }
            $groups[$key]['rows'][] = $row;
        }
        $out = [];
        foreach ($order as $key) {
            $group = $groups[$key];
            $summaryRow = (array) ($group['pc'] ?: ($group['rows'][0] ?? []));
            $group['summary'] = $this->inventorySummaryFromSheetRow($summaryRow, (string) ($group['title'] ?? '-'));
            $out[] = $group;
        }
        return $out;
    }

    private function inventorySummaryFromSheetRow(array $row, string $fallbackUser = '-'): array
    {
        return [
            'computer_name' => (string) (($row[7] ?? '') ?: '-'),
            'user' => (string) (($row[8] ?? '') ?: ($fallbackUser !== '' ? $fallbackUser : '-')),
            'processor' => (string) (($row[9] ?? '') ?: '-'),
            'ram' => (string) (($row[10] ?? '') ?: '-'),
            'harddisk' => (string) (($row[11] ?? '') ?: '-'),
            'ip' => (string) (($row[12] ?? '') ?: '-'),
            'os' => (string) (($row[13] ?? '') ?: '-'),
            'license' => (string) (($row[14] ?? '') ?: '-'),
            'office' => (string) (($row[15] ?? '') ?: '-'),
            'office_license' => (string) (($row[16] ?? '') ?: '-'),
        ];
    }

    private function mapInventoryRowsForDetailView(array $rows): array
    {
        $out = [];
        foreach (array_values($rows) as $idx => $row) {
            $row = (array) $row;
            $out[] = [
                (string) ($idx + 1),
                ['type' => 'image', 'src' => (string) ($row[1] ?? ''), 'alt' => (string) ($row[5] ?? 'Gambar')],
                (string) ($row[4] ?? '-'),
                (string) ($row[5] ?? '-'),
                (string) ($row[6] ?? '-'),
                (string) ($row[17] ?? '-'),
                (string) (($row[18] ?? '') ?: (strtoupper(trim((string) ($row[3] ?? ''))) === 'PC' ? 'AKTIF' : '-')),
            ];
        }
        return $out;
    }

    private function mapInventoryRowsForDetailPdf(array $rows): array
    {
        $out = [];
        foreach (array_values($rows) as $idx => $row) {
            $row = (array) $row;
            $out[] = [
                'no' => (string) ($idx + 1),
                'image' => (string) ($row[1] ?? ''),
                'id' => (string) ($row[4] ?? '-'),
                'device' => (string) ($row[5] ?? '-'),
                'brand' => (string) ($row[6] ?? '-'),
                'unit' => (string) ($row[17] ?? '-'),
                'status' => (string) (($row[18] ?? '') ?: (strtoupper(trim((string) ($row[3] ?? ''))) === 'PC' ? 'AKTIF' : '-')),
            ];
        }
        return $out;
    }

    private function mapInventoryRowsForView(array $rows): array
    {
        $out = [];
        foreach (array_values($rows) as $idx => $row) {
            $out[] = [
                (string) ($idx + 1),
                ['type' => 'image', 'src' => (string) ($row[1] ?? ''), 'alt' => (string) ($row[5] ?? 'Gambar')],
                (string) ($row[4] ?? '-'),
                (string) ($row[5] ?? '-'),
                (string) ($row[6] ?? '-'),
                (string) ($row[8] ?? '-'),
                (string) ($row[17] ?? '-'),
                (string) (($row[18] ?? '') ?: '-'),
            ];
        }
        return $out;
    }

    private function inventoryReportSimpleColumns(): array
    {
        return [
            ['title' => 'No', 'key' => 'no', 'width' => 28, 'chars' => 4],
            ['title' => 'Gambar', 'key' => 'image', 'width' => 56, 'chars' => 10],
            ['title' => 'ID Inventaris', 'key' => 'id', 'width' => 118, 'chars' => 17],
            ['title' => 'Jenis Perangkat', 'key' => 'device', 'width' => 156, 'chars' => 23],
            ['title' => 'Merk', 'key' => 'brand', 'width' => 100, 'chars' => 15],
            ['title' => 'Unit Kerja', 'key' => 'unit', 'width' => 260, 'chars' => 40],
            ['title' => 'Status', 'key' => 'status', 'width' => 72, 'chars' => 10],
        ];
    }
    private function mapInventoryRowsForPdf(array $rows): array
    {
        return $this->mapInventoryRowsForDetailPdf($rows);
    }
    private function buildInventoryGroupedReportPdf(string $title, array $sheets): string
    {
        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 26;
        $blue = '0.18 0.41 0.67';
        $lightBlue = '0.90 0.95 0.99';
        $green = '0.24 0.69 0.31';
        $red = '0.90 0.20 0.20';
        $black = '0.12 0.18 0.25';
        $border = '0.75 0.81 0.89';
        $columns = $this->inventoryReportSimpleColumns();

        if (!$sheets) {
            $sheets = [['division' => 'Data Inventaris', 'rows' => []]];
        }

        $prepared = [];
        $allRowsForImages = [];
        foreach ($sheets as $sheet) {
            $groups = [];
            foreach ($this->inventorySheetUserGroups($sheet) as $group) {
                $rows = $this->mapInventoryRowsForDetailPdf((array) ($group['rows'] ?? []));
                foreach ($rows as $row) { $allRowsForImages[] = $row; }
                $groups[] = [
                    'title' => (string) ($group['title'] ?? '-'),
                    'summary' => (array) ($group['summary'] ?? []),
                    'rows' => $rows,
                ];
            }
            $prepared[] = [
                'division' => (string) ($sheet['division'] ?? 'Data Inventaris'),
                'groups' => $groups,
            ];
        }

        $objects = [
            1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $imageRegistry = $this->collectPdfImageResources($allRowsForImages);
        $streams = [];
        $streamImageUsage = [];
        $current = [];
        $currentImages = [];
        $y = $pageHeight - $margin;
        $currentDivision = '';

        $normalizeText = function (string $text): string {
            $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
            if ($text === '') { return '-'; }
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
                if ($converted !== false && $converted !== '') { $text = $converted; }
            }
            $text = preg_replace('/[^ -~\x80-\xFF]/', '', $text) ?? $text;
            return $text === '' ? '-' : $text;
        };
        $escape = function (string $text) use ($normalizeText): string {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $normalizeText($text));
        };
        $addText = function (float $x, float $yPos, string $text, int $fontId = 1, int $fontSize = 9, string $color = '0 0 0') use (&$current, $escape) {
            $current[] = 'BT';
            $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize);
            $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yPos);
            $current[] = '(' . $escape($text) . ') Tj';
            $current[] = 'ET';
        };
        $addRect = function (float $x, float $yPos, float $w, float $h, ?string $fill = null, string $stroke = '0.75 0.81 0.89', float $lineWidth = 0.7) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            $current[] = $stroke . ' RG';
            $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yPos, $w, $h, $fill !== null ? 'B' : 'S');
        };
        $addImage = function (string $alias, float $x, float $yPos, float $w, float $h) use (&$current, &$currentImages) {
            $currentImages[] = $alias;
            $current[] = 'q';
            $current[] = sprintf('%.2f 0 0 %.2f %.2f %.2f cm', $w, $h, $x, $yPos);
            $current[] = '/' . $alias . ' Do';
            $current[] = 'Q';
        };
        $wrapText = function (string $text, int $maxChars) use ($normalizeText): array {
            $text = $normalizeText($text);
            $parts = preg_split('/\s+/', $text) ?: [];
            $lines = [];
            $line = '';
            foreach ($parts as $part) {
                $candidate = $line === '' ? $part : $line . ' ' . $part;
                if (strlen($candidate) <= $maxChars) { $line = $candidate; continue; }
                if ($line !== '') { $lines[] = $line; $line = $part; continue; }
                while (strlen($part) > $maxChars) { $lines[] = substr($part, 0, $maxChars); $part = substr($part, $maxChars); }
                $line = $part;
            }
            if ($line !== '') { $lines[] = $line; }
            return $lines ?: ['-'];
        };
        $finishPage = function () use (&$streams, &$streamImageUsage, &$current, &$currentImages) {
            if (!$current) { return; }
            $streams[] = implode("\n", $current);
            $streamImageUsage[] = array_values(array_unique($currentImages));
            $current = [];
            $currentImages = [];
        };
        $startPage = function (string $division, string $suffix = '') use (&$current, &$currentImages, &$y, &$currentDivision, $pageHeight, $margin, $title, $addText, $blue, $black) {
            $current = [];
            $currentImages = [];
            $currentDivision = $division;
            $y = $pageHeight - $margin;
            $addText($margin, $y - 12, 'SPMT INVENTORY CONTROL', 2, 16, $blue);
            $addText($margin, $y - 34, $division . ($suffix !== '' ? ' - ' . $suffix : ''), 2, 18, $blue);
            $addText($margin, $y - 52, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 9, $black);
            $y -= 70;
        };
        $drawSummary = function (array $summary) use (&$y, $margin, $addRect, $addText, $lightBlue, $blue, $black) {
            $rows = [
                ['Computer Name', (string) ($summary['computer_name'] ?? '-')], ['User', (string) ($summary['user'] ?? '-')],
                ['Processor', (string) ($summary['processor'] ?? '-')], ['RAM', (string) ($summary['ram'] ?? '-')],
                ['Harddisk', (string) ($summary['harddisk'] ?? '-')], ['IP Address', (string) ($summary['ip'] ?? '-')],
                ['Sistem Operasi', (string) ($summary['os'] ?? '-')], ['Licensed Windows', (string) ($summary['license'] ?? '-')],
                ['MS Office', (string) ($summary['office'] ?? '-')], ['Licensed Office', (string) ($summary['office_license'] ?? '-')],
            ];
            $summaryWidth = 392;
            $rowHeight = 20;
            for ($i = 0; $i < count($rows); $i += 2) {
                $left = $rows[$i];
                $right = $rows[$i + 1] ?? ['', ''];
                $boxY = $y - $rowHeight;
                $addRect($margin, $boxY, $summaryWidth, $rowHeight, $lightBlue, '0.78 0.85 0.92', 0.5);
                $addRect($margin + $summaryWidth + 6, $boxY, $summaryWidth, $rowHeight, $lightBlue, '0.78 0.85 0.92', 0.5);
                $addText($margin + 7, $boxY + 7, $left[0] . ':', 2, 8, $blue);
                $addText($margin + 105, $boxY + 7, $left[1], 1, 8, $black);
                $addText($margin + $summaryWidth + 13, $boxY + 7, $right[0] . ':', 2, 8, $blue);
                $addText($margin + $summaryWidth + 111, $boxY + 7, $right[1], 1, 8, $black);
                $y -= $rowHeight + 4;
            }
            $y -= 8;
        };
        $drawTableHeader = function () use (&$y, $columns, $margin, $addRect, $addText, $blue) {
            $headerHeight = 22;
            $rowY = $y - $headerHeight;
            $x = $margin;
            foreach ($columns as $column) {
                $addRect($x, $rowY, (float) $column['width'], $headerHeight, $blue, $blue, 0.8);
                $addText($x + 5, $rowY + 7, (string) $column['title'], 2, 7, '1 1 1');
                $x += (float) $column['width'];
            }
            $y = $rowY - 4;
        };
        $drawTable = function (array $rows, string $division) use (&$y, $pageHeight, $margin, $columns, $imageRegistry, $addRect, $addText, $addImage, $wrapText, $drawTableHeader, $finishPage, $startPage, $black, $green, $red, $border) {
            $drawTableHeader();
            if (!$rows) {
                $addText($margin, $y - 15, 'Tidak ada data inventaris untuk user ini.', 1, 9, $black);
                $y -= 30;
                return;
            }
            foreach ($rows as $row) {
                $wrapped = [];
                $maxLines = 1;
                foreach ($columns as $column) {
                    if ($column['key'] === 'image') { continue; }
                    $key = (string) $column['key'];
                    $wrapped[$key] = $wrapText((string) ($row[$key] ?? '-'), (int) ($column['chars'] ?? 12));
                    $maxLines = max($maxLines, count($wrapped[$key]));
                }
                $rowHeight = max(46, $maxLines * 10 + 12);
                if (($y - $rowHeight) < $margin) {
                    $finishPage();
                    $startPage($division, 'Lanjutan');
                    $drawTableHeader();
                }
                $rowY = $y - $rowHeight;
                $x = $margin;
                foreach ($columns as $column) {
                    $key = (string) $column['key'];
                    $fill = null;
                    if ($key === 'status') {
                        $statusValue = strtoupper(trim((string) ($row['status'] ?? '')));
                        if ($statusValue === 'AKTIF') {
                            $fill = '0.90 0.97 0.93';
                        } elseif ($statusValue === 'RUSAK') {
                            $fill = '0.99 0.91 0.90';
                        } else {
                            $fill = '1.00 0.95 0.88';
                        }
                    }
                    $addRect($x, $rowY, (float) $column['width'], $rowHeight, $fill ?: '1 1 1', $border, 0.65);
                    if ($key === 'image') {
                        $imageKey = trim((string) ($row['image'] ?? ''));
                        $img = $imageRegistry[$imageKey] ?? null;
                        if ($img) {
                            $fit = min((float) $column['width'] - 10, $rowHeight - 10);
                            $addImage((string) $img['alias'], $x + (((float) $column['width'] - $fit) / 2), $rowY + (($rowHeight - $fit) / 2), $fit, $fit);
                        } else {
                            $addText($x + 20, $rowY + ($rowHeight / 2) - 4, '-', 1, 8, $black);
                        }
                    } else {
                        if ($key === 'status') {
                            $statusValue = strtoupper(trim((string) ($row['status'] ?? '')));
                            if ($statusValue === 'AKTIF') {
                                $textColor = '0.15 0.44 0.23';
                            } elseif ($statusValue === 'RUSAK') {
                                $textColor = '0.72 0.11 0.11';
                            } else {
                                $textColor = '0.90 0.32 0.00';
                            }
                        } else {
                            $textColor = $black;
                        }
                        $font = $key === 'status' ? 2 : 1;
                        $linesCount = count($wrapped[$key]);
                        $textHeight = $linesCount * 10 - 3;
                        $lineY = $rowY + (($rowHeight - $textHeight) / 2) + $textHeight - 7;
                        foreach ($wrapped[$key] as $line) {
                            $addText($x + 5, $lineY, $line, $font, 7, $textColor);
                            $lineY -= 10;
                        }
                    }
                    $x += (float) $column['width'];
                }
                $y = $rowY - 4;
            }
            $y -= 10;
        };

        foreach ($prepared as $sheetIndex => $sheet) {
            $division = (string) ($sheet['division'] ?? 'Data Inventaris');
            $startPage($division);
            if (empty($sheet['groups'])) {
                $addText($margin, $y - 10, 'Tidak ada data inventaris pada divisi ini.', 1, 10, $black);
                $finishPage();
                continue;
            }
            foreach ($sheet['groups'] as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $finishPage();
                    $startPage($division);
                }
                $addText($margin, $y - 4, 'USER: ' . (string) (($group['summary']['user'] ?? '') ?: ($group['title'] ?? '-')), 2, 11, $blue);
                $y -= 18;
                $drawSummary((array) ($group['summary'] ?? []));
                $drawTable((array) ($group['rows'] ?? []), $division);
            }
            $finishPage();
        }

        $nextId = 3;
        foreach ($imageRegistry as $key => $img) {
            $imageRegistry[$key]['object_id'] = $nextId++;
            $objects[$imageRegistry[$key]['object_id']] = "<< /Type /XObject /Subtype /Image /Width {$img['width']} /Height {$img['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($img['data']) . " >>\nstream\n" . $img['data'] . "\nendstream";
        }
        $contentIds = [];
        $pageIds = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $contentIds[] = $contentId;
            $pageIds[] = $nextId++;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $aliases = $streamImageUsage[$index] ?? [];
            $xObjects = '';
            foreach ($aliases as $alias) {
                foreach ($imageRegistry as $img) {
                    if (($img['alias'] ?? '') === $alias) { $xObjects .= '/' . $alias . ' ' . $img['object_id'] . ' 0 R '; break; }
                }
            }
            $resourceXObjects = $xObjects !== '' ? ' /XObject << ' . trim($xObjects) . ' >>' : '';
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >>{$resourceXObjects} >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn ($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [ ' . $kids . ' ] >>';
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $size = $catalogId + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) { $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0); }
        $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }
    private function inventoryReportPdfColumns(): array
    {
        return [
            ['title' => 'No', 'key' => 'no', 'width' => 28, 'chars' => 4],
            ['title' => 'Gambar', 'key' => 'image_text', 'width' => 72, 'chars' => 12],
            ['title' => 'Divisi', 'key' => 'division', 'width' => 94, 'chars' => 18],
            ['title' => 'Tipe', 'key' => 'type', 'width' => 48, 'chars' => 8],
            ['title' => 'ID Inventaris', 'key' => 'id', 'width' => 82, 'chars' => 16],
            ['title' => 'Perangkat', 'key' => 'device', 'width' => 80, 'chars' => 15],
            ['title' => 'Merk', 'key' => 'brand', 'width' => 82, 'chars' => 16],
            ['title' => 'User', 'key' => 'user', 'width' => 88, 'chars' => 17],
            ['title' => 'Unit Kerja', 'key' => 'unit', 'width' => 85, 'chars' => 16],
            ['title' => 'Status', 'key' => 'status', 'width' => 60, 'chars' => 10],
        ];
    }

    private function flattenInventorySheetsForReport(array $sheets): array
    {
        $rows = [];
        foreach ($sheets as $sheet) {
            foreach (array_slice((array) ($sheet['rows'] ?? []), 4) as $row) {
                $rows[] = ['no' => (string) (count($rows) + 1), 'image_text' => (string) (($row[1] ?? '') !== '' ? $row[1] : '-'), 'division' => (string) ($sheet['division'] ?? '-'), 'type' => (string) ($row[3] ?? '-'), 'id' => (string) ($row[4] ?? '-'), 'device' => (string) ($row[5] ?? '-'), 'brand' => (string) ($row[6] ?? '-'), 'user' => (string) ($row[8] ?? '-'), 'unit' => (string) ($row[17] ?? '-'), 'status' => (string) ($row[18] ?? '-')];
            }
        }
        return $rows;
    }


    private function routineReportHeaders(): array
    {
        return ['No', 'Tanggal', 'Kategori', 'Nama Checking', 'Kondisi', 'Keterangan', 'Dicek Oleh', 'Update'];
    }

    private function routineReportPdfColumns(): array
    {
        return [
            ['title' => 'No', 'key' => 'no', 'width' => 28, 'chars' => 4],
            ['title' => 'Tanggal', 'key' => 'tanggal', 'width' => 70, 'chars' => 11],
            ['title' => 'Kategori', 'key' => 'kategori', 'width' => 70, 'chars' => 12],
            ['title' => 'Nama Checking', 'key' => 'checking', 'width' => 155, 'chars' => 26],
            ['title' => 'Kondisi', 'key' => 'kondisi', 'width' => 88, 'chars' => 14],
            ['title' => 'Keterangan', 'key' => 'keterangan', 'width' => 210, 'chars' => 36],
            ['title' => 'Dicek Oleh', 'key' => 'checked_by', 'width' => 100, 'chars' => 18],
            ['title' => 'Update', 'key' => 'update', 'width' => 75, 'chars' => 12],
        ];
    }

    private function userReportHeaders(): array
    {
        return ['No', 'Nama', 'Username', 'Email', 'Role', 'Divisi', 'Unit Kerja', 'Status', 'Tanggal Daftar', 'Login Terakhir'];
    }

    private function userReportPdfColumns(): array
    {
        return [
            ['title' => 'No', 'key' => 'no', 'width' => 28, 'chars' => 4],
            ['title' => 'Nama', 'key' => 'nama', 'width' => 130, 'chars' => 22],
            ['title' => 'Username', 'key' => 'username', 'width' => 90, 'chars' => 16],
            ['title' => 'Email', 'key' => 'email', 'width' => 160, 'chars' => 28],
            ['title' => 'Role', 'key' => 'role', 'width' => 62, 'chars' => 10],
            ['title' => 'Divisi', 'key' => 'divisi', 'width' => 115, 'chars' => 20],
            ['title' => 'Unit Kerja', 'key' => 'unit', 'width' => 120, 'chars' => 20],
            ['title' => 'Status', 'key' => 'status', 'width' => 62, 'chars' => 10],
            ['title' => 'Daftar', 'key' => 'created_at', 'width' => 75, 'chars' => 12],
            ['title' => 'Login', 'key' => 'last_login', 'width' => 75, 'chars' => 12],
        ];
    }

    private function routineReportRowsForOutput(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [(string) ($row['no'] ?? ''), (string) ($row['tanggal'] ?? '-'), (string) ($row['kategori'] ?? '-'), (string) ($row['checking'] ?? '-'), (string) ($row['kondisi'] ?? '-'), (string) ($row['keterangan'] ?? '-'), (string) ($row['checked_by'] ?? '-'), (string) ($row['update'] ?? '-')];
        }, $rows);
    }

    private function userReportRowsForOutput(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [(string) ($row['no'] ?? ''), (string) ($row['nama'] ?? '-'), (string) ($row['username'] ?? '-'), (string) ($row['email'] ?? '-'), (string) ($row['role'] ?? '-'), (string) ($row['divisi'] ?? '-'), (string) ($row['unit'] ?? '-'), (string) ($row['status'] ?? '-'), (string) ($row['created_at'] ?? '-'), (string) ($row['last_login'] ?? '-')];
        }, $rows);
    }

    private function buildUserReportXlsx(string $title, array $rows): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan User');

        // Default font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Column widths: No | Nama | Username | Email | Role | Divisi | Unit Kerja | Status | Tgl Daftar | Login Terakhir
        $colWidths = ['A' => 5, 'B' => 28, 'C' => 18, 'D' => 32, 'E' => 12, 'F' => 22, 'G' => 28, 'H' => 14, 'I' => 18, 'J' => 18];
        foreach ($colWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Row 1: Title
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'name' => 'Arial', 'color' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Row 2: Export date
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Diexport: ' . date('d-m-Y H:i:s'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '595959']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Row 3: Spacer
        $sheet->getRowDimension(3)->setRowHeight(6);

        // Row 4: Header
        $headers = ['No', 'Nama Lengkap', 'Username', 'Email', 'Role', 'Divisi', 'Unit Kerja', 'Status', 'Tgl Daftar', 'Login Terakhir'];
        $hCols   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($hCols as $idx => $col) {
            $sheet->setCellValue($col . '4', $headers[$idx]);
        }
        $sheet->getStyle('A4:J4')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(24);
        $sheet->freezePane('A5');

        // Group by Role
        $groupedRows   = [];
        foreach ($rows as $row) {
            $role = strtoupper(trim((string)($row['role'] ?? 'LAINNYA')));
            $groupedRows[$role][] = $row;
        }
        $roleOrder = ['ADMIN', 'USER'];
        foreach (array_keys($groupedRows) as $r) {
            if (!in_array($r, $roleOrder, true)) { $roleOrder[] = $r; }
        }

        // Role color map
        $roleColors = [
            'ADMIN'   => ['bg' => '1F3864', 'fg' => 'FFFFFF'],
            'USER'    => ['bg' => '1A5276', 'fg' => 'FFFFFF'],
        ];
        $defaultRoleColor = ['bg' => '2E4053', 'fg' => 'FFFFFF'];

        $baris    = 5;
        $globalNo = 1;

        foreach ($roleOrder as $role) {
            if (!isset($groupedRows[$role]) || empty($groupedRows[$role])) { continue; }
            $roleRows  = $groupedRows[$role];
            $roleMeta  = $roleColors[$role] ?? $defaultRoleColor;
            $roleCount = count($roleRows);

            // Role separator row
            $sheet->mergeCells('A' . $baris . ':J' . $baris);
            $sheet->setCellValue('A' . $baris, '  ' . $role . '   (' . $roleCount . ' akun)');
            $sheet->getStyle('A' . $baris . ':J' . $baris)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['rgb' => $roleMeta['fg']]],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $roleMeta['bg']]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders'   => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $roleMeta['bg']]], 'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $roleMeta['bg']]]],
            ]);
            $sheet->getRowDimension($baris)->setRowHeight(22);
            $baris++;

            $roleDataNo = 1;
            foreach ($roleRows as $row) {
                $isEven  = ($roleDataNo % 2 === 0);
                $rowFill = $isEven ? 'EEF4FB' : 'FFFFFF';

                $sheet->setCellValue('A' . $baris, $globalNo);
                $sheet->setCellValue('B' . $baris, (string)($row['nama'] ?? '-'));
                $sheet->setCellValue('C' . $baris, (string)($row['username'] ?? '-'));
                $sheet->setCellValue('D' . $baris, (string)($row['email'] ?? '-'));
                $sheet->setCellValue('E' . $baris, strtoupper((string)($row['role'] ?? '-')));
                $sheet->setCellValue('F' . $baris, (string)($row['divisi'] ?? '-'));
                $sheet->setCellValue('G' . $baris, (string)($row['unit'] ?? '-'));
                $sheet->setCellValue('H' . $baris, (string)($row['status'] ?? '-'));
                $sheet->setCellValue('I' . $baris, (string)($row['created_at'] ?? '-'));
                $sheet->setCellValue('J' . $baris, (string)($row['last_login'] ?? '-'));

                // Base style
                $sheet->getStyle('A' . $baris . ':J' . $baris)->applyFromArray([
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowFill]],
                    'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);

                // Center: No, Role, Status
                foreach (['A', 'E', 'H', 'I', 'J'] as $cCol) {
                    $sheet->getStyle($cCol . $baris)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                // Left: Nama, Username, Email, Divisi, Unit
                foreach (['B', 'C', 'D', 'F', 'G'] as $lCol) {
                    $sheet->getStyle($lCol . $baris)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                }

                // Status color (H)
                $statusVal = strtolower(trim((string)($row['status'] ?? '')));
                if (in_array($statusVal, ['aktif', 'active'], true)) {
                    $sheet->getStyle('H' . $baris)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                    ]);
                } else {
                    $sheet->getStyle('H' . $baris)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                    ]);
                }

                // Role color (E)
                $roleVal = strtoupper(trim((string)($row['role'] ?? '')));
                if ($roleVal === 'ADMIN') {
                    $sheet->getStyle('E' . $baris)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6EAF8']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '1A5276']],
                    ]);
                } else {
                    $sheet->getStyle('E' . $baris)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAFAF1']],
                        'font' => ['bold' => false, 'color' => ['rgb' => '1E8449']],
                    ]);
                }

                $sheet->getRowDimension($baris)->setRowHeight(20);
                $baris++;
                $roleDataNo++;
                $globalNo++;
            }

            // Spacer
            $sheet->getRowDimension($baris)->setRowHeight(8);
            $baris++;
        }

        return $spreadsheet;
    }

    private function buildUserReportPdf(string $title, array $rows): string
    {
        $pageWidth  = 842; $pageHeight = 595; $margin = 22;
        $navyBg  = '0.12 0.22 0.39'; $white = '1 1 1';
        $textMain = '0.12 0.15 0.20'; $textGray = '0.35 0.38 0.42';
        $altFill  = '0.93 0.96 0.98'; $border = '0.83 0.83 0.83';

        $roleColors = [
            'ADMIN' => '0.08 0.16 0.31',
            'USER'  => '0.10 0.32 0.47',
        ];
        $defaultRoleColor = '0.18 0.25 0.31';

        $columns = [
            ['title' => 'No',            'key' => 'no',          'width' => 20,  'align' => 'center', 'chars' => 4],
            ['title' => 'Nama Lengkap',  'key' => 'nama',        'width' => 130, 'align' => 'left',   'chars' => 22],
            ['title' => 'Username',      'key' => 'username',    'width' => 88,  'align' => 'left',   'chars' => 14],
            ['title' => 'Email',         'key' => 'email',       'width' => 150, 'align' => 'left',   'chars' => 25],
            ['title' => 'Role',          'key' => 'role',        'width' => 50,  'align' => 'center', 'chars' => 8],
            ['title' => 'Divisi',        'key' => 'divisi',      'width' => 100, 'align' => 'left',   'chars' => 17],
            ['title' => 'Unit Kerja',    'key' => 'unit',        'width' => 105, 'align' => 'left',   'chars' => 17],
            ['title' => 'Status',        'key' => 'status',      'width' => 58,  'align' => 'center', 'chars' => 9],
            ['title' => 'Tgl Daftar',    'key' => 'created_at',  'width' => 62,  'align' => 'center', 'chars' => 10],
            ['title' => 'Login Terakhir','key' => 'last_login',  'width' => 72,  'align' => 'center', 'chars' => 11],
        ];
        $tableWidth = array_sum(array_column($columns, 'width'));

        $objects = [
            1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica      /Encoding /WinAnsiEncoding >>',
            2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $streams = []; $current = []; $y = $pageHeight - $margin;

        $escape = function (string $text): string {
            $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
            if (function_exists('iconv')) { $c = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text); if ($c !== false && $c !== '') { $text = $c; } }
            $text = preg_replace('/[^ -~\x80-\xFF]/', '', $text) ?? $text;
            return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text === '' ? '-' : $text);
        };
        $addText = function (float $x, float $yPos, string $text, int $fontId = 1, int $fontSize = 7, string $color = '0 0 0') use (&$current, $escape) {
            $current[] = 'BT'; $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize); $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yPos); $current[] = '(' . $escape($text) . ') Tj'; $current[] = 'ET';
        };
        $addRect = function (float $x, float $yPos, float $w, float $h, ?string $fill = null, string $stroke = '0 0 0', float $lineWidth = 0.5) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            $current[] = $stroke . ' RG'; $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yPos, $w, $h, $fill !== null ? 'B' : 'S');
        };
        $wrap = function (string $text, int $maxChars): array {
            $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
            if ($text === '') { return ['-']; }
            $words = preg_split('/\s+/', $text) ?: [$text]; $lines = []; $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (strlen($candidate) <= $maxChars) { $line = $candidate; continue; }
                if ($line !== '') { $lines[] = $line; }
                while (strlen($word) > $maxChars) { $lines[] = substr($word, 0, $maxChars); $word = substr($word, $maxChars); }
                $line = $word;
            }
            if ($line !== '') { $lines[] = $line; }
            return array_slice($lines ?: ['-'], 0, 3);
        };
        $finishPage = function () use (&$streams, &$current) { if ($current) { $streams[] = implode("\n", $current); } $current = []; };
        $startPage = function () use (&$current, &$y, $pageHeight, $margin, $title, $addText, $navyBg, $textGray) {
            $current = []; $y = $pageHeight - $margin;
            $addText($margin, $y - 5, $title, 2, 13, $navyBg);
            $addText($margin, $y - 19, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 7, $textGray);
            $y -= 34;
        };
        $drawColumnHeader = function () use (&$y, $columns, $margin, $addRect, $addText, $navyBg) {
            $x = $margin; $h = 18; $rowY = $y - $h;
            foreach ($columns as $col) {
                $addRect($x, $rowY, (float)$col['width'], $h, $navyBg, $navyBg, 0.6);
                $cx = ($col['align'] ?? 'left') === 'center' ? $x + ((float)$col['width'] / 2) - (strlen($col['title']) * 2) : $x + 3;
                $addText($cx, $rowY + 6, $col['title'], 2, 6, '1 1 1');
                $x += (float)$col['width'];
            }
            $y = $rowY - 2;
        };
        $drawRoleBand = function (string $role, int $count, string $color) use (&$y, $margin, $addRect, $addText, $tableWidth) {
            $h = 16; $rowY = $y - $h;
            $addRect($margin, $rowY, (float)$tableWidth, $h, $color, $color, 0.3);
            $addText($margin + 5, $rowY + 5, $role . '   (' . $count . ' akun)', 2, 8, '1 1 1');
            $y = $rowY - 2;
        };

        // Group by role
        $groupedRows = [];
        foreach ($rows as $row) { $r = strtoupper(trim((string)($row['role'] ?? 'LAINNYA'))); $groupedRows[$r][] = $row; }
        $roleOrder = ['ADMIN', 'USER'];
        foreach (array_keys($groupedRows) as $r) { if (!in_array($r, $roleOrder, true)) { $roleOrder[] = $r; } }

        $startPage();
        $drawColumnHeader();
        $globalNo = 1;

        foreach ($roleOrder as $role) {
            if (!isset($groupedRows[$role]) || empty($groupedRows[$role])) { continue; }
            $roleRows  = $groupedRows[$role];
            $roleCount = count($roleRows);
            $roleColor = $roleColors[$role] ?? $defaultRoleColor;

            if (($y - 20) < $margin) { $finishPage(); $startPage(); $drawColumnHeader(); }
            $drawRoleBand($role, $roleCount, $roleColor);

            $roleDataNo = 1;
            foreach ($roleRows as $row) {
                $wrapped = []; $maxLines = 1;
                foreach ($columns as $col) {
                    $key = (string)$col['key'];
                    $val = $key === 'no' ? (string)$globalNo : ($key === 'role' ? strtoupper((string)($row[$key] ?? '-')) : (string)($row[$key] ?? '-'));
                    $wrapped[$key] = $wrap($val, (int)($col['chars'] ?? 12));
                    $maxLines = max($maxLines, count($wrapped[$key]));
                }
                $rowHeight = max(18, $maxLines * 9 + 6);

                if (($y - $rowHeight) < $margin) { $finishPage(); $startPage(); $drawColumnHeader(); }
                $rowY = $y - $rowHeight; $x = $margin;
                $isEven = ($roleDataNo % 2 === 0);

                // Status + role colors
                $statusUpper = strtolower(trim((string)($row['status'] ?? '')));
                $statusFill  = in_array($statusUpper, ['aktif', 'active'], true) ? '0.83 0.93 0.85' : '1 0.95 0.80';
                $statusTxt   = in_array($statusUpper, ['aktif', 'active'], true) ? '0.08 0.34 0.14' : '0.52 0.39 0.02';
                $roleUpper   = strtoupper(trim((string)($row['role'] ?? '')));
                $roleFill    = $roleUpper === 'ADMIN' ? '0.84 0.91 0.97' : '0.91 0.97 0.95';
                $roleTxt     = $roleUpper === 'ADMIN' ? '0.10 0.32 0.47' : '0.12 0.52 0.29';

                foreach ($columns as $col) {
                    $key = (string)$col['key']; $cw = (float)$col['width'];
                    if ($key === 'status')      { $fill = $statusFill; }
                    elseif ($key === 'role')    { $fill = $roleFill; }
                    else                         { $fill = $isEven ? $altFill : $white; }
                    $addRect($x, $rowY, $cw, $rowHeight, $fill, $border, 0.35);

                    $linesArr = $wrapped[$key]; $nLines = count($linesArr);
                    $textBlock = $nLines * 9 - 3;
                    $lineY = $rowY + (($rowHeight - $textBlock) / 2) + $textBlock - 6;
                    $fontId = ($key === 'status' || $key === 'role') ? 2 : 1;
                    $txtCol = $key === 'status' ? $statusTxt : ($key === 'role' ? $roleTxt : $textMain);

                    foreach ($linesArr as $line) {
                        $textX = ($col['align'] ?? 'left') === 'center' ? $x + ($cw / 2) - (strlen($line) * 1.9) : $x + 3;
                        $addText($textX, $lineY, $line, $fontId, 6, $txtCol);
                        $lineY -= 9;
                    }
                    $x += $cw;
                }
                $y = $rowY - 1; $roleDataNo++; $globalNo++;
            }
            $y -= 6;
        }

        if (!$rows) { $addText($margin, $y - 18, 'Tidak ada data.', 1, 9, $textMain); }
        $finishPage();

        // Assemble PDF
        $nextId = 3; $contentIds = []; $pageIds = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++; $pageId = $nextId++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[$pageId] = ''; $contentIds[] = $contentId; $pageIds[] = $pageId;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [ ' . $kids . ' ] >>';
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        ksort($objects);
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $id => $object) { $offsets[$id] = strlen($pdf); $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n"; }
        $xref = strlen($pdf); $size = $catalogId + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) { $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0); }
        $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function buildGenericLaporanXlsx(string $title, array $headers, array $rows, array $centerCols = []): string
    {
        $sheetRows = [[$title], ['Diexport', date('d-m-Y H:i:s')], [], $headers];
        foreach ($rows as $row) { $sheetRows[] = $row; }
        return $this->buildMultiSheetXlsx([[
            'name' => $this->safeExcelSheetName($title),
            'rows' => $sheetRows,
            'widths' => array_fill(0, max(1, count($headers)), 18),
            'center_cols' => $centerCols,
        ]]);
    }

    private function fetchRoutineMonitoringReportRows(PDO $pdo, array $filters = []): array
    {
        $this->ensureRoutineMonitoringTable($pdo);
        $f = $this->buildLaporanFilters($filters);
        $where = [];
        $params = [];
        if (empty($f['all'])) {
            $where[] = 'YEAR(rm.monitor_date) = :year';
            $params['year'] = (int) $f['year'];
            if ($f['month'] !== 'all') {
                $where[] = 'MONTH(rm.monitor_date) = :month';
                $params['month'] = (int) $f['month'];
            }
        }
        $category = trim((string) ($f['category'] ?? ''));
        if ($category !== '') {
            $where[] = 'UPPER(COALESCE(NULLIF(ri.category_field, ""), NULLIF(ri.item_group, ""), "")) = UPPER(:category)';
            $params['category'] = $category;
        }
        $sql = 'SELECT rm.monitor_date, COALESCE(NULLIF(ri.category_field, ""), NULLIF(ri.item_group, ""), "-") AS kategori, rm.item_name, rm.condition_status, rm.keterangan, rm.checked_by_name, rm.updated_at FROM routine_monitoring rm LEFT JOIN routine_monitoring_items ri ON ri.id = rm.item_id ' . ($where ? ('WHERE ' . implode(' AND ', $where)) : '') . ' ORDER BY rm.monitor_date ASC, kategori ASC, rm.item_name ASC';
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rawRows = $stmt->fetchAll();
        } catch (Throwable $e) {
            $fallbackWhere = $where;
            if ($category !== '') {
                // If the join fails, we fallback to filtering item_name
                // because category won't be available without the items table join
                array_pop($fallbackWhere);
                $fallbackWhere[] = 'UPPER(rm.item_name) LIKE :cat_like';
                $params['cat_like'] = '%' . strtoupper($category) . '%';
                unset($params['category']);
            }
            $stmt = $pdo->prepare('SELECT monitor_date, "-" AS kategori, item_name, condition_status, keterangan, checked_by_name, updated_at FROM routine_monitoring rm ' . ($fallbackWhere ? ('WHERE ' . implode(' AND ', $fallbackWhere)) : '') . ' ORDER BY monitor_date ASC, item_name ASC');
            $stmt->execute($params);
            $rawRows = $stmt->fetchAll();
        }
        $rows = [];
        $no = 1;
        foreach ($rawRows as $row) {
            $rows[] = [
                'no' => (string) $no++,
                'tanggal' => date('d/m/Y', strtotime((string) ($row['monitor_date'] ?? 'now'))),
                'kategori' => strtoupper((string) (($row['kategori'] ?? '') ?: '-')),
                'checking' => (string) (($row['item_name'] ?? '') ?: '-'),
                'kondisi' => ucwords(strtolower((string) (($row['condition_status'] ?? '') ?: '-'))),
                'keterangan' => (string) (($row['keterangan'] ?? '') ?: '-'),
                'checked_by' => (string) (($row['checked_by_name'] ?? '') ?: '-'),
                'update' => (string) (($row['updated_at'] ?? '') ?: '-'),
            ];
        }
        return $rows;
    }

    private function fetchUserReportRows(PDO $pdo, array $filters = []): array
    {
        $f = $this->buildLaporanFilters($filters);
        $role = strtolower(trim((string) $f['user_role']));
        $division = trim((string) $f['user_division']);
        
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $pageDivision = trim((string) ($filters['division'] ?? ''));

        $where = [];
        $params = [];
        if (in_array($role, ['admin', 'user'], true)) {
            $where[] = 'LOWER(u.role) = :role';
            $params['role'] = $role;
        }
        if ($division !== '') {
            $where[] = '(CAST(u.default_divisi_id AS CHAR) = :division_exact OR LOWER(COALESCE(md.division_label, "")) LIKE :division_like OR LOWER(COALESCE(md.division_code, "")) LIKE :division_like OR LOWER(COALESCE(u.unit_kerja_default, "")) LIKE :division_like)';
            $params['division_exact'] = $division;
            $params['division_like'] = '%' . strtolower($division) . '%';
        }
        if ($search !== '') {
            $where[] = '(LOWER(u.nama_lengkap) LIKE :search OR LOWER(u.email) LIKE :search OR LOWER(u.username) LIKE :search OR LOWER(COALESCE(md.division_label, "")) LIKE :search OR LOWER(u.unit_kerja_default) LIKE :search OR LOWER(u.role) LIKE :search)';
            $params['search'] = '%' . strtolower($search) . '%';
        }
        if ($status === 'active') {
            $where[] = 'u.is_active = 1';
        } elseif ($status === 'pending') {
            $where[] = 'u.is_active = 0';
        }
        if ($pageDivision !== '' && $pageDivision !== 'all') {
            $where[] = '(LOWER(COALESCE(md.division_label, "")) = :page_div OR LOWER(u.unit_kerja_default) = :page_div)';
            $params['page_div'] = strtolower($pageDivision);
        }

        $sql = 'SELECT u.id, u.username, u.nama_lengkap, u.email, u.role, u.is_active, u.unit_kerja_default, u.created_at, u.last_login_at, COALESCE(NULLIF(md.division_label, ""), NULLIF(md.division_code, ""), "-") AS divisi FROM users u LEFT JOIN master_divisi md ON md.id = u.default_divisi_id ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY u.role ASC, u.nama_lengkap ASC, u.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = [];
        $no = 1;
        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            $rows[] = [
                'no' => (string) $no++,
                'nama' => (string) (($row['nama_lengkap'] ?? '') ?: '-'),
                'username' => (string) (($row['username'] ?? '') ?: '-'),
                'email' => (string) (($row['email'] ?? '') ?: '-'),
                'role' => strtoupper((string) (($row['role'] ?? '') ?: '-')),
                'divisi' => (string) (($row['divisi'] ?? '') ?: '-'),
                'unit' => (string) (($row['unit_kerja_default'] ?? '') ?: '-'),
                'status' => ((int) ($row['is_active'] ?? 0) === 1) ? 'Aktif' : 'Belum Aktif',
                'created_at' => (string) (($row['created_at'] ?? '') ?: '-'),
                'last_login' => (string) (($row['last_login_at'] ?? '') ?: '-'),
            ];
        }
        return $rows;
    }

    private function buildLaporanFilters(array $filters): array
    {
        $month = trim((string) ($filters['report_month'] ?? 'all'));
        if ($month === '') { $month = 'all'; }
        if ($month !== 'all') {
            $m = (int) $month;
            $month = ($m >= 1 && $m <= 12) ? str_pad((string) $m, 2, '0', STR_PAD_LEFT) : 'all';
        }
        $year = (int) ($filters['report_year'] ?? date('Y'));
        if ($year < 2020 || $year > 2100) { $year = (int) date('Y'); }
        return [
            'date_from' => trim((string) ($filters['report_date_from'] ?? '')),
            'date_to' => trim((string) ($filters['report_date_to'] ?? '')),
            'division' => trim((string) ($filters['report_division'] ?? '')),
            'month' => $month,
            'year' => (string) $year,
            'user_role' => trim((string) ($filters['report_user_role'] ?? '')),
            'user_division' => trim((string) ($filters['report_user_division'] ?? '')),
            'all' => trim((string) ($filters['report_all'] ?? '')) === '1',
            'category' => trim((string) ($filters['report_category'] ?? '')),
        ];
    }

    private function buildLaporanTitleSuffix(array $filters, string $type = ''): string
    {
        $f = $this->buildLaporanFilters($filters);
        $type = $this->normalizeLaporanType($type);
        $parts = [];

        if (in_array($type, ['inventory', 'complaint', 'log'], true)) {
            if ($f['date_from'] !== '' || $f['date_to'] !== '') {
                $parts[] = 'Tanggal ' . ($f['date_from'] ?: 'awal') . ' s.d. ' . ($f['date_to'] ?: 'sekarang');
            }
            if ($f['division'] !== '') {
                $parts[] = 'Divisi ' . $f['division'];
            }
        } elseif ($type === 'routine') {
            if (!empty($f['all'])) {
                $parts[] = 'Semua Data';
            } elseif ($f['month'] !== 'all') {
                $parts[] = 'Bulan ' . $f['month'] . '/' . $f['year'];
            } else {
                $parts[] = 'Semua Bulan Tahun ' . $f['year'];
            }
            if ($f['category'] !== '') {
                $parts[] = 'Kategori ' . strtoupper($f['category']);
            }
        } elseif ($type === 'user') {
            if ($f['user_role'] !== '') {
                $parts[] = 'Role ' . strtoupper($f['user_role']);
            }
            if ($f['user_division'] !== '') {
                $parts[] = 'Divisi ' . $f['user_division'];
            }
        }
        return $parts ? (' - ' . implode(' - ', $parts)) : ' - Semua Data';
    }

    private function laporanDateBounds(array $filters): array
    {
        $f = $this->buildLaporanFilters($filters);
        return [$f['date_from'], $f['date_to']];
    }

    private function fetchReportDivisionOptions(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("SELECT division_code, division_label, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY CASE WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%spmt%' THEN 0 WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%subreg%' THEN 1 ELSE 2 END ASC, division_label ASC");
            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) { return []; }
    }

    private function normalizeReportDivisionKey(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?: '';
        return trim($value, '_');
    }

    private function reportDivisionMatches(array $row, string $value): bool
    {
        $value = trim($value);
        if ($value === '') { return true; }
        $needle = $this->normalizeReportDivisionKey($value);
        foreach (['division_code', 'division_label', 'division_group_name', 'inventory_db_name'] as $key) {
            $raw = trim((string) ($row[$key] ?? ''));
            if ($raw === '') { continue; }
            if (strcasecmp($raw, $value) === 0) { return true; }
            $normalized = $this->normalizeReportDivisionKey($raw);
            if ($normalized === $needle) { return true; }
            $normalizedNoDb = preg_replace('/^DB_/', '', $normalized) ?: $normalized;
            if ($normalizedNoDb === $needle) { return true; }
        }
        return false;
    }

    private function reportDivisionAliases(PDO $pdo, string $value): array
    {
        $value = trim($value);
        if ($value === '') { return []; }
        $aliases = [$value];
        try {
            $stmt = $pdo->query('SELECT division_code, division_label, division_group_name, inventory_db_name FROM master_divisi WHERE is_active = 1');
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                if (!$this->reportDivisionMatches($row, $value)) { continue; }
                foreach (['division_code', 'division_label', 'division_group_name', 'inventory_db_name'] as $key) {
                    $v = trim((string) ($row[$key] ?? ''));
                    if ($v !== '') { $aliases[] = $v; }
                }
            }
        } catch (Throwable $e) {}
        return array_values(array_unique($aliases));
    }

    private function reportTableDateColumn(PDO $pdo, string $db, string $table): string
    {
        if (!$this->isSafeIdentifier($db) || !$this->isSafeIdentifier($table)) { return ''; }
        try {
            $stmt = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`.`%s`', $db, $table));
            $cols = [];
            foreach (($stmt ? $stmt->fetchAll() : []) as $col) { $cols[strtolower((string) ($col['Field'] ?? ''))] = true; }
            foreach (['tanggal', 'created_at', 'updated_at', 'last_edited_at', 'sync_at'] as $candidate) { if (isset($cols[$candidate])) { return $candidate; } }
        } catch (Throwable $e) {}
        return '';
    }

    private function fetchAllInventoryReportSheets(PDO $pdo, array $filters = []): array
    {
        $f = $this->buildLaporanFilters($filters); [$from, $to] = $this->laporanDateBounds($filters);
        try {
            $sql = "SELECT division_code, division_label, division_group_name, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY CASE WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%spmt%' THEN 0 WHEN LOWER(CONCAT(division_code, ' ', division_label, ' ', inventory_db_name)) LIKE '%subreg%' THEN 1 ELSE 2 END ASC, division_label ASC, id ASC";
            $stmt = $pdo->query($sql);
            $divisions = $stmt ? $stmt->fetchAll() : [];
            if ($f['division'] !== '') {
                $selectedDivision = $f['division'];
                $divisions = array_values(array_filter($divisions, function (array $row) use ($selectedDivision): bool {
                    return $this->reportDivisionMatches($row, $selectedDivision);
                }));
            }
        } catch (Throwable $e) { $divisions = []; }
        $sheets = [];
        foreach ($divisions as $division) {
            $db = (string) ($division['inventory_db_name'] ?? ''); if (!$this->isSafeIdentifier($db)) { continue; }
            $label = (string) ($division['division_label'] ?? $db);
            $sheetRows = [['Laporan Data Inventaris - ' . $label], ['Divisi', $label, 'Diexport', date('d-m-Y H:i:s')], [], ['No', 'Gambar', 'Divisi', 'Tipe', 'ID Inventaris', 'Jenis Perangkat', 'Merk Perangkat', 'Computer Name', 'User', 'Processor', 'RAM', 'Harddisk', 'IP Address', 'Sistem Operasi', 'Licensed Windows', 'MS Office', 'Licensed Office', 'Unit Kerja', 'Status']];
            $no = 1;
            foreach (['pc' => 'PC', 'perangkat_lain' => 'Perangkat Lain'] as $table => $typeLabel) {
                try {
                    $dateCol = $this->reportTableDateColumn($pdo, $db, $table); $where = []; $params = [];
                    if ($dateCol !== '') { if ($from !== '') { $where[] = "DATE(`$dateCol`) >= ?"; $params[] = $from; } if ($to !== '') { $where[] = "DATE(`$dateCol`) <= ?"; $params[] = $to; } }
                    $order = $table === 'pc' ? '`user` ASC, id_inventaris ASC' : '`user` ASC, jenis_perangkat ASC, id_inventaris ASC';
                    $sql = sprintf('SELECT * FROM `%s`.`%s` %s ORDER BY %s', $db, $table, $where ? ('WHERE ' . implode(' AND ', $where)) : '', $order);
                    $stmt = $pdo->prepare($sql); $stmt->execute($params);
                    foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                        if ($table === 'pc') {
                            $image = $this->normalizeReportAssetPath((string) ($row['gambar'] ?? ''));
                            $pcBrand = (string) (($row['merk_perangkat'] ?? '') ?: (((string) ($row['jenis_perangkat'] ?? '')) !== 'PC' ? ($row['jenis_perangkat'] ?? '') : '-'));
                            $pcStatus = strtoupper(trim((string) ($row['status'] ?? 'AKTIF'))); if (!in_array($pcStatus, ['AKTIF', 'RUSAK'], true)) { $pcStatus = 'AKTIF'; }
                            $sheetRows[] = [(string) $no++, $image, $label, $typeLabel, (string) ($row['id_inventaris'] ?? '-'), 'PC', $pcBrand !== '' ? $pcBrand : '-', (string) ($row['computer_name'] ?? '-'), (string) ($row['user'] ?? '-'), (string) ($row['processor'] ?? '-'), (string) ($row['ram'] ?? '-'), (string) ($row['kapasitas_harddisk'] ?? '-'), (string) ($row['ip_address'] ?? '-'), (string) ($row['sistem_operasi'] ?? '-'), (string) ($row['licensed_windows'] ?? '-'), (string) ($row['microsoft_office'] ?? '-'), (string) ($row['licensed_office'] ?? '-'), (string) ($row['unit_kerja'] ?? '-'), $pcStatus];
                        } else {
                            $image = $this->normalizeReportAssetPath((string) ($row['gambar'] ?? ''));
                            $sheetRows[] = [(string) $no++, $image, $label, $typeLabel, (string) ($row['id_inventaris'] ?? '-'), (string) ($row['jenis_perangkat'] ?? '-'), (string) ($row['merk_perangkat'] ?? '-'), '-', (string) ($row['user'] ?? '-'), '-', '-', '-', '-', '-', '-', '-', '-', (string) ($row['unit_kerja'] ?? '-'), (string) ($row['status'] ?? '-')];
                        }
                    }
                } catch (Throwable $e) {}
            }
            $sheets[] = ['name' => $this->safeExcelSheetName($label ?: $db), 'division' => $label ?: $db, 'db' => $db, 'rows' => $sheetRows, 'widths' => [6, 20, 28, 14, 18, 22, 22, 22, 24, 22, 14, 18, 18, 24, 18, 22, 18, 24, 16]];
        }
        if (!$sheets) { $sheets[] = ['name' => 'Inventaris', 'division' => 'Data Inventaris', 'db' => '-', 'rows' => [['Laporan Data Inventaris'], ['Diexport', date('d-m-Y H:i:s')], [], ['No', 'Gambar', 'Divisi', 'Tipe', 'ID Inventaris', 'Jenis Perangkat', 'Merk Perangkat', 'Computer Name', 'User', 'Processor', 'RAM', 'Harddisk', 'IP Address', 'Sistem Operasi', 'Licensed Windows', 'MS Office', 'Licensed Office', 'Unit Kerja', 'Status']], 'widths' => [6, 20, 28, 14, 18, 22, 22, 22, 24, 22, 14, 18, 18, 24, 18, 22, 18, 24, 16]]; }
        return $sheets;
    }

    private function fetchAllComplaintReportRows(PDO $pdo, array $filters = []): array
    {
        try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_pelapor"); } catch (Throwable $e) {}
        $this->ensureComplaintEmailNotificationColumns($pdo);
        try { $pdo->exec("UPDATE it_support_request SET status = 'NOT YET' WHERE status IS NULL OR TRIM(CAST(status AS CHAR)) = '' OR TRIM(CAST(status AS CHAR)) = '0'"); } catch (Throwable $e) {}
        try {
            $f = $this->buildLaporanFilters($filters); [$from, $to] = $this->laporanDateBounds($filters); $where = []; $params = [];
            if ($from !== '') { $where[] = 'r.tanggal >= ?'; $params[] = $from; }
            if ($to !== '') { $where[] = 'r.tanggal <= ?'; $params[] = $to; }
            if ($f['division'] !== '') { $aliases = $this->reportDivisionAliases($pdo, $f['division']); $where[] = 'r.divisi IN (' . implode(',', array_fill(0, count($aliases), '?')) . ')'; foreach ($aliases as $alias) { $params[] = $alias; } }
            $sql = 'SELECT r.id, r.ticket_no, r.tanggal AS tanggal_raw, r.jam AS jam_raw, CONCAT(DATE_FORMAT(r.tanggal, "%d %M %Y"), " ", TIME_FORMAT(r.jam, "%H:%i:%s")) AS tanggal_dan_jam, r.email_pelapor AS email, r.nama_pelapor AS nama, r.divisi, r.aset_yang_perlu_diperbaiki AS barang, r.lokasi_perbaikan AS lokasi, r.deskripsi_kerusakan AS deskripsi, r.dokumentasi_kerusakan AS dokumentasi, r.status, r.catatan_penanganan, r.handled_by_user_id, COALESCE(NULLIF(u.nama_lengkap, ""), NULLIF(u.username, ""), NULLIF(u.email, ""), "") AS handled_by_name FROM it_support_request r LEFT JOIN users u ON u.id = r.handled_by_user_id ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY r.tanggal DESC, r.jam DESC, r.id DESC';
            $stmt = $pdo->prepare($sql); $stmt->execute($params); $out = [];
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) { $status = strtoupper((string) ($row['status'] ?? 'NOT YET')); $email = (string) ($row['email'] ?? ''); $out[] = ['id' => (int) ($row['id'] ?? 0), 'ticket_no' => (string) ($row['ticket_no'] ?? '-'), 'datetime' => (string) ($row['tanggal_dan_jam'] ?? '-'), 'datetime_plain' => (string) ($row['tanggal_dan_jam'] ?? '-'), 'email' => $email, 'email_plain' => $email, 'name' => (string) ($row['nama'] ?? '-'), 'division' => (string) ($row['divisi'] ?? '-'), 'item' => (string) ($row['barang'] ?? '-'), 'location' => (string) ($row['lokasi'] ?? '-'), 'description' => (string) ($row['deskripsi'] ?? '-'), 'doc_image' => $this->normalizeReportAssetPath((string) ($row['dokumentasi'] ?? '')), 'status' => $status, 'status_class' => $status === 'DONE' ? 'good' : ($status === 'ON PROGRESS' ? 'progress' : 'bad'), 'catatan_penanganan' => (string) ($row['catatan_penanganan'] ?? ''), 'handled_by_user_id' => (int) ($row['handled_by_user_id'] ?? 0), 'handled_by_name' => (string) ($row['handled_by_name'] ?? '')]; }
            return $out;
        } catch (Throwable $e) { return []; }
    }

    private function fetchAllLogReportRows(PDO $pdo, array $filters = []): array
    {
        try {
            $f = $this->buildLaporanFilters($filters); 
            [$from, $to] = $this->laporanDateBounds($filters); 
            $where = []; 
            $params = [];
            if ($from !== '') { $where[] = 'tanggal_masuk >= ?'; $params[] = $from; }
            if ($to !== '') { $where[] = 'tanggal_masuk <= ?'; $params[] = $to; }
            if ($f['division'] !== '') { 
                $aliases = $this->reportDivisionAliases($pdo, $f['division']); 
                $where[] = 'divisi_pengelola IN (' . implode(',', array_fill(0, count($aliases), '?')) . ')'; 
                foreach ($aliases as $alias) { $params[] = $alias; } 
            }
            $sql = 'SELECT id, log_no, tanggal_masuk, waktu_input_masuk, tanggal_keluar, waktu_input_keluar, created_at, nama_barang, qty, no_po, dokumen_po, divisi_pengelola, divisi_peminta, keterangan FROM log_barang ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY tanggal_masuk DESC, created_at DESC, id DESC';
            $stmt = $pdo->prepare($sql); 
            $stmt->execute($params); 
            $out = []; 
            $no = 1;
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) { 
                $isSelesai = (!empty($row['tanggal_keluar']) && $row['tanggal_keluar'] !== '0000-00-00');
                $status = $isSelesai ? 'SELESAI' : 'MASUK'; 
                $out[] = [
                    'id' => (int) ($row['id'] ?? 0), 
                    'no' => $no++, 
                    'date' => $this->formatDateShortSimple((string) ($row['tanggal_masuk'] ?? '')), 
                    'raw_date' => (string) ($row['tanggal_masuk'] ?? ''),
                    'date_keluar' => $isSelesai ? $this->formatDateShortSimple((string) $row['tanggal_keluar']) : '-',
                    'raw_date_keluar' => $isSelesai ? (string) $row['tanggal_keluar'] : '',
                    'datetime' => trim((string) (($row['tanggal_masuk'] ?? '') . ' ' . ($row['created_at'] ?? ''))), 
                    'item' => (string) ($row['nama_barang'] ?? '-'), 
                    'status' => $status, 
                    'status_class' => $isSelesai ? 'selesai' : 'in', 
                    'qty' => (int) ($row['qty'] ?? 1), 
                    'no_po' => (string) ($row['no_po'] ?? '-'), 
                    'pdf' => (string) ($row['dokumen_po'] ?? ''), 
                    'pdf_name' => basename((string) ($row['dokumen_po'] ?? '')), 
                    'division' => (string) ($row['divisi_pengelola'] ?? '-'), 
                    'divisi_terkait' => (string) ($row['divisi_peminta'] ?? '-'), 
                    'log_no' => (string) ($row['log_no'] ?? '-'), 
                    'keterangan' => (string) ($row['keterangan'] ?? ''),
                    'created_time' => $row['waktu_input_masuk'] ? date('H:i:s', strtotime($row['waktu_input_masuk'])) : '-',
                    'waktu_input_keluar' => $row['waktu_input_keluar'] ? date('H:i:s', strtotime($row['waktu_input_keluar'])) : '-',
                ]; 
            }
            return $out;
        } catch (Throwable $e) { return []; }
    }
    private function formatDateShortSimple(string $value): string
    {
        try { return (new DateTimeImmutable($value))->format('d/m/Y'); } catch (Throwable $e) { return $value ?: '-'; }
    }

    private function normalizeReportAssetPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') { return ''; }
        if (preg_match('#^https?://#i', $path)) { return $path; }
        $path = ltrim((string) $path, '/');
        if (strpos($path, 'public/uploads/') === 0) { return $path; }
        if (strpos($path, 'public/assets/') === 0) { return substr($path, strlen('public/assets/')); }
        if (strpos($path, 'public/') === 0) { return $path; }
        return $path;
    }
    private function safeExcelSheetName(string $name): string
    {
        // Hindari preg_replace untuk karakter backslash/slash agar tidak muncul warning
        // "Unknown modifier" pada beberapa versi PHP/XAMPP.
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name);
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name) ?: 'Sheet';
        return mb_substr($name, 0, 31) ?: 'Sheet';
    }

    private function buildInventoryDetailExcelSheets(array $sheets): array
    {
        $excelSheets = [];
        if (!$sheets) {
            $sheets = [['name' => 'Inventaris', 'division' => 'Data Inventaris', 'rows' => []]];
        }
        foreach ($sheets as $sheet) {
            $division = (string) (($sheet['division'] ?? '') ?: ($sheet['name'] ?? 'Data Inventaris'));
            $rows = [
                ['Laporan Data Inventaris - ' . $division],
                ['Diexport', date('d-m-Y H:i:s')],
                [],
            ];
            $groups = $this->inventorySheetUserGroups((array) $sheet);
            if (!$groups) {
                $rows[] = ['Tidak ada data inventaris'];
            }
            foreach ($groups as $groupIndex => $group) {
                $summary = (array) ($group['summary'] ?? []);
                if ($groupIndex > 0) {
                    $rows[] = [];
                }
                $rows[] = ['Computer Name:', (string) (($summary['computer_name'] ?? '') ?: '-'), 'User:', (string) (($summary['user'] ?? '') ?: '-')];
                $rows[] = ['Processor:', (string) (($summary['processor'] ?? '') ?: '-'), 'RAM:', (string) (($summary['ram'] ?? '') ?: '-')];
                $rows[] = ['Harddisk:', (string) (($summary['harddisk'] ?? '') ?: '-'), 'IP Address:', (string) (($summary['ip'] ?? '') ?: '-')];
                $rows[] = ['Sistem Operasi:', (string) (($summary['os'] ?? '') ?: '-'), 'Licensed Windows:', (string) (($summary['license'] ?? '') ?: '-')];
                $rows[] = ['MS Office:', (string) (($summary['office'] ?? '') ?: '-'), 'Licensed Office:', (string) (($summary['office_license'] ?? '') ?: '-')];
                $rows[] = [];
                $rows[] = ['No', 'Gambar', 'ID Inventaris', 'Jenis Perangkat', 'Merk', 'Unit Kerja', 'Status'];
                $detailRows = $this->mapInventoryRowsForDetailPdf((array) ($group['rows'] ?? []));
                if (!$detailRows) {
                    $rows[] = ['', '', 'Tidak ada data inventaris untuk user ini.', '', '', '', ''];
                } else {
                    foreach ($detailRows as $detailRow) {
                        $rows[] = [
                            (string) ($detailRow['no'] ?? ''),
                            (string) (($detailRow['image'] ?? '') ?: '-'),
                            (string) (($detailRow['id'] ?? '') ?: '-'),
                            (string) (($detailRow['device'] ?? '') ?: '-'),
                            (string) (($detailRow['brand'] ?? '') ?: '-'),
                            (string) (($detailRow['unit'] ?? '') ?: '-'),
                            (string) (($detailRow['status'] ?? '') ?: '-'),
                        ];
                    }
                }
            }
            $excelSheets[] = [
                'name' => $this->safeExcelSheetName((string) (($sheet['name'] ?? '') ?: $division)),
                'division' => $division,
                'rows' => $rows,
                'widths' => [8, 22, 24, 26, 22, 32, 16],
            ];
        }
        return $excelSheets;
    }
    private function buildMultiSheetXlsx(array $sheets): string
    {
        if (!$sheets) { $sheets = [['name' => 'Laporan', 'rows' => [['Laporan'], ['Tidak ada data']], 'widths' => [20]]]; }
        $files = [];
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        $workbookSheets = ''; $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'; $used = [];
        foreach (array_values($sheets) as $index => $sheet) {
            $i = $index + 1; $name = $this->safeExcelSheetName((string) ($sheet['name'] ?? ('Sheet ' . $i))); $base = $name; $suffix = 2;
            while (isset($used[mb_strtolower($name)])) { $tail = ' ' . $suffix++; $name = mb_substr($base, 0, 31 - mb_strlen($tail)) . $tail; }
            $used[mb_strtolower($name)] = true;
            $files['xl/worksheets/sheet' . $i . '.xml'] = $this->buildGenericWorksheetXml((array) ($sheet['rows'] ?? []), (array) ($sheet['widths'] ?? [18]), (array) ($sheet['center_cols'] ?? []));
            $contentTypes .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="' . $this->xml($name) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $workbookRels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $styleRid = count($sheets) + 1; $workbookRels .= '<Relationship Id="rId' . $styleRid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'; $contentTypes .= '</Types>';
        $files['[Content_Types].xml'] = $contentTypes;
        $files['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
        $files['docProps/core.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan</dc:title><dc:creator>SPMT</dc:creator><cp:lastModifiedBy>SPMT</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified></cp:coreProperties>';
        $files['docProps/app.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Excel</Application></Properties>';
        $files['xl/workbook.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $workbookSheets . '</sheets></workbook>';
        $files['xl/_rels/workbook.xml.rels'] = $workbookRels;
        $files['xl/styles.xml'] = $this->buildExcelStylesXml();
        foreach ($files as $fileName => $fileContent) { $files[$fileName] = str_replace('\\n', "
", $fileContent); }
        return $this->buildZipBinary($files);
    }

    private function buildExcelStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="14"/><name val="Calibri"/><color rgb="FF1B3E6F"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2A66A5"/><bgColor rgb="FF2A66A5"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFEAF5FB"/><bgColor rgb="FFEAF5FB"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFD3D3D3"/></left>
      <right style="thin"><color rgb="FFD3D3D3"/></right>
      <top style="thin"><color rgb="FFD3D3D3"/></top>
      <bottom style="thin"><color rgb="FFD3D3D3"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="7">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>';
    }

    private function buildGenericWorksheetXml(array $rows, array $widths, array $centerCols = []): string
    {
        $xmlRows = []; $maxCols = max(1, count($widths));
        foreach ($rows as $rIndex => $row) {
            $cells = [];
            $rowValues = array_values($row);
            $firstCell = (string) ($rowValues[0] ?? '');
            
            foreach ($rowValues as $cIndex => $value) {
                $maxCols = max($maxCols, $cIndex + 1);
                if ($value === null || $value === '') {
                    continue;
                }
                
                $style = '';
                if ($rIndex === 0 || strpos((string)$value, 'Laporan Data Inventaris') === 0) {
                    $style = ' s="1"'; // Title
                } else if ($firstCell === 'No' || $value === 'No') {
                    $style = ' s="2"'; // Table Header (blue background, white bold text)
                } else if ($value !== null && substr(trim((string)$value), -1) === ':') {
                    $style = ' s="3"'; // Summary Label (light blue background, bold text)
                } else if ($cIndex > 0 && substr(trim((string)($rowValues[$cIndex - 1] ?? '')), -1) === ':') {
                    $style = ' s="4"'; // Summary Value
                } else if ($rIndex === 1 && $cIndex === 0) {
                    $style = ' s="6"'; // Normal bold (Diexport label)
                } else {
                    // Regular data row
                    if ($cIndex === 0 || in_array($cIndex, $centerCols, true)) {
                        $style = ' s="5"'; // Centered cell
                    } else {
                        $style = ' s="0"'; // Normal cell
                    }
                }
                
                $ref = $this->excelColumnName($cIndex + 1) . ($rIndex + 1);
                $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . ($rIndex + 1) . '">' . implode('', $cells) . '</row>';
        }
        $cols = ''; for ($i = 0; $i < $maxCols; $i++) { $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) ($widths[$i] ?? 18) . '" customWidth="1"/>'; }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:' . $this->excelColumnName($maxCols) . max(1, count($rows)) . '"/><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>' . $cols . '</cols><sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    private function buildRoutineMonitoringGroupedPdf(string $title, array $rows): string
    {
        // ---- Page setup (Landscape A4) ----
        $pageWidth  = 842;
        $pageHeight = 595;
        $margin     = 24;

        // ---- Color palette (PDF RGB 0–1) ----
        $navyBg   = '0.12 0.22 0.39';   // #1F3864  – column header fill
        $white    = '1 1 1';
        $textMain = '0.12 0.15 0.20';   // near-black
        $textGray = '0.35 0.38 0.42';   // gray for metadata
        $altFill  = '0.93 0.96 0.98';   // light blue-white even rows
        $border   = '0.83 0.83 0.83';   // #D3D3D3 thin border

        // Category separator colors
        $catColors = [
            'CCTV'   => '0.10 0.32 0.47',   // #1A5276
            'GATE'   => '0.12 0.52 0.29',   // #1E8449
            'SERVER' => '0.42 0.21 0.51',   // #6C3483
        ];
        $defaultCatColor = '0.18 0.25 0.31'; // dark slate

        // ---- Column definitions ----
        $columns = [
            ['title' => 'No',           'key' => 'no',          'width' => 22,  'align' => 'center', 'chars' => 4],
            ['title' => 'Tanggal',      'key' => 'tanggal',     'width' => 65,  'align' => 'center', 'chars' => 10],
            ['title' => 'Kategori',     'key' => 'kategori',    'width' => 55,  'align' => 'center', 'chars' => 9],
            ['title' => 'Nama Checking','key' => 'checking',    'width' => 160, 'align' => 'left',   'chars' => 28],
            ['title' => 'Kondisi',      'key' => 'kondisi',     'width' => 65,  'align' => 'center', 'chars' => 10],
            ['title' => 'Keterangan',   'key' => 'keterangan',  'width' => 185, 'align' => 'left',   'chars' => 30],
            ['title' => 'Dicek Oleh',   'key' => 'checked_by',  'width' => 120, 'align' => 'left',   'chars' => 18],
            ['title' => 'Update',       'key' => 'update',      'width' => 105, 'align' => 'left',   'chars' => 16],
        ];
        $tableWidth = array_sum(array_column($columns, 'width')); // should equal ~777

        // ---- PDF primitives ----
        $objects = [
            1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica       /Encoding /WinAnsiEncoding >>',
            2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold  /Encoding /WinAnsiEncoding >>',
        ];
        $streams = [];
        $current = [];
        $y       = $pageHeight - $margin;

        $escape = function (string $text): string {
            $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
            if (function_exists('iconv')) {
                $c = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
                if ($c !== false && $c !== '') { $text = $c; }
            }
            $text = preg_replace('/[^ -~\x80-\xFF]/', '', $text) ?? $text;
            return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text === '' ? '-' : $text);
        };

        $addText = function (float $x, float $yPos, string $text, int $fontId = 1, int $fontSize = 7, string $color = '0 0 0') use (&$current, $escape) {
            $current[] = 'BT';
            $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize);
            $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yPos);
            $current[] = '(' . $escape($text) . ') Tj';
            $current[] = 'ET';
        };

        $addRect = function (float $x, float $yPos, float $w, float $h, ?string $fill = null, string $stroke = '0 0 0', float $lineWidth = 0.5) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            $current[] = $stroke . ' RG';
            $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yPos, $w, $h, $fill !== null ? 'B' : 'S');
        };

        $wrap = function (string $text, int $maxChars): array {
            $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
            if ($text === '') { return ['-']; }
            $words = preg_split('/\s+/', $text) ?: [$text];
            $lines = []; $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (strlen($candidate) <= $maxChars) { $line = $candidate; continue; }
                if ($line !== '') { $lines[] = $line; }
                while (strlen($word) > $maxChars) { $lines[] = substr($word, 0, $maxChars); $word = substr($word, $maxChars); }
                $line = $word;
            }
            if ($line !== '') { $lines[] = $line; }
            return array_slice($lines ?: ['-'], 0, 3);
        };

        $finishPage = function () use (&$streams, &$current) {
            if ($current) { $streams[] = implode("\n", $current); }
            $current = [];
        };

        $startPage = function () use (&$current, &$y, $pageHeight, $margin, $title, $addText, $navyBg, $textGray, $tableWidth) {
            $current = [];
            $y = $pageHeight - $margin;
            // Title
            $addText($margin, $y - 5, $title, 2, 13, $navyBg);
            // Export date
            $addText($margin, $y - 19, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 7, $textGray);
            $y -= 34;
        };

        $drawColumnHeader = function () use (&$y, $columns, $margin, $addRect, $addText, $navyBg) {
            $x = $margin;
            $h = 18;
            $rowY = $y - $h;
            foreach ($columns as $col) {
                $addRect($x, $rowY, (float)$col['width'], $h, $navyBg, $navyBg, 0.6);
                $cx = $x + 3;
                if (($col['align'] ?? 'left') === 'center') {
                    $cx = $x + ((float)$col['width'] / 2) - (strlen($col['title']) * 2);
                }
                $addText($cx, $rowY + 6, $col['title'], 2, 6, '1 1 1');
                $x += (float)$col['width'];
            }
            $y = $rowY - 2;
        };

        $drawCategoryBand = function (string $kat, int $count, string $catColor) use (&$y, $columns, $margin, $addRect, $addText, $tableWidth) {
            $h = 16;
            $rowY = $y - $h;
            // Full-width background
            $addRect($margin, $rowY, (float)$tableWidth, $h, $catColor, $catColor, 0.3);
            $addText($margin + 5, $rowY + 5, $kat . '   (' . $count . ' data)', 2, 8, '1 1 1');
            $y = $rowY - 2;
        };

        // ---- Group rows ----
        $groupedRows = [];
        foreach ($rows as $row) {
            $kat = strtoupper(trim((string)($row['kategori'] ?? '-')));
            $groupedRows[$kat][] = $row;
        }
        $categoryOrder = ['CCTV', 'GATE', 'SERVER'];
        foreach (array_keys($groupedRows) as $kat) {
            if (!in_array($kat, $categoryOrder, true)) { $categoryOrder[] = $kat; }
        }

        // ---- Render ----
        $startPage();
        $drawColumnHeader();

        $globalNo = 1;

        foreach ($categoryOrder as $kat) {
            if (!isset($groupedRows[$kat]) || empty($groupedRows[$kat])) { continue; }
            $catRows  = $groupedRows[$kat];
            $catCount = count($catRows);
            $catColor = $catColors[$kat] ?? $defaultCatColor;

            // Category band
            if (($y - 20) < $margin) {
                $finishPage();
                $startPage();
                $drawColumnHeader();
            }
            $drawCategoryBand($kat, $catCount, $catColor);

            $catDataNo = 1;
            foreach ($catRows as $row) {
                // Build wrapped cells
                $wrapped  = [];
                $maxLines = 1;
                foreach ($columns as $col) {
                    $key = (string)$col['key'];
                    $val = $key === 'no' ? (string)$globalNo
                        : ($key === 'kategori' ? strtoupper((string)($row['kategori'] ?? '-'))
                        : ($key === 'kondisi'  ? ucwords(strtolower((string)($row['kondisi'] ?? '-')))
                        : (string)($row[$key] ?? '-')));
                    $wrapped[$key] = $wrap($val, (int)($col['chars'] ?? 12));
                    $maxLines = max($maxLines, count($wrapped[$key]));
                }
                $rowHeight = max(18, $maxLines * 9 + 6);

                if (($y - $rowHeight) < $margin) {
                    $finishPage();
                    $startPage();
                    $drawColumnHeader();
                }

                $rowY   = $y - $rowHeight;
                $x      = $margin;
                $isEven = ($catDataNo % 2 === 0);

                // Status color for Kondisi
                $kondisiUpper = strtoupper(trim((string)($row['kondisi'] ?? '')));
                $kondisiColor = null; // null = use row fill
                if (in_array($kondisiUpper, ['BAIK', 'ON', 'AKTIF', 'AMAN', 'ACTIVE'], true)) {
                    $kondisiColor = '0.83 0.93 0.85'; // green tint
                } elseif (in_array($kondisiUpper, ['KURANG BAIK', 'WARNING', 'PENDING'], true)) {
                    $kondisiColor = '1 0.95 0.80';    // yellow tint
                } elseif (in_array($kondisiUpper, ['BURUK', 'OFF', 'TIDAK AKTIF', 'RUSAK'], true)) {
                    $kondisiColor = '0.97 0.84 0.85'; // red tint
                }

                foreach ($columns as $col) {
                    $key  = (string)$col['key'];
                    $cw   = (float)$col['width'];
                    $fill = $isEven ? $altFill : $white;
                    if ($key === 'kondisi' && $kondisiColor !== null) {
                        $fill = $kondisiColor;
                    }
                    $addRect($x, $rowY, $cw, $rowHeight, $fill, $border, 0.35);

                    $linesArr  = $wrapped[$key];
                    $nLines    = count($linesArr);
                    $textBlock = $nLines * 9 - 3;
                    $lineY     = $rowY + (($rowHeight - $textBlock) / 2) + $textBlock - 6;

                    $fontId    = ($key === 'kondisi' && $kondisiColor !== null) ? 2 : 1;
                    $textColor = ($key === 'kondisi' && $kondisiColor !== null)
                        ? (in_array($kondisiUpper, ['BAIK', 'ON', 'AKTIF', 'AMAN', 'ACTIVE'], true)
                            ? '0.08 0.34 0.14'   // dark green
                            : (in_array($kondisiUpper, ['KURANG BAIK', 'WARNING', 'PENDING'], true)
                                ? '0.52 0.39 0.02' // dark yellow
                                : '0.44 0.11 0.14')) // dark red
                        : $textMain;

                    foreach ($linesArr as $line) {
                        if (($col['align'] ?? 'left') === 'center') {
                            $textX = $x + ($cw / 2) - (strlen($line) * 1.9);
                        } else {
                            $textX = $x + 3;
                        }
                        $addText($textX, $lineY, $line, $fontId, 6, $textColor);
                        $lineY -= 9;
                    }
                    $x += $cw;
                }

                $y = $rowY - 1;
                $catDataNo++;
                $globalNo++;
            }

            // Spacer after category
            $y -= 6;
        }

        if (!$rows) {
            $addText($margin, $y - 18, 'Tidak ada data.', 1, 9, $textMain);
        }
        $finishPage();

        // ---- Assemble PDF ----
        $nextId    = 3;
        $contentIds = [];
        $pageIds    = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++;
            $pageId    = $nextId++;
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[$pageId]    = '';
            $contentIds[] = $contentId;
            $pageIds[]    = $pageId;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [ ' . $kids . ' ] >>';
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        ksort($objects);
        $pdf     = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $size = $catalogId + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function buildGenericReportPdf(string $title, array $columns, array $rows): string
    {
        $pageWidth = 842; $pageHeight = 595; $margin = 24; $blue = '0.16 0.37 0.65'; $textColor = '0.15 0.18 0.22'; $border = '0.78 0.84 0.90'; $altFill = '0.97 0.98 1';
        $objects = [1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>']; $streams = []; $current = []; $y = $pageHeight - $margin;
        $escape = function (string $text): string { $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text); if (function_exists('iconv')) { $c = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text); if ($c !== false && $c !== '') { $text = $c; } } $text = preg_replace('/[^ -~\x80-\xFF]/', '', $text) ?? $text; return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text === '' ? '-' : $text); };
        $addText = function (float $x, float $yPos, string $text, int $fontId = 1, int $fontSize = 7, string $color = '0 0 0') use (&$current, $escape) { $current[] = 'BT'; $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize); $current[] = $color . ' rg'; $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $yPos); $current[] = '(' . $escape($text) . ') Tj'; $current[] = 'ET'; };
        $addRect = function (float $x, float $yPos, float $w, float $h, ?string $fill = null, string $stroke = '0 0 0', float $lineWidth = 0.5) use (&$current) { $current[] = sprintf('%.2f w', $lineWidth); if ($fill !== null) { $current[] = $fill . ' rg'; } $current[] = $stroke . ' RG'; $current[] = sprintf('%.2f %.2f %.2f %.2f re %s', $x, $yPos, $w, $h, $fill !== null ? 'B' : 'S'); };
        $wrap = function (string $text, int $maxChars): array { $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text); if ($text === '') { return ['-']; } $words = preg_split('/\s+/', $text) ?: [$text]; $lines = []; $line = ''; foreach ($words as $word) { $candidate = $line === '' ? $word : $line . ' ' . $word; if (strlen($candidate) <= $maxChars) { $line = $candidate; continue; } if ($line !== '') { $lines[] = $line; } while (strlen($word) > $maxChars) { $lines[] = substr($word, 0, $maxChars); $word = substr($word, $maxChars); } $line = $word; } if ($line !== '') { $lines[] = $line; } return array_slice($lines ?: ['-'], 0, 3); };
        $startPage = function () use (&$current, &$y, $pageHeight, $margin, $title, $addText, $blue, $textColor) { $current = []; $y = $pageHeight - $margin; $addText($margin, $y - 4, $title, 2, 14, $blue); $addText($margin, $y - 20, 'Diexport: ' . date('d-m-Y H:i:s'), 1, 8, $textColor); $y -= 40; };
        $finishPage = function () use (&$streams, &$current) { if ($current) { $streams[] = implode("\n", $current); } $current = []; };
        $drawHeader = function () use (&$y, $columns, $margin, $addRect, $addText, $blue) { $x = $margin; $h = 18; $rowY = $y - $h; foreach ($columns as $col) { $addRect($x, $rowY, (float) $col['width'], $h, $blue, $blue, 0.7); $addText($x + 3, $rowY + 6, (string) $col['title'], 2, 6, '1 1 1'); $x += (float) $col['width']; } $y = $rowY - 2; };
        $startPage(); $drawHeader();
        foreach ($rows as $idx => $row) { $wrapped = []; $maxLines = 1; foreach ($columns as $col) { $key = (string) $col['key']; $wrapped[$key] = $wrap((string) ($row[$key] ?? '-'), (int) ($col['chars'] ?? 12)); $maxLines = max($maxLines, count($wrapped[$key])); } $rowHeight = max(20, $maxLines * 9 + 8); if (($y - $rowHeight) < $margin) { $finishPage(); $startPage(); $drawHeader(); } $rowY = $y - $rowHeight; $x = $margin; foreach ($columns as $col) { $key = (string) $col['key']; $addRect($x, $rowY, (float) $col['width'], $rowHeight, $idx % 2 === 0 ? $altFill : '1 1 1', $border, 0.45); $linesCount = count($wrapped[$key]); $textHeight = $linesCount * 9 - 3; $lineY = $rowY + (($rowHeight - $textHeight) / 2) + $textHeight - 6; foreach ($wrapped[$key] as $line) { $addText($x + 3, $lineY, $line, 1, 6, $textColor); $lineY -= 9; } $x += (float) $col['width']; } $y = $rowY - 2; }
        if (!$rows) { $addText($margin, $y - 18, 'Tidak ada data.', 1, 9, $textColor); } $finishPage();
        $nextId = 3; $contentIds = []; $pageIds = []; foreach ($streams as $stream) { $contentId = $nextId++; $pageId = $nextId++; $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"; $objects[$pageId] = ''; $contentIds[] = $contentId; $pageIds[] = $pageId; }
        $pagesId = $nextId++; foreach ($pageIds as $index => $pageId) { $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents {$contentIds[$index]} 0 R >>"; } $kids = implode(' ', array_map(static fn ($id) => $id . ' 0 R', $pageIds)); $objects[$pagesId] = '<< /Type /Pages /Count ' . count($pageIds) . ' /Kids [ ' . $kids . ' ] >>'; $catalogId = $nextId++; $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        ksort($objects); $pdf = "%PDF-1.4\n"; $offsets = [0]; foreach ($objects as $id => $object) { $offsets[$id] = strlen($pdf); $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n"; } $xref = strlen($pdf); $size = $catalogId + 1; $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n"; for ($i = 1; $i < $size; $i++) { $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0); } $pdf .= "trailer << /Size {$size} /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF"; return $pdf;
    }

    private function handleInventoryAction(array $filters): void
    {
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return;
        }

        if (($_GET['action'] ?? '') === 'export') {
            $this->streamInventoryExport($pdo, $filters, (string) ($_GET['format'] ?? 'pdf'));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string) ($_POST['action'] ?? '');
        if (!in_array($action, ['save_pc', 'save_other', 'save_inventory_edit', 'delete_inventory_bundle', 'delete_other_item'], true)) {
            return;
        }

        try {
            $context = $this->model->getDivisionContext($pdo, $filters);
            $inventoryDb = (string) ($context['inventory_db'] ?? '');
            if (!$this->isSafeIdentifier($inventoryDb)) {
                throw new RuntimeException('Database inventaris tidak valid.');
            }

            if (AuthController::role() === 'user' && in_array($action, ['save_inventory_edit', 'delete_inventory_bundle', 'delete_other_item'], true)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Akses ditolak. Sebagai User, Anda hanya diperbolehkan menambah data dan tidak bisa mengedit atau menghapus.'];
                header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, $context)));
                exit;
            }

            if ($action === 'save_pc') {
                $this->ensurePcSchema($pdo, $inventoryDb);
                $this->insertPcRow($pdo, $inventoryDb, $_POST);
                $pageKey = $this->buildPageKeyFromInput($_POST);
                $focusItem = $this->buildPcFocusItemFromInput($_POST);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'create', 'pc', (string) ($_POST['id_inventaris'] ?? ''));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data PC baru berhasil ditambahkan dan dapat diperbarui.'];
                $lastPage = $this->resolveUserPageNumberByPageKey($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $this->resolveLastUserPageNumber($pdo, (string) ($context['division_code'] ?? '')));
                header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, $context, ['user_page' => $lastPage, 'user' => trim((string) ($_POST['user'] ?? '')), 'focus_item' => $focusItem])));
                exit;
            }

            if ($action === 'save_other') {
                $pageKey = trim((string) ($_POST['page_key'] ?? ''));
                $focusItem = $this->insertOtherRow($pdo, $inventoryDb, $pageKey, $_POST, $_FILES);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'create', 'perangkat_lain', (string) ($_POST['other_id_inventaris'] ?? ''));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Perangkat lain baru berhasil ditambahkan dan halaman ini sudah diperbarui.'];
                $targetPage = $this->resolveUserPageNumberByPageKey($pdo, (string) ($context['division_code'] ?? ''), $pageKey, (int) ($context['current_user_page'] ?? 1));
                header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, $context, ['user_page' => $targetPage, 'focus_item' => 'other:' . $focusItem])));
                exit;
            }

            if ($action === 'delete_inventory_bundle') {
                $pageKey = trim((string) ($_POST['page_key'] ?? ''));
                $this->deleteInventoryBundle($pdo, $inventoryDb, $pageKey);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'delete', 'bundle', $pageKey);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data PC dan perangkat lain berhasil dihapus dan daftar sudah diperbarui.'];
                header('Location: ' . $this->buildDetailUrl($filters));
                exit;
            }

            if ($action === 'delete_other_item') {
                $pageKey = trim((string) ($_POST['page_key'] ?? ''));
                $itemKey = trim((string) ($_POST['item_key'] ?? ''));
                $deletedItem = $this->deleteOtherRow($pdo, $inventoryDb, $pageKey, $itemKey);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'delete', 'perangkat_lain', $itemKey);
                $deviceLabel = trim((string) ($deletedItem['jenis_perangkat'] ?? 'Perangkat'));
                $_SESSION['flash'] = ['type' => 'success', 'message' => $deviceLabel . ' berhasil dihapus dan halaman ini sudah diperbarui.'];
                header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, $context)));
                exit;
            }

            $pageKey = trim((string) ($_POST['page_key'] ?? ''));
            $focusItem = '';
            if ((string) ($_POST['edit_scope'] ?? 'pc') === 'other') {
                $focusItem = 'other:' . $this->updateOtherRow($pdo, $inventoryDb, $pageKey, trim((string) ($_POST['item_key'] ?? '')), $_POST, $_FILES);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'update', 'perangkat_lain', (string) ($_POST['item_key'] ?? ''));
            } else {
                $this->ensurePcSchema($pdo, $inventoryDb);
                $targetDivisionCode = trim((string) ($_POST['target_division_code'] ?? ($context['division_code'] ?? '')));
                $currentDivisionCode = trim((string) ($context['division_code'] ?? ''));
                if ($targetDivisionCode !== '' && $currentDivisionCode !== '' && $targetDivisionCode !== $currentDivisionCode) {
                    $targetDivision = $this->fetchInventoryDivisionByCode($pdo, $targetDivisionCode);
                    if (!$targetDivision) {
                        throw new RuntimeException('Divisi tujuan tidak valid atau tidak aktif.');
                    }
                    $moveResult = $this->moveInventoryBundleToDivision($pdo, $inventoryDb, $pageKey, $_POST, $targetDivision);
                    $pageKey = (string) ($moveResult['page_key'] ?? $this->buildPageKeyFromInput($_POST));
                    $focusItem = $this->buildPcFocusItemFromInput($_POST);
                    $this->model->logInventoryUpdate($pdo, $targetDivisionCode, $pageKey, (string) ($moveResult['inventory_db'] ?? ''), 'move', 'bundle', (string) ($_POST['id_inventaris'] ?? $pageKey));
                    $_SESSION['flash'] = ['type' => 'success', 'message' => '1 set PC dan perangkat lain berhasil dipindahkan ke divisi tujuan. Nama user dan unit perangkat lain sudah ikut tersinkron.'];
                    $targetLabel = strtoupper((string) ($targetDivision['division_label'] ?? $targetDivisionCode));
                    header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, [
                        'division_code' => $targetDivisionCode,
                        'display_division' => $targetLabel,
                        'current_user_page' => 1,
                    ], ['division_code' => $targetDivisionCode, 'display_division' => $targetLabel, 'user_page' => 1, 'user' => trim((string) ($_POST['user'] ?? '')), 'focus_item' => $focusItem])));
                    exit;
                }
                $this->updatePcRow($pdo, $inventoryDb, $pageKey, $_POST);
                $pageKey = $this->buildPageKeyFromInput($_POST);
                $focusItem = $this->buildPcFocusItemFromInput($_POST);
                $this->model->logInventoryUpdate($pdo, (string) ($context['division_code'] ?? ''), $pageKey, $inventoryDb, 'update', 'pc', (string) ($_POST['id_inventaris'] ?? ''));
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Perubahan data inventaris berhasil disimpan. Jika nama user/unit PC berubah, perangkat lain pada set yang sama sudah ikut diperbarui.'];
            $redirectPageKey = (string) ($_POST['edit_scope'] ?? 'pc') === 'other' ? $pageKey : $this->buildPageKeyFromInput($_POST);
            $redirectPage = $this->resolveUserPageNumberByPageKey($pdo, (string) ($context['division_code'] ?? ''), $redirectPageKey, (int) ($context['current_user_page'] ?? 1));
            $redirectFilters = $this->detailRedirectFilters($filters, $context, ['user_page' => $redirectPage, 'focus_item' => $focusItem]);
            if ((string) ($_POST['edit_scope'] ?? 'pc') !== 'other') {
                $redirectFilters['user'] = trim((string) ($_POST['user'] ?? ''));
            }
            header('Location: ' . $this->buildDetailUrl($redirectFilters));
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Perubahan gagal disimpan: ' . $e->getMessage()];
        }

        header('Location: ' . $this->buildDetailUrl($this->detailRedirectFilters($filters, $context ?? [])));
        exit;
    }

    private function detailRedirectFilters(array $filters, array $context = [], array $extra = []): array
    {
        $currentPage = max(1, (int) ($context['current_user_page'] ?? $filters['user_page'] ?? 1));
        $currentUser = trim((string) ($context['current_user_name'] ?? $filters['user'] ?? ''));
        $base = $filters;
        $base['division_code'] = (string) ($context['division_code'] ?? ($filters['division_code'] ?? ''));
        $base['display_division'] = (string) ($context['display_division'] ?? ($filters['display_division'] ?? ''));
        $base['user_page'] = $currentPage;
        if ($currentUser !== '') {
            $base['user'] = $currentUser;
        }
        foreach ($extra as $key => $value) {
            $base[$key] = $value;
        }
        return $base;
    }

    private function nextPcInventoryOrder(PDO $pdo, string $inventoryDb): int
    {
        if (!$this->isSafeIdentifier($inventoryDb)) {
            return 1;
        }
        try {
            $stmt = $pdo->query(sprintf('SELECT COALESCE(MAX(`inventory_order`), 0) + 1 FROM `%s`.pc', $inventoryDb));
            return max(1, (int) ($stmt ? $stmt->fetchColumn() : 1));
        } catch (Throwable $e) {
            return 1;
        }
    }

    private function insertPcRow(PDO $pdo, string $inventoryDb, array $payload): void
    {
        $this->ensurePcSchema($pdo, $inventoryDb);
        $sql = sprintf('INSERT INTO `%s`.pc (id_inventaris, unit_kerja, jenis_perangkat, merk_perangkat, computer_name, user, processor, ram, kapasitas_harddisk, ip_address, sistem_operasi, licensed_windows, microsoft_office, licensed_office, gambar, status, inventory_order, inventory_created_at, inventory_updated_at) VALUES (:id_inventaris, :unit_kerja, :jenis_perangkat, :merk_perangkat, :computer_name, :user, :processor, :ram, :kapasitas_harddisk, :ip_address, :sistem_operasi, :licensed_windows, :microsoft_office, :licensed_office, :gambar, :status, :inventory_order, NOW(), NOW())', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $payloadFields = $this->pcFieldPayload($payload);
        $payloadFields['inventory_order'] = $this->nextPcInventoryOrder($pdo, $inventoryDb);
        $stmt->execute($payloadFields);
    }

    private function updatePcRow(PDO $pdo, string $inventoryDb, string $pageKey, array $payload): void
    {
        $this->ensurePcSchema($pdo, $inventoryDb);
        $this->ensureInventoryOtherSchema($pdo, $inventoryDb);
        $current = $this->findPcRowByPageKey($pdo, $inventoryDb, $pageKey);
        if (!$current) {
            throw new RuntimeException('Data PC tidak ditemukan.');
        }
        $where = $this->buildWhereByPcRow($current);
        $pcPayload = $this->pcFieldPayload($payload, (string) ($current['gambar'] ?? ''));
        $sql = sprintf('UPDATE `%s`.pc SET id_inventaris = :id_inventaris, unit_kerja = :unit_kerja, jenis_perangkat = :jenis_perangkat, merk_perangkat = :merk_perangkat, computer_name = :computer_name, user = :user, processor = :processor, ram = :ram, kapasitas_harddisk = :kapasitas_harddisk, ip_address = :ip_address, sistem_operasi = :sistem_operasi, licensed_windows = :licensed_windows, microsoft_office = :microsoft_office, licensed_office = :licensed_office, gambar = :gambar, status = :status, inventory_updated_at = NOW() %s', $inventoryDb, $where['sql']);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($pcPayload, $where['params']));
        $this->syncOtherItemsForPcChange($pdo, $inventoryDb, $current, $pcPayload);
    }


    private function ensureInventoryOtherSchema(PDO $pdo, string $inventoryDb): void
    {
        if (!$this->isSafeIdentifier($inventoryDb)) {
            return;
        }
        $columns = [
            'created_at' => 'ALTER TABLE `%s`.perangkat_lain ADD COLUMN `created_at` datetime NOT NULL DEFAULT current_timestamp() AFTER `gambar`',
            'updated_at' => 'ALTER TABLE `%s`.perangkat_lain ADD COLUMN `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`',
            'last_edited_at' => 'ALTER TABLE `%s`.perangkat_lain ADD COLUMN `last_edited_at` datetime DEFAULT NULL AFTER `updated_at`',
            'sync_at' => 'ALTER TABLE `%s`.perangkat_lain ADD COLUMN `sync_at` datetime DEFAULT NULL AFTER `last_edited_at`',
            'edit_source' => 'ALTER TABLE `%s`.perangkat_lain ADD COLUMN `edit_source` varchar(50) DEFAULT "manual" AFTER `sync_at`',
        ];
        foreach ($columns as $column => $sqlTemplate) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "perangkat_lain" AND column_name = :column');
            $stmt->execute(['schema' => $inventoryDb, 'column' => $column]);
            if ((int) $stmt->fetchColumn() < 1) {
                $pdo->exec(sprintf($sqlTemplate, $inventoryDb));
            }
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "perangkat_lain" AND column_name = "status"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.perangkat_lain ADD COLUMN `status` varchar(100) DEFAULT "AKTIF" AFTER `user`', $inventoryDb));
        }
        // Tambah kolom pc_row_id sebagai soft-reference ke pc.id
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "perangkat_lain" AND column_name = "pc_row_id"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.perangkat_lain ADD COLUMN `pc_row_id` BIGINT NULL DEFAULT NULL AFTER `gambar`', $inventoryDb));
        }
        $pdo->exec(sprintf("UPDATE `%s`.perangkat_lain SET status = 'AKTIF' WHERE status IS NULL OR TRIM(status) = ''", $inventoryDb));
    }

    private function fetchOtherRowsForPcSet(PDO $pdo, string $inventoryDb, array $pcRow): array
    {
        $this->ensureInventoryOtherSchema($pdo, $inventoryDb);
        $user = trim((string) ($pcRow['user'] ?? ''));
        if ($user === '') {
            return [];
        }
        $sql = sprintf('SELECT * FROM `%s`.perangkat_lain WHERE TRIM(`user`) = :user ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user' => $user]);
        return $stmt->fetchAll() ?: [];
    }

    private function syncOtherItemsForPcChange(PDO $pdo, string $inventoryDb, array $oldPc, array $newPc): void
    {
        $this->ensureInventoryOtherSchema($pdo, $inventoryDb);
        $oldUser = trim((string) ($oldPc['user'] ?? ''));
        $newUser = trim((string) ($newPc['user'] ?? ''));
        $newUnit = trim((string) ($newPc['unit_kerja'] ?? ''));

        if ($oldUser === '') {
            return;
        }

        $sql = sprintf('UPDATE `%s`.perangkat_lain SET `user` = :new_user, `unit_kerja` = :new_unit, `sync_at` = NOW(), `edit_source` = "pc_user_sync" WHERE TRIM(`user`) = :old_user', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['new_user' => $newUser, 'new_unit' => $newUnit, 'old_user' => $oldUser]);
    }

    private function insertPcPayload(PDO $pdo, string $inventoryDb, array $pcPayload): void
    {
        $this->ensurePcSchema($pdo, $inventoryDb);
        $sql = sprintf('INSERT INTO `%s`.pc (id_inventaris, unit_kerja, jenis_perangkat, merk_perangkat, computer_name, user, processor, ram, kapasitas_harddisk, ip_address, sistem_operasi, licensed_windows, microsoft_office, licensed_office, gambar, status, inventory_order, inventory_created_at, inventory_updated_at) VALUES (:id_inventaris, :unit_kerja, :jenis_perangkat, :merk_perangkat, :computer_name, :user, :processor, :ram, :kapasitas_harddisk, :ip_address, :sistem_operasi, :licensed_windows, :microsoft_office, :licensed_office, :gambar, :status, :inventory_order, NOW(), NOW())', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $pcPayload['inventory_order'] = $this->nextPcInventoryOrder($pdo, $inventoryDb);
        $stmt->execute($pcPayload);
    }

    private function insertOtherPayload(PDO $pdo, string $inventoryDb, array $otherPayload): void
    {
        $this->ensureInventoryOtherSchema($pdo, $inventoryDb);
        $sql = sprintf('INSERT INTO `%s`.perangkat_lain (id_inventaris, jenis_perangkat, merk_perangkat, unit_kerja, user, status, gambar, sync_at, edit_source) VALUES (:id_inventaris, :jenis_perangkat, :merk_perangkat, :unit_kerja, :user, :status, :gambar, NOW(), "move_sync")', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_inventaris' => $otherPayload['id_inventaris'] ?? null,
            'jenis_perangkat' => $otherPayload['jenis_perangkat'] ?? null,
            'merk_perangkat' => $otherPayload['merk_perangkat'] ?? null,
            'unit_kerja' => $otherPayload['unit_kerja'] ?? null,
            'user' => $otherPayload['user'] ?? null,
            'status' => $otherPayload['status'] ?? 'AKTIF',
            'gambar' => $otherPayload['gambar'] ?? null,
        ]);
    }

    private function moveInventoryBundleToDivision(PDO $pdo, string $sourceDb, string $pageKey, array $payload, array $targetDivision): array
    {
        $targetDb = (string) ($targetDivision['inventory_db_name'] ?? '');
        if (!$this->isSafeIdentifier($sourceDb) || !$this->isSafeIdentifier($targetDb)) {
            throw new RuntimeException('Database sumber atau tujuan tidak valid.');
        }
        $this->ensureInventoryDatabase($pdo, $targetDb);
        $this->ensurePcSchema($pdo, $sourceDb);
        $this->ensurePcSchema($pdo, $targetDb);
        $this->ensureInventoryOtherSchema($pdo, $sourceDb);
        $this->ensureInventoryOtherSchema($pdo, $targetDb);

        $currentPc = $this->findPcRowByPageKey($pdo, $sourceDb, $pageKey);
        if (!$currentPc) {
            throw new RuntimeException('Data PC yang akan dipindahkan tidak ditemukan.');
        }
        $otherRows = $this->fetchOtherRowsForPcSet($pdo, $sourceDb, $currentPc);
        $pcPayload = $this->pcFieldPayload($payload, (string) ($currentPc['gambar'] ?? ''));
        if (trim((string) ($pcPayload['unit_kerja'] ?? '')) === '') {
            $pcPayload['unit_kerja'] = (string) ($targetDivision['division_label'] ?? '');
        }

        $pdo->beginTransaction();
        try {
            $this->insertPcPayload($pdo, $targetDb, $pcPayload);
            foreach ($otherRows as $other) {
                $otherPayload = [
                    'id_inventaris' => $other['id_inventaris'] ?? null,
                    'jenis_perangkat' => $other['jenis_perangkat'] ?? null,
                    'merk_perangkat' => $other['merk_perangkat'] ?? null,
                    'unit_kerja' => $pcPayload['unit_kerja'] ?? null,
                    'user' => $pcPayload['user'] ?? null,
                    'status' => in_array(strtoupper(trim((string) ($other['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper(trim((string) ($other['status'] ?? 'AKTIF'))) : 'AKTIF',
                    'gambar' => $other['gambar'] ?? null,
                ];
                $this->insertOtherPayload($pdo, $targetDb, $otherPayload);
            }
            $where = $this->buildWhereByPcRow($currentPc);
            $stmtPc = $pdo->prepare(sprintf('DELETE FROM `%s`.pc %s', $sourceDb, $where['sql']));
            $stmtPc->execute($where['params']);
            if (trim((string) ($currentPc['user'] ?? '')) !== '') {
                $stmtOther = $pdo->prepare(sprintf('DELETE FROM `%s`.perangkat_lain WHERE TRIM(`user`) = :user', $sourceDb));
                $stmtOther->execute(['user' => trim((string) ($currentPc['user'] ?? ''))]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return [
            'inventory_db' => $targetDb,
            'page_key' => $this->buildPageKeyFromInput($payload),
            'pc_payload' => $pcPayload,
        ];
    }

    private function updateOtherRow(PDO $pdo, string $inventoryDb, string $pageKey, string $itemKey, array $payload, array $files = []): string
    {
        $pcRow = $this->findPcRowByPageKey($pdo, $inventoryDb, $pageKey);
        if (!$pcRow) {
            throw new RuntimeException('Konteks user tidak ditemukan.');
        }
        $other = $this->findOtherRow($pdo, $inventoryDb, $pcRow, $itemKey);
        if (!$other) {
            throw new RuntimeException('Perangkat lain tidak ditemukan.');
        }

        $sql = sprintf('UPDATE `%s`.perangkat_lain SET id_inventaris = :id_inventaris, jenis_perangkat = :jenis_perangkat, merk_perangkat = :merk_perangkat, unit_kerja = :unit_kerja, user = :user, status = :status, gambar = :gambar WHERE id_inventaris <=> :where_id AND jenis_perangkat <=> :where_jenis AND merk_perangkat <=> :where_merk AND unit_kerja <=> :where_unit AND user <=> :where_user', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $params = $this->otherFieldPayload($payload, $pcRow, $files, (string) ($other['gambar'] ?? ''));
        $stmt->execute(array_merge($params, [
            'where_id' => $other['id_inventaris'] ?? null,
            'where_jenis' => $other['jenis_perangkat'] ?? null,
            'where_merk' => $other['merk_perangkat'] ?? null,
            'where_unit' => $other['unit_kerja'] ?? null,
            'where_user' => $other['user'] ?? null,
        ]));
        return $this->buildItemKey($params);
    }

    private function deleteOtherRow(PDO $pdo, string $inventoryDb, string $pageKey, string $itemKey): array
    {
        $pcRow = $this->findPcRowByPageKey($pdo, $inventoryDb, $pageKey);
        if (!$pcRow) {
            throw new RuntimeException('Konteks user tidak ditemukan.');
        }

        $other = $this->findOtherRow($pdo, $inventoryDb, $pcRow, $itemKey);
        if (!$other) {
            throw new RuntimeException('Perangkat lain tidak ditemukan.');
        }

        $sql = sprintf('DELETE FROM `%s`.perangkat_lain WHERE id_inventaris <=> :where_id AND jenis_perangkat <=> :where_jenis AND merk_perangkat <=> :where_merk AND unit_kerja <=> :where_unit AND user <=> :where_user LIMIT 1', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'where_id' => $other['id_inventaris'] ?? null,
            'where_jenis' => $other['jenis_perangkat'] ?? null,
            'where_merk' => $other['merk_perangkat'] ?? null,
            'where_unit' => $other['unit_kerja'] ?? null,
            'where_user' => $other['user'] ?? null,
        ]);

        if ((int) $stmt->rowCount() < 1) {
            throw new RuntimeException('Perangkat lain gagal dihapus.');
        }

        return $other;
    }

    private function deleteInventoryBundle(PDO $pdo, string $inventoryDb, string $pageKey): void
    {
        $pcRow = $this->findPcRowByPageKey($pdo, $inventoryDb, $pageKey);
        if (!$pcRow) {
            throw new RuntimeException('Konteks user tidak ditemukan.');
        }

        $where = $this->buildWhereByPcRow($pcRow);
        $sqlPc = sprintf('DELETE FROM `%s`.pc %s', $inventoryDb, $where['sql']);
        $stmtPc = $pdo->prepare($sqlPc);
        $stmtPc->execute($where['params']);

        $user = trim((string) ($pcRow['user'] ?? ''));
        if ($user !== '') {
            $sqlOther = sprintf('DELETE FROM `%s`.perangkat_lain WHERE TRIM(`user`) = :user', $inventoryDb);
            $stmtOther = $pdo->prepare($sqlOther);
            $stmtOther->execute(['user' => $user]);
        }
    }

    private function otherFieldPayload(array $payload, array $pcRow = [], array $files = [], ?string $existingImage = null): array
    {
        $jenis = $this->clean((string) ($payload['other_jenis_perangkat'] ?? ''));
        $merk = $this->clean((string) ($payload['other_merk_perangkat'] ?? ''));
        return [
            'id_inventaris' => $this->clean((string) ($payload['other_id_inventaris'] ?? '')),
            'jenis_perangkat' => $jenis,
            'merk_perangkat' => $merk,
            'unit_kerja' => $this->clean((string) ($payload['other_unit_kerja'] ?? ($pcRow['unit_kerja'] ?? ''))),
            'user' => $this->clean((string) ($payload['other_user'] ?? ($pcRow['user'] ?? ''))),
            'status' => in_array(strtoupper($this->clean((string) ($payload['other_status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper($this->clean((string) ($payload['other_status'] ?? 'AKTIF'))) : 'AKTIF',
            'gambar' => $this->clean($this->resolveImagePath($payload, $files, $jenis, $existingImage)),
        ];
    }

    private function insertOtherRow(PDO $pdo, string $inventoryDb, string $pageKey, array $payload, array $files = [], ?int $pcRowId = null, string $overrideUser = '', string $overrideUnitKerja = ''): string
    {
        $pcRow = $this->findPcRowByPageKey($pdo, $inventoryDb, $pageKey);
        if (!$pcRow) {
            throw new RuntimeException('Konteks user tidak ditemukan.');
        }

        // Jika ada override (dari Mode A baru), pakai data PC yang dipilih
        if ($overrideUser !== '') {
            $pcRow['user'] = $overrideUser;
        }
        if ($overrideUnitKerja !== '') {
            $pcRow['unit_kerja'] = $overrideUnitKerja;
        }

        $params = $this->otherFieldPayload($payload, $pcRow, $files);
        $resolvedPcRowId = $pcRowId ?? ((isset($pcRow['id']) && (int) $pcRow['id'] > 0) ? (int) $pcRow['id'] : null);

        $sql = sprintf(
            'INSERT INTO `%s`.perangkat_lain (id_inventaris, jenis_perangkat, merk_perangkat, unit_kerja, user, status, gambar, pc_row_id) VALUES (:id_inventaris, :jenis_perangkat, :merk_perangkat, :unit_kerja, :user, :status, :gambar, :pc_row_id)',
            $inventoryDb
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, ['pc_row_id' => $resolvedPcRowId]));
        return $this->buildItemKey($params);
    }

    /**
     * Insert perangkat lain Mode B (barang mandiri) — tanpa kaitan ke PC.
     */
    private function insertStandaloneOtherRow(PDO $pdo, string $inventoryDb, array $payload, array $files = []): string
    {
        $this->ensureInventoryOtherSchema($pdo, $inventoryDb);
        $params = $this->otherFieldPayload($payload, [], $files);
        // Unit kerja dari form (bisa dari division_label jika kosong)
        if (trim((string) ($params['unit_kerja'] ?? '')) === '') {
            $params['unit_kerja'] = trim((string) ($_POST['division_label'] ?? ''));
        }
        $sql = sprintf(
            'INSERT INTO `%s`.perangkat_lain (id_inventaris, jenis_perangkat, merk_perangkat, unit_kerja, user, status, gambar, pc_row_id, edit_source) VALUES (:id_inventaris, :jenis_perangkat, :merk_perangkat, :unit_kerja, :user, :status, :gambar, NULL, "standalone")',
            $inventoryDb
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $this->buildItemKey($params);
    }

    private function resolveImagePath(array $payload, array $files, ?string $device, ?string $existingImage = null): string
    {
        $device = trim((string) ($device ?? ''));
        $uploaded = $this->saveUploadedImage($files['other_gambar_file'] ?? null, $device);
        if ($uploaded !== null) {
            return $uploaded;
        }

        $existing = trim((string) ($payload['other_gambar_existing'] ?? $existingImage ?? ''));
        if ($existing !== '') {
            return ltrim($existing, '/');
        }

        return $this->defaultImageForDevice($device);
    }

    private function resolvePcImagePath(array $payload, array $files, ?string $device, ?string $existingImage = null): string
    {
        $device = trim((string) ($device ?? 'PC'));
        $uploaded = $this->saveUploadedImage($files['pc_gambar_file'] ?? null, $device);
        if ($uploaded !== null) {
            return $uploaded;
        }

        if ((string) ($payload['remove_pc_gambar_file'] ?? '0') === '1') {
            return 'images/inv-pc.png';
        }

        $existing = trim((string) ($payload['pc_gambar_existing'] ?? ''));
        if ($existing === '') {
            $existing = trim((string) ($payload['existing_pc_gambar_file'] ?? ''));
        }
        if ($existing === '') {
            $existing = trim((string) ($existingImage ?? ''));
        }
        if ($existing !== '') {
            $existing = preg_replace('/\?.*$/', '', $existing);
            $existing = preg_replace('#^public/assets/#', '', ltrim($existing, '/'));
            return ltrim($existing, '/');
        }

        return 'images/inv-pc.png';
    }

    private function saveUploadedImage(?array $file, string $device): ?string
    {
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gambar gagal diproses.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }

        $mime = (string) mime_content_type($tmp);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
        }

        $baseDir = dirname(__DIR__, 2) . '/public/assets/uploads/inventory';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Folder upload gambar tidak bisa dibuat.');
        }

        $safeDevice = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($device ?: 'device')));
        $filename = $safeDevice . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $target = $baseDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Gambar tidak berhasil disimpan.');
        }

        if ($allowed[$mime] !== 'jpg') {
            $convertPath = trim((string) shell_exec('command -v magick || command -v convert'));
            if ($convertPath !== '') {
                $jpgTarget = preg_replace('/\.[a-z0-9]+$/i', '.jpg', $target);
                $cmd = escapeshellarg($convertPath) . ' ' . escapeshellarg($target) . ' -auto-orient -quality 88 ' . escapeshellarg($jpgTarget);
                @shell_exec($cmd . ' 2>/dev/null');
                if (is_file($jpgTarget)) {
                    @unlink($target);
                    return 'uploads/inventory/' . basename($jpgTarget);
                }
            }
        }

        return 'uploads/inventory/' . $filename;
    }

    private function findPcRowByPageKey(PDO $pdo, string $inventoryDb, string $pageKey): ?array
    {
        $sql = sprintf('SELECT * FROM `%s`.pc ORDER BY CASE WHEN `inventory_order` IS NULL THEN 0 ELSE 1 END ASC, `inventory_order` ASC, COALESCE(NULLIF(`user`, ""), NULLIF(`computer_name`, ""), NULLIF(`id_inventaris`, ""), "") ASC, COALESCE(NULLIF(`computer_name`, ""), "") ASC, COALESCE(NULLIF(`id_inventaris`, ""), "") ASC', $inventoryDb);
        $stmt = $pdo->query($sql);
        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            if ($this->buildPageKeyFromRow($row) === $pageKey) {
                return $row;
            }
        }
        return null;
    }

    private function findOtherRow(PDO $pdo, string $inventoryDb, array $pcRow, string $itemKey): ?array
    {
        // Prioritaskan query by pc_row_id jika pc row punya id (lebih akurat dari string matching)
        $pcId = isset($pcRow['id']) && (int) $pcRow['id'] > 0 ? (int) $pcRow['id'] : null;
        if ($pcId !== null) {
            $sqlById = sprintf(
                'SELECT * FROM `%s`.perangkat_lain WHERE `pc_row_id` = :pc_id ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC',
                $inventoryDb
            );
            $stmtById = $pdo->prepare($sqlById);
            $stmtById->execute(['pc_id' => $pcId]);
            foreach (($stmtById ? $stmtById->fetchAll() : []) as $row) {
                if ($this->buildItemKey($row) === $itemKey) {
                    return $row;
                }
            }
        }

        // Fallback: string matching user (untuk data lama yang belum punya pc_row_id)
        $user = trim((string) ($pcRow['user'] ?? ''));
        if ($user !== '') {
            $sql = sprintf('SELECT * FROM `%s`.perangkat_lain WHERE `user` = :user AND (`pc_row_id` IS NULL OR `edit_source` != "standalone") ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC', $inventoryDb);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user' => $user]);
        } else {
            $fallback = trim((string) ($pcRow['computer_name'] ?? ''));
            $sql = sprintf('SELECT * FROM `%s`.perangkat_lain WHERE COALESCE(NULLIF(`user`, ""), `unit_kerja`) = :fallback AND (`pc_row_id` IS NULL OR `edit_source` != "standalone") ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC', $inventoryDb);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['fallback' => $fallback]);
        }
        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            if ($this->buildItemKey($row) === $itemKey) {
                return $row;
            }
        }
        return null;
    }

    private function buildWhereByPcRow(array $row): array
    {
        if (trim((string) ($row['user'] ?? '')) !== '') {
            return ['sql' => 'WHERE `user` = :where_user LIMIT 1', 'params' => ['where_user' => $row['user']]];
        }
        if (trim((string) ($row['computer_name'] ?? '')) !== '') {
            return ['sql' => 'WHERE `computer_name` = :where_computer_name LIMIT 1', 'params' => ['where_computer_name' => $row['computer_name']]];
        }
        return ['sql' => 'WHERE `id_inventaris` = :where_id LIMIT 1', 'params' => ['where_id' => $row['id_inventaris'] ?? '']];
    }

    private function pcFieldPayload(array $payload, ?string $existingImage = null): array
    {
        $legacyJenis = $this->clean((string) ($payload['jenis_perangkat'] ?? ''));
        $merk = $this->clean((string) ($payload['merk_perangkat'] ?? ''));
        if ($merk === '' && $legacyJenis !== '' && strtoupper($legacyJenis) !== 'PC') {
            $merk = $legacyJenis;
        }

        return [
            'id_inventaris' => $this->clean((string) ($payload['id_inventaris'] ?? '')),
            'unit_kerja' => $this->clean((string) ($payload['unit_kerja'] ?? '')),
            'jenis_perangkat' => 'PC',
            'merk_perangkat' => $merk,
            'computer_name' => $this->clean((string) ($payload['computer_name'] ?? '')),
            'user' => $this->clean((string) ($payload['user'] ?? '')),
            'processor' => $this->clean((string) ($payload['processor'] ?? '')),
            'ram' => $this->clean((string) ($payload['ram'] ?? '')),
            'kapasitas_harddisk' => $this->clean((string) ($payload['kapasitas_harddisk'] ?? '')),
            'ip_address' => $this->clean((string) ($payload['ip_address'] ?? '')),
            'sistem_operasi' => $this->clean((string) ($payload['sistem_operasi'] ?? '')),
            'licensed_windows' => $this->clean((string) ($payload['licensed_windows'] ?? '')),
            'microsoft_office' => $this->clean((string) ($payload['microsoft_office'] ?? '')),
            'licensed_office' => $this->clean((string) ($payload['licensed_office'] ?? '')),
            'gambar' => $this->clean($this->resolvePcImagePath($payload, $_FILES ?? [], 'PC', $existingImage)),
            'status' => in_array(strtoupper($this->clean((string) ($payload['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper($this->clean((string) ($payload['status'] ?? 'AKTIF'))) : 'AKTIF',
        ];
    }

    private function ensurePcSchema(PDO $pdo, string $inventoryDb): void
    {
        if (!$this->isSafeIdentifier($inventoryDb)) {
            return;
        }

        // Tambah PRIMARY KEY id AUTO_INCREMENT jika belum ada
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "id"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            try {
                $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST', $inventoryDb));
            } catch (Throwable $e) {
                // Mungkin sudah ada primary key lain — coba tanpa PRIMARY KEY
                try { $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `id` BIGINT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)', $inventoryDb)); } catch (Throwable $e2) {}
            }
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "merk_perangkat"');
        $stmt->execute(['schema' => $inventoryDb]);
        $exists = (int) $stmt->fetchColumn() > 0;
        if (!$exists) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `merk_perangkat` VARCHAR(255) NULL AFTER `jenis_perangkat`', $inventoryDb));
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "status"');
        $stmt->execute(['schema' => $inventoryDb]);
        $statusExists = (int) $stmt->fetchColumn() > 0;
        if (!$statusExists) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `status` VARCHAR(100) NULL DEFAULT \'AKTIF\' AFTER `gambar`', $inventoryDb));
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "inventory_order"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `inventory_order` BIGINT NULL AFTER `status`', $inventoryDb));
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "inventory_created_at"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `inventory_created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `inventory_order`', $inventoryDb));
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = "inventory_updated_at"');
        $stmt->execute(['schema' => $inventoryDb]);
        if ((int) $stmt->fetchColumn() < 1) {
            $pdo->exec(sprintf('ALTER TABLE `%s`.pc ADD COLUMN `inventory_updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `inventory_created_at`', $inventoryDb));
        }

        $pdo->exec(sprintf("UPDATE `%s`.pc SET merk_perangkat = TRIM(jenis_perangkat) WHERE (merk_perangkat IS NULL OR merk_perangkat = '') AND jenis_perangkat IS NOT NULL AND TRIM(jenis_perangkat) <> '' AND UPPER(TRIM(jenis_perangkat)) <> 'PC'", $inventoryDb));
        $pdo->exec(sprintf("UPDATE `%s`.pc SET jenis_perangkat = 'PC' WHERE jenis_perangkat IS NULL OR TRIM(jenis_perangkat) = '' OR UPPER(TRIM(jenis_perangkat)) <> 'PC'", $inventoryDb));
        $pdo->exec(sprintf("UPDATE `%s`.pc SET status = 'AKTIF' WHERE status IS NULL OR TRIM(status) = ''", $inventoryDb));
    }

    private function streamInventoryExport(PDO $pdo, array $filters, string $format): void
    {
        $data = $this->model->getAll('inventaris-detail', $filters);
        $context = $this->model->getDivisionContext($pdo, $filters);
        $rows = $this->model->exportRowsForContext($pdo, $context);
        $summary = $data['summary_specs'] ?? [];
        $division = (string) ($context['display_division'] ?? 'DIVISI');
        $updated = (string) ($data['updated'] ?? '-');
        $currentUser = (string) ($context['current_user_name'] ?? '-');
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($division . '_' . ($summary['user'] ?? 'inventaris')));

        if ($format === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
            echo $this->buildStyledPdf($division, $summary, $rows, $currentUser, $updated);
            exit;
        }

        $xlsx = $this->buildExcelXlsx($division, $summary, $rows, $currentUser, $updated);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
        header('Content-Length: ' . strlen($xlsx));
        echo $xlsx;
        exit;
    }

    private function buildExcelXlsx(string $division, array $summary, array $rows, string $userName, string $updated): string
    {
        $sheetRows = [
            [$division],
            ['Last Updated', $updated],
            [],
            ['Computer Name', (string) ($summary['computer_name'] ?? '-'), 'User', (string) (($summary['user'] ?? '') !== '' ? $summary['user'] : $userName)],
            ['Processor', (string) ($summary['processor'] ?? '-'), 'RAM', (string) ($summary['ram'] ?? '-')],
            ['Harddisk', (string) ($summary['harddisk'] ?? '-'), 'IP Address', (string) ($summary['ip'] ?? '-')],
            ['Sistem Operasi', (string) ($summary['os'] ?? '-'), 'Licensed Windows', (string) ($summary['license'] ?? '-')],
            ['MS Office', (string) ($summary['office'] ?? '-'), 'Licensed Office', (string) ($summary['office_license'] ?? '-')],
            [],
            ['No', 'Gambar', 'ID Inventaris', 'Jenis Perangkat', 'Merk Perangkat', 'Unit Kerja', 'Status'],
        ];

        foreach ($rows as $row) {
            $sheetRows[] = [
                (string) ($row['no'] ?? ''),
                trim((string) ($row['image'] ?? '')) !== '' ? '[Gambar]' : '-',
                (string) ($row['id'] ?? '-'),
                (string) ($row['device'] ?? '-'),
                (string) ($row['brand'] ?? '-'),
                (string) ($row['unit'] ?? '-'),
                (string) ($row['status'] ?? '-'),
            ];
        }

        $imageResources = $this->collectExcelImageResources($rows);
        $worksheetXml = $this->buildWorksheetXml($sheetRows, !empty($imageResources));

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        if (!empty($imageResources)) {
            $contentTypes .= '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
            $seenExt = [];
            foreach ($imageResources as $resource) {
                $ext = strtolower((string) ($resource['extension'] ?? 'jpg'));
                if (isset($seenExt[$ext])) {
                    continue;
                }
                $seenExt[$ext] = true;
                $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                $contentTypes .= '<Default Extension="' . $this->xml($ext) . '" ContentType="' . $mime . '"/>';
            }
        }
        $contentTypes .= '</Types>';

        $files = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->xml($division) . '</dc:title><dc:creator>ChatGPT</dc:creator><cp:lastModifiedBy>ChatGPT</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified></cp:coreProperties>',
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Excel</Application></Properties>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inventaris" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $this->buildExcelStylesXml(),
            'xl/worksheets/sheet1.xml' => $worksheetXml,
        ];

        if (!empty($imageResources)) {
            $files['xl/drawings/drawing1.xml'] = $this->buildExcelDrawingXml($imageResources);
            $files['xl/drawings/_rels/drawing1.xml.rels'] = $this->buildExcelDrawingRelsXml($imageResources);
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
            foreach ($imageResources as $index => $resource) {
                $files['xl/media/image' . ($index + 1) . '.' . $resource['extension']] = $resource['data'];
            }
        }

        return $this->buildZipBinary($files);
    }

    private function buildWorksheetXml(array $rows, bool $hasDrawing = false): string
    {
        $xmlRows = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            $rowValues = array_values($row);
            $firstCell = (string) ($rowValues[0] ?? '');
            
            foreach ($rowValues as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                
                $style = '';
                if ($rowIndex === 0 || strpos((string)$value, 'Laporan Data Inventaris') === 0) {
                    $style = ' s="1"'; // Title
                } else if ($firstCell === 'No' || $value === 'No') {
                    $style = ' s="2"'; // Table Header (blue background, white bold text)
                } else if ($value !== null && substr(trim((string)$value), -1) === ':') {
                    $style = ' s="3"'; // Summary Label (light blue background, bold text)
                } else if ($columnIndex > 0 && substr(trim((string)($rowValues[$columnIndex - 1] ?? '')), -1) === ':') {
                    $style = ' s="4"'; // Summary Value
                } else if ($rowIndex === 1 && $columnIndex === 0) {
                    $style = ' s="6"'; // Normal bold (Diexport label)
                } else {
                    // Regular data row
                    if ($columnIndex === 0 || $columnIndex === 1 || $columnIndex === 6) {
                        $style = ' s="5"'; // Centered cell
                    } else {
                        $style = ' s="0"'; // Normal cell
                    }
                }
                
                $ref = $this->excelColumnName($columnIndex + 1) . (string) ($rowIndex + 1);
                $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xml((string) $value) . '</t></is></c>';
            }
            $extra = '';
            if ($rowIndex >= 10) {
                $extra = ' ht="64" customHeight="1"';
            }
            $xmlRows[] = '<row r="' . (string) ($rowIndex + 1) . '"' . $extra . '>' . implode('', $cells) . '</row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"' . ($hasDrawing ? ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"' : '') . '>'
            . '<dimension ref="A1:G' . max(1, count($rows)) . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="2" width="18" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/><col min="4" max="4" width="22" customWidth="1"/><col min="5" max="5" width="22" customWidth="1"/><col min="6" max="6" width="24" customWidth="1"/><col min="7" max="7" width="14" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>';
        if ($hasDrawing) {
            $xml .= '<drawing r:id="rId1"/>';
        }
        $xml .= '</worksheet>';
        return $xml;
    }

    private function collectExcelImageResources(array $rows): array
    {
        $images = [];
        foreach (array_values($rows) as $index => $row) {
            $path = trim((string) ($row['image'] ?? ''));
            if ($path === '') {
                continue;
            }
            $resource = $this->resolveExcelImageResource($path);
            if (!$resource) {
                continue;
            }
            $resource['row_zero_based'] = 10 + $index;
            $resource['col_zero_based'] = 1;
            $images[] = $resource;
        }
        return $images;
    }

    private function resolveExcelImageResource(string $relativePath): ?array
    {
        $relativePath = ltrim($relativePath, '/');
        $base = dirname(__DIR__, 2) . '/public/assets/';
        $fullPath = $base . $relativePath;
        if (!is_file($fullPath)) {
            $alt = preg_replace('/\.(webp)$/i', '.jpg', $fullPath);
            if ($alt && is_file($alt)) {
                $fullPath = $alt;
            }
        }
        if (!is_file($fullPath)) {
            return null;
        }
        $info = @getimagesize($fullPath);
        if (!$info) {
            return null;
        }
        $mime = strtolower((string) ($info['mime'] ?? ''));
        if ($mime === 'image/png') {
            $extension = 'png';
        } elseif ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $extension = 'jpg';
        } else {
            $extension = '';
        }
        if ($extension === '') {
            return null;
        }
        return [
            'width' => (int) ($info[0] ?? 80),
            'height' => (int) ($info[1] ?? 80),
            'extension' => $extension,
            'data' => (string) file_get_contents($fullPath),
        ];
    }

    private function buildExcelDrawingXml(array $images): string
    {
        $anchors = [];
        foreach (array_values($images) as $index => $image) {
            $widthPx = max(36, min(84, (int) ($image['width'] ?? 72)));
            $heightPx = max(36, min(84, (int) ($image['height'] ?? 72)));
            $cx = $widthPx * 9525;
            $cy = $heightPx * 9525;
            $anchors[] = '<xdr:oneCellAnchor>'
                . '<xdr:from><xdr:col>' . (int) ($image['col_zero_based'] ?? 1) . '</xdr:col><xdr:colOff>95250</xdr:colOff><xdr:row>' . (int) ($image['row_zero_based'] ?? 10) . '</xdr:row><xdr:rowOff>95250</xdr:rowOff></xdr:from>'
                . '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>'
                . '<xdr:pic>'
                . '<xdr:nvPicPr><xdr:cNvPr id="' . ($index + 1) . '" name="Image ' . ($index + 1) . '"/><xdr:cNvPicPr/></xdr:nvPicPr>'
                . '<xdr:blipFill><a:blip r:embed="rId' . ($index + 1) . '"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
                . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
                . '</xdr:pic><xdr:clientData/></xdr:oneCellAnchor>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . implode('', $anchors)
            . '</xdr:wsDr>';
    }

    private function buildExcelDrawingRelsXml(array $images): string
    {
        $rels = [];
        foreach (array_values($images) as $index => $image) {
            $rels[] = '<Relationship Id="rId' . ($index + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image' . ($index + 1) . '.' . $this->xml((string) $image['extension']) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . implode('', $rels) . '</Relationships>';
    }

    private function excelColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private function buildZipBinary(array $files): string
    {
        $data = '';
        $central = '';
        $offset = 0;
        $now = getdate();
        $dosTime = (($now['hours'] ?? 0) << 11) | (($now['minutes'] ?? 0) << 5) | intdiv(($now['seconds'] ?? 0), 2);
        $dosDate = (((($now['year'] ?? 1980) - 1980) & 0x7F) << 9) | (($now['mon'] ?? 1) << 5) | ($now['mday'] ?? 1);

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            if ($crc < 0) { $crc += 4294967296; }
            $compressed = gzdeflate($content);
            $nameLen = strlen($name);
            $compLen = strlen($compressed);
            $uncompLen = strlen($content);

            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, $dosTime, $dosDate, $crc, $compLen, $uncompLen, $nameLen, 0) . $name . $compressed;
            $data .= $local;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, $dosTime, $dosDate, $crc, $compLen, $uncompLen, $nameLen, 0, 0, 0, 0, 32, $offset) . $name;
            $offset += strlen($local);
        }

        return $data . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), strlen($data), 0);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function buildStyledPdf(string $division, array $summary, array $rows, string $userName, string $updated): string
    {
        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 26;
        $contentWidth = $pageWidth - ($margin * 2);
        $blue = '0.18 0.41 0.67';
        $lightBlue = '0.90 0.95 0.99';
        $green = '0.24 0.69 0.31';
        $red = '0.90 0.20 0.20';
        $black = '0.12 0.18 0.25';

        $objects = [];
        $objects[1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        $columns = [
            ['key' => 'no', 'title' => 'No', 'width' => 28],
            ['key' => 'image', 'title' => 'Gambar', 'width' => 56],
            ['key' => 'id', 'title' => 'ID Inventaris', 'width' => 118],
            ['key' => 'device', 'title' => 'Jenis Perangkat', 'width' => 156],
            ['key' => 'brand', 'title' => 'Merk', 'width' => 100],
            ['key' => 'unit', 'title' => 'Unit Kerja', 'width' => 260],
            ['key' => 'status', 'title' => 'Status', 'width' => 72],
        ];

        $summaryRows = [
            ['Computer Name', (string) ($summary['computer_name'] ?? '-')],
            ['User', (string) (($summary['user'] ?? '') !== '' ? $summary['user'] : $userName)],
            ['Processor', (string) ($summary['processor'] ?? '-')],
            ['RAM', (string) ($summary['ram'] ?? '-')],
            ['Harddisk', (string) ($summary['harddisk'] ?? '-')],
            ['IP Address', (string) ($summary['ip'] ?? '-')],
            ['Sistem Operasi', (string) ($summary['os'] ?? '-')],
            ['Licensed Windows', (string) ($summary['license'] ?? '-')],
            ['MS Office', (string) ($summary['office'] ?? '-')],
            ['Licensed Office', (string) ($summary['office_license'] ?? '-')],
        ];

        $imageRegistry = $this->collectPdfImageResources($rows);
        $streams = [];
        $streamImageUsage = [];
        $current = [];
        $currentImages = [];
        $y = $pageHeight - $margin;

        $flushPage = function () use (&$streams, &$current, &$streamImageUsage, &$currentImages) {
            $streams[] = implode("\n", $current);
            $streamImageUsage[] = array_values(array_unique($currentImages));
            $current = [];
            $currentImages = [];
        };

        $addText = function (float $x, float $y, string $text, int $fontId = 1, int $fontSize = 10, string $color = '0 0 0') use (&$current) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
            $current[] = 'BT';
            $current[] = sprintf('/F%d %d Tf', $fontId, $fontSize);
            $current[] = $color . ' rg';
            $current[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
            $current[] = '(' . $safe . ') Tj';
            $current[] = 'ET';
        };

        $addRect = function (float $x, float $y, float $w, float $h, ?string $fill = null, ?string $stroke = '0.75 0.81 0.89', float $lineWidth = 1.0) use (&$current) {
            $current[] = sprintf('%.2f w', $lineWidth);
            if ($fill !== null) { $current[] = $fill . ' rg'; }
            if ($stroke !== null) { $current[] = $stroke . ' RG'; }
            $current[] = sprintf('%.2f %.2f %.2f %.2f re', $x, $y, $w, $h);
            $current[] = ($fill !== null && $stroke !== null) ? 'B' : (($fill !== null) ? 'f' : 'S');
        };

        $addImage = function (string $alias, float $x, float $y, float $w, float $h) use (&$current, &$currentImages) {
            $currentImages[] = $alias;
            $current[] = 'q';
            $current[] = sprintf('%.2f 0 0 %.2f %.2f %.2f cm', $w, $h, $x, $y);
            $current[] = '/' . $alias . ' Do';
            $current[] = 'Q';
        };

        $wrapText = function (string $text, int $maxChars): array {
            $text = trim($text) === '' ? '-' : trim($text);
            $parts = preg_split('/\s+/', $text) ?: [];
            $lines = [];
            $line = '';
            foreach ($parts as $part) {
                $candidate = $line === '' ? $part : $line . ' ' . $part;
                if (strlen($candidate) <= $maxChars) {
                    $line = $candidate;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = $part;
                    continue;
                }
                $lines[] = substr($part, 0, $maxChars);
                $line = substr($part, $maxChars);
            }
            if ($line !== '') { $lines[] = $line; }
            return $lines ?: ['-'];
        };

        $startNewPage = function () use (&$y, $pageHeight, $margin, &$current) {
            $current[] = 'q';
            $y = $pageHeight - $margin;
        };

        $finishPage = function () use (&$current, $flushPage) {
            $current[] = 'Q';
            $flushPage();
        };

        $startNewPage();
        $addText($margin, $y - 12, 'SPMT INVENTORY CONTROL', 2, 18, $blue);
        $addText($margin, $y - 36, $division, 2, 22, $blue);
        $addText($margin, $y - 56, 'Last Updated: ' . $updated, 1, 10, $black);
        $y -= 74;

        $summaryX = $margin;
        $summaryWidth = $contentWidth / 2;
        $rowHeight = 24;
        for ($i = 0; $i < count($summaryRows); $i += 2) {
            $left = $summaryRows[$i];
            $right = $summaryRows[$i + 1] ?? ['', ''];
            $boxY = $y - $rowHeight;
            $addRect($summaryX, $boxY, $summaryWidth - 6, $rowHeight, $lightBlue);
            $addRect($summaryX + $summaryWidth, $boxY, $summaryWidth - 6, $rowHeight, $lightBlue);
            $addText($summaryX + 8, $boxY + 8, $left[0] . ':', 2, 9, $blue);
            $addText($summaryX + 110, $boxY + 8, $left[1], 1, 9, $black);
            $addText($summaryX + $summaryWidth + 8, $boxY + 8, $right[0] . ':', 2, 9, $blue);
            $addText($summaryX + $summaryWidth + 110, $boxY + 8, $right[1], 1, 9, $black);
            $y -= $rowHeight + 6;
        }

        $y -= 10;
        $headerHeight = 24;
        $tableX = $margin;
        $drawHeader = function () use (&$y, $headerHeight, $tableX, $columns, $addRect, $addText, $blue) {
            $tableY = $y - $headerHeight;
            $x = $tableX;
            foreach ($columns as $column) {
                $addRect($x, $tableY, $column['width'], $headerHeight, $blue, $blue, 0.8);
                $addText($x + 6, $tableY + 7, $column['title'], 2, 8, '1 1 1');
                $x += $column['width'];
            }
            $y = $tableY - 4;
        };
        $drawHeader();

        foreach ($rows as $row) {
            $wrapped = [];
            $maxLines = 1;
            foreach ($columns as $column) {
                if ($column['key'] === 'image') { continue; }
                $value = (string) ($row[$column['key']] ?? '-');
                $maxChars = max(6, (int) floor($column['width'] / 6.8));
                $wrapped[$column['key']] = $wrapText($value, $maxChars);
                $maxLines = max($maxLines, count($wrapped[$column['key']]));
            }
            $cellHeight = max(46, ($maxLines * 12) + 12);
            if (($y - $cellHeight) < $margin) {
                $finishPage();
                $startNewPage();
                $drawHeader();
            }

            $rowY = $y - $cellHeight;
            $x = $tableX;
            foreach ($columns as $column) {
                $fill = null;
                if ($column['key'] === 'status') {
                    $statusValue = strtoupper(trim((string) ($row['status'] ?? '')));
                    if ($statusValue === 'AKTIF') {
                        $fill = '0.90 0.97 0.93';
                    } elseif ($statusValue === 'RUSAK') {
                        $fill = '0.99 0.91 0.90';
                    } else {
                        $fill = '1.00 0.95 0.88';
                    }
                }
                $addRect($x, $rowY, $column['width'], $cellHeight, $fill, '0.75 0.81 0.89', 0.8);
                if ($column['key'] === 'image') {
                    $imageKey = (string) ($row['image'] ?? '');
                    $img = $imageRegistry[$imageKey] ?? null;
                    if ($img) {
                        $fit = min($column['width'] - 10, $cellHeight - 10);
                        $drawW = $fit;
                        $drawH = $fit;
                        $posX = $x + (($column['width'] - $drawW) / 2);
                        $posY = $rowY + (($cellHeight - $drawH) / 2);
                        $addImage($img['alias'], $posX, $posY, $drawW, $drawH);
                    } else {
                        $addText($x + 18, $rowY + ($cellHeight / 2) - 4, '-', 1, 9, $black);
                    }
                    $x += $column['width'];
                    continue;
                }

                if ($column['key'] === 'status') {
                    $statusValue = strtoupper(trim((string) ($row['status'] ?? '')));
                    if ($statusValue === 'AKTIF') {
                        $textColor = '0.15 0.44 0.23';
                    } elseif ($statusValue === 'RUSAK') {
                        $textColor = '0.72 0.11 0.11';
                    } else {
                        $textColor = '0.90 0.32 0.00';
                    }
                } else {
                    $textColor = $black;
                }
                $font = $column['key'] === 'status' ? 2 : 1;
                $linesCount = count($wrapped[$column['key']]);
                $textHeight = $linesCount * 11 - 3;
                $lineY = $rowY + (($cellHeight - $textHeight) / 2) + $textHeight - 8;
                foreach ($wrapped[$column['key']] as $line) {
                    $addText($x + 6, $lineY, $line, $font, 8, $textColor);
                    $lineY -= 11;
                }
                $x += $column['width'];
            }
            $y = $rowY - 4;
        }

        $finishPage();

        $nextId = 3;
        foreach ($imageRegistry as $key => $img) {
            $imageRegistry[$key]['object_id'] = $nextId++;
            $objects[$imageRegistry[$key]['object_id']] = "<< /Type /XObject /Subtype /Image /Width {$img['width']} /Height {$img['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($img['data']) . " >>\nstream\n" . $img['data'] . "\nendstream";
        }

        $contentIds = [];
        $pageIds = [];
        foreach ($streams as $stream) {
            $contentId = $nextId++;
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $contentIds[] = $contentId;
            $pageIds[] = $nextId++;
        }
        $pagesId = $nextId++;
        foreach ($pageIds as $index => $pageId) {
            $aliases = $streamImageUsage[$index] ?? [];
            $xObjects = '';
            foreach ($aliases as $alias) {
                foreach ($imageRegistry as $img) {
                    if ($img['alias'] === $alias) {
                        $xObjects .= '/' . $alias . ' ' . $img['object_id'] . ' 0 R ';
                        break;
                    }
                }
            }
            $resourceXObjects = $xObjects !== '' ? ' /XObject << ' . trim($xObjects) . ' >>' : '';
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 1 0 R /F2 2 0 R >>{$resourceXObjects} >> /Contents {$contentIds[$index]} 0 R >>";
        }
        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = "<< /Type /Pages /Count " . count($pageIds) . " /Kids [ {$kids} ] >>";
        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($catalogId + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $catalogId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer << /Size " . ($catalogId + 1) . " /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function collectPdfImageResources(array $rows): array
    {
        $images = [];
        $counter = 1;
        foreach ($rows as $row) {
            $path = trim((string) ($row['image'] ?? ''));
            if ($path === '' || isset($images[$path])) {
                continue;
            }
            $resource = $this->resolvePdfImageResource($path);
            if (!$resource) {
                continue;
            }
            $resource['alias'] = 'Im' . $counter++;
            $images[$path] = $resource;
        }
        return $images;
    }

    private function resolvePdfImageResource(string $relativePath): ?array
    {
        $relativePath = ltrim($relativePath, '/');
        $base = dirname(__DIR__, 2) . '/public/assets/';
        $fullPath = $base . $relativePath;
        if (!is_file($fullPath)) {
            $alt = preg_replace('/\.(png|webp)$/i', '.jpg', $fullPath);
            if ($alt && is_file($alt)) {
                $fullPath = $alt;
            }
        }
        if (!is_file($fullPath)) {
            return null;
        }
        $info = @getimagesize($fullPath);
        if (!$info) {
            return null;
        }

        $mime = strtolower((string) ($info['mime'] ?? ''));
        if ($mime !== 'image/jpeg') {
            $convertPath = trim((string) shell_exec('command -v magick || command -v convert'));
            if ($convertPath !== '') {
                $jpgTarget = preg_replace('/\.[a-z0-9]+$/i', '.jpg', $fullPath);
                if (!is_file($jpgTarget)) {
                    $cmd = escapeshellarg($convertPath) . ' ' . escapeshellarg($fullPath) . ' -auto-orient -quality 88 ' . escapeshellarg($jpgTarget);
                    @shell_exec($cmd . ' 2>/dev/null');
                }
                if (is_file($jpgTarget)) {
                    $fullPath = $jpgTarget;
                    $info = @getimagesize($fullPath) ?: $info;
                    $mime = strtolower((string) ($info['mime'] ?? 'image/jpeg'));
                }
            }
        }
        if ($mime !== 'image/jpeg') {
            return null;
        }
        return [
            'width' => (int) ($info[0] ?? 80),
            'height' => (int) ($info[1] ?? 80),
            'data' => (string) file_get_contents($fullPath),
        ];
    }

    private function buildDetailUrl(array $filters): string
    {
        $query = [
            'page' => 'inventaris-detail',
            'division_code' => (string) ($filters['division_code'] ?? ''),
            'display_division' => (string) ($filters['display_division'] ?? ''),
            'user_page' => (string) ($filters['user_page'] ?? 1),
            'user' => (string) ($filters['user'] ?? ''),
            'focus_item' => (string) ($filters['focus_item'] ?? ''),
        ];
        if (!empty($filters['after_add_inventory'])) {
            $query['after_add_inventory'] = '1';
        }
        return 'index.php?' . http_build_query($query);
    }

    private function buildPcFocusItemFromInput(array $payload): string
    {
        return 'pc:' . md5(strtolower(trim((string) ($payload['id_inventaris'] ?? ''))) . '|' . strtolower(trim((string) ($payload['computer_name'] ?? ''))));
    }

    private function buildPageKeyFromInput(array $payload): string
    {
        $user = trim((string) ($payload['user'] ?? ''));
        if ($user !== '') { return 'user:' . mb_strtolower($user); }
        $computer = trim((string) ($payload['computer_name'] ?? ''));
        if ($computer !== '') { return 'computer:' . mb_strtolower($computer); }
        return 'id:' . mb_strtolower(trim((string) ($payload['id_inventaris'] ?? '')));
    }

    private function buildPageKeyFromRow(array $row): string
    {
        $user = trim((string) ($row['user'] ?? ''));
        if ($user !== '') { return 'user:' . mb_strtolower($user); }
        $computer = trim((string) ($row['computer_name'] ?? ''));
        if ($computer !== '') { return 'computer:' . mb_strtolower($computer); }
        return 'id:' . mb_strtolower(trim((string) ($row['id_inventaris'] ?? '')));
    }

    private function buildItemKey(array $row): string
    {
        return md5(strtolower(trim((string) ($row['id_inventaris'] ?? ''))) . '|' . strtolower(trim((string) ($row['jenis_perangkat'] ?? ''))) . '|' . strtolower(trim((string) ($row['unit_kerja'] ?? ''))) . '|' . strtolower(trim((string) ($row['user'] ?? ''))) . '|' . strtolower(trim((string) ($row['merk_perangkat'] ?? ''))));
    }

    private function defaultImageForDevice(?string $device): string
    {
        $device = strtoupper(trim((string) $device));
        if (strpos($device, 'MONITOR') !== false) return 'images/inv-monitor.jpg';
        if (strpos($device, 'KEYBOARD') !== false) return 'images/inv-keyboard.jpg';
        if (strpos($device, 'MOUSE') !== false) return 'images/inv-mouse.jpg';
        if (strpos($device, 'WEBCAM') !== false) return 'images/inv-webcam.jpg';
        if (strpos($device, 'PRINTER') !== false) return 'images/inv-printer.jpg';
        if (strpos($device, 'SPEAKER') !== false) return 'images/inv-speaker.jpg';
        return 'images/inv-default.jpg';
    }

    private function clean(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function isSafeIdentifier(string $value): bool
    {
        return $value !== '' && (bool) preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    private function postProcessHtml(string $html, string $page, array $data): string
    {
        if ($page === 'splash') {
            $redirectUrl = 'index.php?page=login';
            $script = '<script>setTimeout(function(){window.location.href=' . json_encode($redirectUrl) . ';},1500);</script>';
            return stripos($html, '</body>') !== false ? str_ireplace('</body>', $script . '</body>', $html) : $html . $script;
        }
        if ($page === 'inventaris-detail' && !empty($data['current_display_division'])) {
            $safeTitle = htmlspecialchars((string) $data['current_display_division'], ENT_QUOTES, 'UTF-8');
            $html = str_replace('DIVISI TEKNOLOGI INFORMASI', $safeTitle, $html);
        }
        if ($page === 'log-barang' && isset($data['inventory_flow']['data'][0], $data['inventory_flow']['data'][1])) {
            $html = str_replace('BARANG MASUK<br>20', 'BARANG MASUK<br>' . (int)$data['inventory_flow']['data'][0], $html);
            $html = str_replace('BARANG KELUAR<br>5', 'BARANG KELUAR<br>' . (int)$data['inventory_flow']['data'][1], $html);
        }
        if ($page === 'data-inventaris' && !empty($data['category_cards'])) {
            $links = array_values(array_filter(array_map(static fn(array $card): string => (string) ($card['route_url'] ?? ''), $data['category_cards'])));
            $index = 0;
            $html = preg_replace_callback('/href="index\.php\?page=inventaris-detail"/', static function () use (&$index, $links): string {
                $replacement = $links[$index] ?? 'index.php?page=inventaris-detail';
                $index++;
                return 'href="' . htmlspecialchars($replacement, ENT_QUOTES, 'UTF-8') . '"';
            }, $html, count($links)) ?? $html;
        }
        return $html;
    }
    private function ensureCctvInventarisSchema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `inventaris_cctv` (
            `id`             bigint(20)   NOT NULL AUTO_INCREMENT,
            `nama_cctv`      varchar(255) NOT NULL,
            `kode_cctv`      varchar(100) NOT NULL,
            `lokasi`         varchar(255) DEFAULT NULL,
            `jumlah`         int(11)      NOT NULL DEFAULT 1,
            `status`         varchar(50)  NOT NULL DEFAULT 'AKTIF',
            `division_code`  varchar(100) DEFAULT NULL,
            `division_label` varchar(255) DEFAULT NULL,
            `keterangan`     text         DEFAULT NULL,
            `created_at`     datetime     NOT NULL DEFAULT current_timestamp(),
            `updated_at`     datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_inventaris_cctv_kode` (`kode_cctv`),
            KEY `idx_inventaris_cctv_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
 
    /**
     * Pastikan tabel inventaris_printer ada di db_spmt_app_backend
     */
    private function ensurePrinterInventarisSchema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `inventaris_printer` (
            `id`             bigint(20)   NOT NULL AUTO_INCREMENT,
            `id_inventaris`  varchar(255) DEFAULT NULL,
            `merk`           varchar(255) DEFAULT NULL,
            `tipe_model`     varchar(255) DEFAULT NULL,
            `unit_kerja`     varchar(255) DEFAULT NULL,
            `status`         varchar(50)  NOT NULL DEFAULT 'AKTIF',
            `division_code`  varchar(100) DEFAULT NULL,
            `division_label` varchar(255) DEFAULT NULL,
            `gambar`         varchar(255) DEFAULT NULL,
            `keterangan`     text         DEFAULT NULL,
            `created_at`     datetime     NOT NULL DEFAULT current_timestamp(),
            `updated_at`     datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_inventaris_printer_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
 
    /**
     * Insert satu baris inventaris CCTV
     */
    private function insertCctvInventaris(PDO $pdo, array $payload): void
    {
        $this->ensureCctvInventarisSchema($pdo);
 
        $namaCctv  = $this->requiredText(trim((string) ($payload['nama_cctv'] ?? '')), 'Nama CCTV wajib diisi.');
        $kodeCctv  = $this->requiredText(trim((string) ($payload['kode_cctv'] ?? '')), 'Kode CCTV wajib diisi.');
        $lokasi    = $this->clean((string) ($payload['lokasi'] ?? ''));
        $jumlah    = max(1, (int) ($payload['jumlah'] ?? 1));
        $status    = in_array(strtoupper(trim((string) ($payload['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK', 'NONAKTIF'], true)
                     ? strtoupper(trim((string) ($payload['status'] ?? 'AKTIF')))
                     : 'AKTIF';
        $divCode   = $this->clean((string) ($payload['division_code'] ?? ''));
        $divLabel  = $this->clean((string) ($payload['division_label'] ?? ''));
        $ket       = $this->clean((string) ($payload['keterangan'] ?? ''));
 
        $stmt = $pdo->prepare('INSERT INTO `inventaris_cctv`
            (nama_cctv, kode_cctv, lokasi, jumlah, status, division_code, division_label, keterangan)
            VALUES (:nama_cctv, :kode_cctv, :lokasi, :jumlah, :status, :division_code, :division_label, :keterangan)');
        $stmt->execute([
            'nama_cctv'      => $namaCctv,
            'kode_cctv'      => $kodeCctv,
            'lokasi'         => $lokasi,
            'jumlah'         => $jumlah,
            'status'         => $status,
            'division_code'  => $divCode,
            'division_label' => $divLabel,
            'keterangan'     => $ket,
        ]);
    }
 
    /**
     * Insert satu baris inventaris Printer
     */
    private function insertPrinterInventaris(PDO $pdo, array $payload, ?string $gambarPath): void
    {
        $this->ensurePrinterInventarisSchema($pdo);
 
        $merk      = $this->requiredText(trim((string) ($payload['merk'] ?? '')), 'Merk printer wajib diisi.');
        $tipe      = $this->requiredText(trim((string) ($payload['tipe_model'] ?? '')), 'Tipe/model printer wajib diisi.');
        $idInv     = $this->clean((string) ($payload['id_inventaris'] ?? ''));
        $unitKerja = $this->clean((string) ($payload['unit_kerja'] ?? ''));
        $status    = in_array(strtoupper(trim((string) ($payload['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK', 'NONAKTIF'], true)
                     ? strtoupper(trim((string) ($payload['status'] ?? 'AKTIF')))
                     : 'AKTIF';
        $divCode   = $this->clean((string) ($payload['division_code'] ?? ''));
        $divLabel  = $this->clean((string) ($payload['division_label'] ?? ''));
        $ket       = $this->clean((string) ($payload['keterangan'] ?? ''));
 
        $stmt = $pdo->prepare('INSERT INTO `inventaris_printer`
            (id_inventaris, merk, tipe_model, unit_kerja, status, division_code, division_label, gambar, keterangan)
            VALUES (:id_inventaris, :merk, :tipe_model, :unit_kerja, :status, :division_code, :division_label, :gambar, :keterangan)');
        $stmt->execute([
            'id_inventaris'  => $idInv,
            'merk'           => $merk,
            'tipe_model'     => $tipe,
            'unit_kerja'     => $unitKerja,
            'status'         => $status,
            'division_code'  => $divCode,
            'division_label' => $divLabel,
            'gambar'         => $gambarPath,
            'keterangan'     => $ket,
        ]);
    }

    /**
     * Google Form Integrasi - Memastikan tabel settings ada
     */
    private function ensureSettingsTable(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    /**
     * Google Form Integrasi - Get Setting Value
     */
    private function getSetting(PDO $pdo, string $key, string $default = ''): string
    {
        $this->ensureSettingsTable($pdo);
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['setting_value'] : $default;
    }

    /**
     * Google Form Integrasi - Set Setting Value
     */
    private function setSetting(PDO $pdo, string $key, string $value): void
    {
        $this->ensureSettingsTable($pdo);
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value_update');
        $stmt->execute(['key' => $key, 'value' => $value, 'value_update' => $value]);
    }

    /**
     * Google Form Integrasi - Fetch URL Content
     */
    private function fetchUrl(string $url): ?string
    {
        $this->lastFetchError = '';
        $errors = [];

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $data = curl_exec($ch);
            $errNum = curl_errno($ch);
            $errMsg = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errNum === 0 && $httpCode === 200) {
                return $data;
            }

            if ($errNum !== 0) {
                $errors[] = "cURL error (code $errNum): $errMsg";
            } else {
                $errors[] = "HTTP status code $httpCode";
            }
        } else {
            $errors[] = "cURL extension is not loaded";
        }
        
        // Try fallback file_get_contents
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                "timeout" => 30,
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
                $firstHeader = $http_response_header[0];
                if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/i', $firstHeader, $matches)) {
                    $statusCode = (int)$matches[1];
                    if ($statusCode === 200) {
                        return $data;
                    }
                    $errors[] = "Fallback HTTP status code $statusCode";
                }
            } else {
                return $data;
            }
        } else {
            $lastError = error_get_last();
            $msg = $lastError ? $lastError['message'] : 'Unknown stream error';
            $errors[] = "file_get_contents error: $msg";
        }

        $this->lastFetchError = implode('; ', $errors);
        return null;
    }

    /**
     * Google Form Integrasi - Ekstrak File ID Google Drive
     */
    private function extractGoogleDriveId(string $url): ?string
    {
        if (preg_match('/[?&]id=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/file\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Google Form Integrasi - Normalisasi Divisi
     */
    private function normalizeDivisionFromMasterForImport(PDO $pdo, string $division): string
    {
        $division = trim(preg_replace('/\s*-\s*(SPMT|SUBREG)\s*$/i', '', $division));
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

    /**
     * Google Form Integrasi - Generate Ticket No
     */
    private function generateTicketNoForImport(PDO $pdo, string $tanggal): string
    {
        $cleanDate = str_replace('-', '', $tanggal);
        $yymmdd = (strlen($cleanDate) === 8) ? substr($cleanDate, 2) : $cleanDate;
        $prefix = 'TSR-' . $yymmdd . '-';
        
        $stmt = $pdo->prepare('SELECT ticket_no FROM it_support_request WHERE ticket_no LIKE :prefix ORDER BY ticket_no DESC LIMIT 1');
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastTicket = $stmt->fetchColumn();
        
        if ($lastTicket) {
            $parts = explode('-', $lastTicket);
            $lastNum = (int) end($parts);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Google Form Integrasi - Sinkronisasi data dari Google Sheets CSV
     */
    public function syncGoogleFormSubmissions(PDO $pdo, bool $isAjax = false, bool $isSilent = false): int
    {
        $url = $this->getSetting($pdo, 'google_sheet_csv_url');
        if ($url === '') {
            $msg = 'Google Sheet CSV URL belum dikonfigurasi di Pengaturan.';
            if ($isSilent) {
                return 0;
            }
            if (php_sapi_name() === 'cli') {
                echo "[ERROR] " . $msg . "\n";
                return 0;
            }
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg, 'imported' => 0]);
                return 0;
            }
            $_SESSION['flash'] = ['type' => 'error', 'message' => $msg];
            return 0;
        }

        $csvData = $this->fetchUrl($url);
        if (!$csvData) {
            $detailedError = $this->lastFetchError ? " Detail error: " . $this->lastFetchError : "";
            $msg = 'Gagal mengambil data dari Google Sheets. Pastikan dokumen telah dipublikasikan ke web sebagai CSV dan URL benar.' . $detailedError;
            if ($isSilent) {
                return 0;
            }
            if (php_sapi_name() === 'cli') {
                echo "[ERROR] " . $msg . "\n";
                return 0;
            }
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg, 'imported' => 0]);
                return 0;
            }
            $_SESSION['flash'] = ['type' => 'error', 'message' => $msg];
            return 0;
        }

        $lines = explode("\n", str_replace("\r", "", $csvData));
        if (count($lines) < 2) {
            $msg = 'Tidak ada respon yang ditemukan di Google Sheet.';
            if ($isSilent) {
                return 0;
            }
            if (php_sapi_name() === 'cli') {
                echo "[INFO] " . $msg . "\n";
                return 0;
            }
            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => $msg, 'imported' => 0]);
                return 0;
            }
            $_SESSION['flash'] = ['type' => 'info', 'message' => $msg];
            return 0;
        }

        // Auto-detect header row by scanning up to the first 5 rows
        $headerRowIdx = -1;
        $indices = [
            'timestamp' => -1,
            'email' => -1,
            'nama' => -1,
            'divisi' => -1,
            'aset' => -1,
            'lokasi' => -1,
            'deskripsi' => -1,
            'dokumentasi' => -1
        ];
        $header = [];

        $scanLimit = min(5, count($lines));
        for ($r = 0; $r < $scanLimit; $r++) {
            $rowCells = @str_getcsv($lines[$r]);
            if (!$rowCells || count($rowCells) === 0) {
                continue;
            }

            $tempIndices = [
                'timestamp' => -1,
                'email' => -1,
                'nama' => -1,
                'divisi' => -1,
                'aset' => -1,
                'lokasi' => -1,
                'deskripsi' => -1,
                'dokumentasi' => -1
            ];

            foreach ($rowCells as $idx => $colName) {
                $colNameClean = strtolower(trim($colName));
                if (strpos($colNameClean, 'timestamp') !== false || strpos($colNameClean, 'tanggal') !== false || strpos($colNameClean, 'waktu') !== false) {
                    $tempIndices['timestamp'] = $idx;
                } elseif (strpos($colNameClean, 'email') !== false) {
                    $tempIndices['email'] = $idx;
                } elseif (strpos($colNameClean, 'nama') !== false) {
                    $tempIndices['nama'] = $idx;
                } elseif (strpos($colNameClean, 'divisi') !== false || strpos($colNameClean, 'unit') !== false) {
                    $tempIndices['divisi'] = $idx;
                } elseif (strpos($colNameClean, 'aset') !== false || strpos($colNameClean, 'barang') !== false || strpos($colNameClean, 'perangkat') !== false) {
                    $tempIndices['aset'] = $idx;
                } elseif (strpos($colNameClean, 'lokasi') !== false || strpos($colNameClean, 'tempat') !== false) {
                    $tempIndices['lokasi'] = $idx;
                } elseif (strpos($colNameClean, 'dokumentasi') !== false || strpos($colNameClean, 'gambar') !== false || strpos($colNameClean, 'foto') !== false || strpos($colNameClean, 'upload') !== false || strpos($colNameClean, 'file') !== false) {
                    $tempIndices['dokumentasi'] = $idx;
                } elseif (strpos($colNameClean, 'deskripsi') !== false || strpos($colNameClean, 'kerusakan') !== false || strpos($colNameClean, 'keluhan') !== false || strpos($colNameClean, 'detail') !== false || strpos($colNameClean, 'masalah') !== false) {
                    $tempIndices['deskripsi'] = $idx;
                }
            }

            // A row is the header row if it contains at least timestamp, email, and deskripsi
            if ($tempIndices['timestamp'] !== -1 && $tempIndices['email'] !== -1 && $tempIndices['deskripsi'] !== -1) {
                $headerRowIdx = $r;
                $indices = $tempIndices;
                $header = $rowCells;
                break;
            }
        }

        if ($headerRowIdx === -1) {
            $msg = 'Struktur kolom Google Sheet tidak cocok. Pastikan terdapat kolom Timestamp, Email, dan Deskripsi.';
            if ($isSilent) {
                return 0;
            }
            if (php_sapi_name() === 'cli') {
                echo "[ERROR] " . $msg . "\n";
                return 0;
            }
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg, 'imported' => 0]);
                return 0;
            }
            $_SESSION['flash'] = ['type' => 'error', 'message' => $msg];
            return 0;
        }

        require_once __DIR__ . '/../models/ItSupportPublicModel.php';
        $publicModel = new ItSupportPublicModel();

        $successCount = 0;
        $duplicateCount = 0;

        for ($i = $headerRowIdx + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $row = @str_getcsv($line);
            if (!$row || count($row) <= max($indices)) {
                continue;
            }

            $email = trim((string) $row[$indices['email']]);
            $deskripsi = trim((string) $row[$indices['deskripsi']]);
            if ($email === '' || $deskripsi === '') {
                continue;
            }

            $timestampStr = trim((string) $row[$indices['timestamp']]);
            // Try explicit format with d/m/Y (preferred for Indonesian date format outputted by GSheets)
            $dt = DateTime::createFromFormat('d/m/Y H:i:s', $timestampStr);
            if (!$dt) {
                $dt = DateTime::createFromFormat('d/m/Y H.i.s', $timestampStr);
            }
            if (!$dt) {
                $dt = DateTime::createFromFormat('d-m-Y H:i:s', $timestampStr);
            }
            if ($dt) {
                $time = $dt->getTimestamp();
            } else {
                $time = strtotime($timestampStr);
                if ($time === false) {
                    $time = time();
                }
            }
            $tanggal = date('Y-m-d', $time);
            $jam = date('H:i:s', $time);

            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM it_support_request WHERE email_pelapor = :email AND tanggal = :tanggal AND jam = :jam AND deskripsi_kerusakan = :deskripsi');
            $checkStmt->execute([
                'email' => $email,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'deskripsi' => $deskripsi
            ]);
            if (((int) $checkStmt->fetchColumn()) > 0) {
                $duplicateCount++;
                continue;
            }

            $nama = $indices['nama'] !== -1 ? trim((string) $row[$indices['nama']]) : '';
            $divisi = $indices['divisi'] !== -1 ? trim((string) $row[$indices['divisi']]) : '';
            $aset = $indices['aset'] !== -1 ? trim((string) $row[$indices['aset']]) : '';
            $lokasi = $indices['lokasi'] !== -1 ? trim((string) $row[$indices['lokasi']]) : '';
            $dokumentasi = $indices['dokumentasi'] !== -1 ? trim((string) $row[$indices['dokumentasi']]) : '';

            // Google Drive Auto-Downloader
            if (preg_match('#^https?://#i', $dokumentasi) && strpos($dokumentasi, 'drive.google.com') !== false) {
                $driveId = $this->extractGoogleDriveId($dokumentasi);
                if ($driveId) {
                    $localDir = __DIR__ . '/../../public/uploads/it-support';
                    if (!is_dir($localDir)) {
                        mkdir($localDir, 0777, true);
                    }
                    
                    $localPath = null;
                    $found = false;
                    foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                        $checkPath = "public/uploads/it-support/gform_{$driveId}.{$ext}";
                        if (file_exists(__DIR__ . '/../../' . $checkPath)) {
                            $localPath = $checkPath;
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        $dokumentasi = $localPath;
                    } else {
                        $downloadUrl = "https://docs.google.com/uc?export=download&id={$driveId}";
                        $fileData = $this->fetchUrl($downloadUrl);
                        if ($fileData && strpos($fileData, '<!DOCTYPE html>') === false && strpos($fileData, '<html') === false) {
                            $ext = 'jpg';
                            $newLocalPath = "public/uploads/it-support/gform_{$driveId}.{$ext}";
                            if (file_put_contents(__DIR__ . '/../../' . $newLocalPath, $fileData) !== false) {
                                $dokumentasi = $newLocalPath;
                            }
                        }
                    }
                }
            }

            $reporterUserId = null;
            $user = $publicModel->findUserByEmail($pdo, $email);
            if ($user) {
                if ($nama === '') {
                    $nama = (string) ($user['nama_lengkap'] ?? '');
                }
                if ($divisi === '') {
                    $divisi = (string) ($user['division_label'] ?? $user['unit_kerja_default'] ?? '');
                }
                $reporterUserId = (int) ($user['id'] ?? 0);
            }

            $normalizedDivision = $this->normalizeDivisionFromMasterForImport($pdo, $divisi);
            if ($normalizedDivision !== '') {
                $divisi = $normalizedDivision;
            }

            $ticketNo = $this->generateTicketNoForImport($pdo, $tanggal);

            $insertSql = 'INSERT INTO it_support_request (
                ticket_no, reporter_user_id, tanggal, jam, email_pelapor, email_verified, nama_pelapor, divisi,
                aset_yang_perlu_diperbaiki, lokasi_perbaikan, deskripsi_kerusakan, dokumentasi_kerusakan, status
            ) VALUES (
                :ticket_no, :reporter_user_id, :tanggal, :jam, :email_pelapor, 1, :nama_pelapor, :divisi,
                :aset_yang_perlu_diperbaiki, :lokasi_perbaikan, :deskripsi_kerusakan, :dokumentasi_kerusakan, "NOT YET"
            )';
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'ticket_no' => $ticketNo,
                'reporter_user_id' => $reporterUserId,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'email_pelapor' => $email,
                'nama_pelapor' => $nama !== '' ? $nama : explode('@', $email)[0],
                'divisi' => $divisi !== '' ? $divisi : 'UMUM',
                'aset_yang_perlu_diperbaiki' => $aset !== '' ? $aset : 'Perangkat IT',
                'lokasi_perbaikan' => $lokasi !== '' ? $lokasi : 'Kantor Cabang',
                'deskripsi_kerusakan' => $deskripsi,
                'dokumentasi_kerusakan' => $dokumentasi !== '' ? $dokumentasi : null
            ]);

            $successCount++;
        }

        $msg = $successCount > 0 
            ? "Sinkronisasi berhasil! {$successCount} tiket baru telah diimport."
            : "Tidak ada data baru dari Google Sheet.";
        
        if ($isSilent) {
            return $successCount;
        }
        if (php_sapi_name() === 'cli') {
            echo "[SUCCESS] " . $msg . "\n";
            return $successCount;
        }
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => $msg, 'imported' => $successCount]);
            return $successCount;
        }
        $_SESSION['flash'] = ['type' => $successCount > 0 ? 'success' : 'info', 'message' => $msg];
        return $successCount;
    }

    private function ensureAlertTable(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `alert_notifications` (
          `id`          bigint(20)   NOT NULL AUTO_INCREMENT,
          `kategori`    varchar(50)  NOT NULL COMMENT 'PC / CCTV / KELUHAN / LOG / MONITORING',
          `level`       varchar(20)  NOT NULL DEFAULT 'INFO' COMMENT 'KRITIS / PERINGATAN / INFO',
          `judul`       varchar(255) NOT NULL COMMENT 'Judul singkat alert',
          `keterangan`  text         NOT NULL COMMENT 'Penjelasan detail alert',
          `link_url`    varchar(500) DEFAULT NULL COMMENT 'URL halaman terkait',
          `is_read`     tinyint(1)   NOT NULL DEFAULT 0,
          `status_tindak_lanjut` varchar(20) NOT NULL DEFAULT 'BELUM_DITANGANI' COMMENT 'BELUM_DITANGANI / SEDANG_DITANGANI / SELESAI',
          `ditangani_oleh` varchar(100) DEFAULT NULL,
          `ditangani_at` datetime     DEFAULT NULL,
          `dibaca_oleh` varchar(100) DEFAULT NULL COMMENT 'username yang menandai sudah dibaca',
          `dibaca_at`   datetime     DEFAULT NULL,
          `created_at`  datetime     NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_alert_kategori` (`kategori`),
          KEY `idx_alert_level` (`level`),
          KEY `idx_alert_is_read` (`is_read`),
          KEY `idx_alert_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);
        
        try {
            $pdo->exec("ALTER TABLE `alert_notifications` ADD COLUMN `status_tindak_lanjut` VARCHAR(20) NOT NULL DEFAULT 'BELUM_DITANGANI' COMMENT 'BELUM_DITANGANI / SEDANG_DITANGANI / SELESAI' AFTER `is_read`");
        } catch (Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE `alert_notifications` ADD COLUMN `ditangani_oleh` VARCHAR(100) DEFAULT NULL AFTER `status_tindak_lanjut`");
        } catch (Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE `alert_notifications` ADD COLUMN `ditangani_at` DATETIME DEFAULT NULL AFTER `ditangani_oleh`");
        } catch (Throwable $e) {}

        try {
            $pdo->exec("DELETE FROM alert_notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (Throwable $e) {}
    }

    private function handleNotifikasiAlertAction(): void
    {
        if (!AuthController::canAccessAlertSystem()) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }
        
        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
            header('Location: index.php?page=notifikasi-alert');
            exit;
        }
        
        $this->ensureAlertTable($pdo);

        try {
            if ($action === 'update_status_tindak_lanjut') {
                $id = (int) ($_POST['id'] ?? 0);
                $statusBaru = strtoupper(trim((string) ($_POST['status_tindak_lanjut'] ?? '')));
                $validStatus = ['BELUM_DITANGANI', 'SEDANG_DITANGANI', 'SELESAI'];
                if ($id > 0 && in_array($statusBaru, $validStatus, true)) {
                    $stmt = $pdo->prepare(
                        "UPDATE alert_notifications
                         SET status_tindak_lanjut = :status,
                             ditangani_oleh = :user,
                             ditangani_at = NOW(),
                             is_read = 1,
                             dibaca_oleh = COALESCE(dibaca_oleh, :user),
                             dibaca_at = COALESCE(dibaca_at, NOW())
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        'status' => $statusBaru,
                        'user' => $_SESSION['auth']['username'] ?? 'system',
                        'id' => $id,
                    ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status tindak lanjut berhasil diperbarui.'];
                }
                header('Location: index.php?page=notifikasi-alert');
                exit;
            } elseif ($action === 'mark_read') {
                $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('ID notifikasi tidak valid.');
                }
                $username = $_SESSION['auth']['username'] ?? 'system';
                $stmt = $pdo->prepare('UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = :username WHERE id = :id');
                $stmt->execute(['username' => $username, 'id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Notifikasi ditandai sudah dibaca.'];
            } elseif ($action === 'mark_all_read') {
                $username = $_SESSION['auth']['username'] ?? 'system';
                $stmt = $pdo->prepare('UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = :username WHERE is_read = 0');
                $stmt->execute(['username' => $username]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.'];
            } elseif ($action === 'delete_alert') {
                if (!AuthController::isAdminSpmt()) {
                    throw new RuntimeException('Hanya admin.spmt yang dapat menghapus notifikasi.');
                }
                $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('ID notifikasi tidak valid.');
                }
                $stmt = $pdo->prepare('DELETE FROM alert_notifications WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Notifikasi berhasil dihapus.'];
            } else {
                throw new RuntimeException('Aksi tidak dikenal.');
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        }
        header('Location: index.php?page=notifikasi-alert');
        exit;
    }

    private function buildAlertNotifications(PDO $pdo): array
    {
        $this->ensureAlertTable($pdo);

        // 1. KATEGORI: PC
        try {
            $stmtDiv = $pdo->query("SELECT division_code, division_label, inventory_db_name FROM master_divisi WHERE is_active = 1");
            $divisions = $stmtDiv ? $stmtDiv->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($divisions as $division) {
                $inventoryDb = trim((string) ($division['inventory_db_name'] ?? ''));
                if (!$this->isSafeIdentifier($inventoryDb)) {
                    continue;
                }
                
                // Cek apakah tabel pc ada
                $tableExists = false;
                try {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = 'pc'");
                    $chk->execute(['db' => $inventoryDb]);
                    $tableExists = ((int)$chk->fetchColumn()) > 0;
                } catch (Throwable $e) {
                    $tableExists = false;
                }
                
                if ($tableExists) {
                    try {
                        $dbNameQuoted = '`' . str_replace('`', '``', $inventoryDb) . '`';
                        $countPC = (int) $pdo->query("SELECT COUNT(*) FROM {$dbNameQuoted}.pc WHERE status = 'RUSAK'")->fetchColumn();
                        if ($countPC > 0) {
                            $judul = "Ada {$countPC} PC Rusak di " . $division['division_label'];
                            $keterangan = "{$countPC} unit PC berstatus RUSAK dan belum ditangani.";
                            $link = "index.php?page=inventaris-detail&division_code=" . urlencode($division['division_code']);
                            $this->insertAlertIfNotExists($pdo, 'PC', 'KRITIS', $judul, $keterangan, $link);
                            
                            // Auto-resolve any older/outdated unread PC alerts for this division
                            $stmtUpdOther = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'PC' AND is_read = 0 AND judul LIKE :divLabel AND judul <> :currentJudul");
                            $stmtUpdOther->execute([
                                'divLabel' => '%' . $division['division_label'] . '%',
                                'currentJudul' => $judul
                            ]);
                        } else {
                            // Auto-resolve PC alerts for this division
                            $stmtUpd = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'PC' AND is_read = 0 AND judul LIKE :divLabel");
                            $stmtUpd->execute(['divLabel' => '%' . $division['division_label'] . '%']);
                        }
                    } catch (Throwable $e) {}
                }
            }
        } catch (Throwable $e) {}

        // 2. KATEGORI: KELUHAN
        try {
            $countKeluhan = (int) $pdo->query("SELECT COUNT(*) FROM it_support_request WHERE UPPER(status) = 'NOT YET' AND tanggal <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)")->fetchColumn();
            if ($countKeluhan > 0) {
                $judul = "Ada {$countKeluhan} Keluhan Belum Direspon >3 Hari";
                $keterangan = "{$countKeluhan} tiket IT Support masih belum ditangani lebih dari 3 hari.";
                $link = "index.php?page=data-keluhan&complaint_status=NOT+YET";
                $this->insertAlertIfNotExists($pdo, 'KELUHAN', 'PERINGATAN', $judul, $keterangan, $link);
                
                // Auto-resolve any older/outdated unread Keluhan alerts
                $stmtUpdOther = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'KELUHAN' AND is_read = 0 AND judul <> :currentJudul");
                $stmtUpdOther->execute(['currentJudul' => $judul]);
            } else {
                // Auto-resolve Keluhan alerts
                $pdo->exec("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'KELUHAN' AND is_read = 0");
            }
        } catch (Throwable $e) {}

        // 3. KATEGORI: MONITORING - Item Kondisi BURUK hari ini
        try {
            $countBurukToday = (int) $pdo->query("SELECT COUNT(DISTINCT ri.item_name) FROM routine_monitoring rm JOIN routine_monitoring_items ri ON ri.id = rm.item_id WHERE rm.condition_status = 'BURUK' AND DATE(rm.check_date) = CURDATE()")->fetchColumn();
            if ($countBurukToday > 0) {
                $judul = "Ada {$countBurukToday} Item Kondisi BURUK Hari Ini";
                $keterangan = "{$countBurukToday} item pada Routine Monitoring tercatat kondisi BURUK hari ini.";
                $link = "index.php?page=routine-monitoring";
                $this->insertAlertIfNotExists($pdo, 'MONITORING', 'KRITIS', $judul, $keterangan, $link);
                
                // Auto-resolve any older/outdated unread BURUK alerts
                $stmtUpdOther = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'MONITORING' AND is_read = 0 AND judul LIKE '%Item Kondisi BURUK%' AND judul <> :currentJudul");
                $stmtUpdOther->execute(['currentJudul' => $judul]);
            } else {
                // Auto-resolve Buruk monitoring alert
                $pdo->exec("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'MONITORING' AND is_read = 0 AND judul LIKE '%Item Kondisi BURUK%'");
            }
        } catch (Throwable $e) {}

        // 4. KATEGORI: MONITORING - Item Belum Dicek Bulan Ini
        try {
            $countNotCheckedMonth = (int) $pdo->query("SELECT COUNT(*) FROM routine_monitoring_items ri WHERE ri.is_active = 1 AND ri.id NOT IN (SELECT DISTINCT item_id FROM routine_monitoring WHERE MONTH(check_date) = MONTH(CURDATE()) AND YEAR(check_date) = YEAR(CURDATE()))")->fetchColumn();
            if ($countNotCheckedMonth > 0) {
                $judul = "{$countNotCheckedMonth} Item Belum Dicek Bulan Ini";
                $keterangan = "{$countNotCheckedMonth} item monitoring belum memiliki data pengecekan di bulan ini.";
                $link = "index.php?page=routine-monitoring";
                $this->insertAlertIfNotExists($pdo, 'MONITORING', 'INFO', $judul, $keterangan, $link);
                
                // Auto-resolve any older/outdated unread unchecked alerts
                $stmtUpdOther = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'MONITORING' AND is_read = 0 AND judul LIKE '%Belum Dicek%' AND judul <> :currentJudul");
                $stmtUpdOther->execute(['currentJudul' => $judul]);
            } else {
                // Auto-resolve unchecked monitoring alert
                $pdo->exec("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'MONITORING' AND is_read = 0 AND judul LIKE '%Belum Dicek%'");
            }
        } catch (Throwable $e) {}

        // 5. KATEGORI: PEMINJAMAN - Peminjaman laptop overdue > 7 hari
        try {
            $peminjamanTableExists = false;
            try {
                $chk = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'peminjaman_laptop'");
                $peminjamanTableExists = ((int)$chk->fetchColumn()) > 0;
            } catch (Throwable $e) {}

            if ($peminjamanTableExists) {
                $countOverdue = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman_laptop WHERE (tanggal_pengembalian IS NULL OR tanggal_pengembalian = '0000-00-00') AND tanggal_peminjaman <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
                if ($countOverdue > 0) {
                    $judul = "Ada {$countOverdue} Peminjaman Laptop Overdue (>7 Hari)";
                    $keterangan = "{$countOverdue} peminjaman laptop belum dikembalikan lebih dari 7 hari.";
                    $link = "index.php?page=peminjaman-laptop&pinjam_filter=dipinjam";
                    $this->insertAlertIfNotExists($pdo, 'PEMINJAMAN', 'PERINGATAN', $judul, $keterangan, $link);
                    
                    // Auto-resolve any older/outdated unread PEMINJAMAN alerts
                    $stmtUpdOther = $pdo->prepare("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'PEMINJAMAN' AND is_read = 0 AND judul <> :currentJudul");
                    $stmtUpdOther->execute(['currentJudul' => $judul]);
                } else {
                    // Auto-resolve overdue loans
                    $pdo->exec("UPDATE alert_notifications SET is_read = 1, dibaca_at = NOW(), dibaca_oleh = 'SYSTEM' WHERE kategori = 'PEMINJAMAN' AND is_read = 0");
                }
            }
        } catch (Throwable $e) {}

        // Ambil data alert dengan filter
        $filterKategori = trim((string) ($_GET['alert_kategori'] ?? ''));
        $filterLevel    = trim((string) ($_GET['alert_level'] ?? ''));
        $filterRead     = trim((string) ($_GET['alert_read'] ?? ''));
        $filterTindakLanjut = strtoupper(trim((string) ($_GET['alert_tindak_lanjut'] ?? '')));

        $sql = "SELECT * FROM alert_notifications WHERE 1=1";
        $params = [];
        if ($filterKategori !== '') {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filterKategori;
        }
        if ($filterLevel !== '') {
            $sql .= " AND level = :level";
            $params['level'] = $filterLevel;
        }
        if ($filterRead !== '') {
            $sql .= " AND is_read = :is_read";
            $params['is_read'] = (int) $filterRead;
        }
        if ($filterTindakLanjut !== '') {
            $sql .= " AND status_tindak_lanjut = :tindak_lanjut";
            $params['tindak_lanjut'] = $filterTindakLanjut;
        }
        $sql .= " ORDER BY is_read ASC, created_at DESC LIMIT 200";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function insertAlertIfNotExists(PDO $pdo, string $kategori, string $level, string $judul, string $keterangan, string $link): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM alert_notifications WHERE kategori = :kategori AND judul = :judul AND (is_read = 0 OR DATE(created_at) = CURDATE())");
        $stmt->execute(['kategori' => $kategori, 'judul' => $judul]);
        $exists = ((int)$stmt->fetchColumn()) > 0;
        
        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO alert_notifications (kategori, level, judul, keterangan, link_url, is_read, created_at) VALUES (:kategori, :level, :judul, :keterangan, :link, 0, NOW())");
            $stmt->execute([
                'kategori' => $kategori,
                'level' => $level,
                'judul' => $judul,
                'keterangan' => $keterangan,
                'link' => $link
            ]);
        }
    }

    private function buildAlertStats(PDO $pdo): array
    {
        try {
            $total = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications")->fetchColumn();
            $belum_baca = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE is_read = 0")->fetchColumn();
            $kritis = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE level = 'KRITIS'")->fetchColumn();
            $peringatan = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE level = 'PERINGATAN'")->fetchColumn();
            $info = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE level = 'INFO'")->fetchColumn();
            
            $belum_ditangani = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE status_tindak_lanjut = 'BELUM_DITANGANI'")->fetchColumn();
            $sedang_ditangani = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE status_tindak_lanjut = 'SEDANG_DITANGANI'")->fetchColumn();
            $selesai = (int) $pdo->query("SELECT COUNT(*) FROM alert_notifications WHERE status_tindak_lanjut = 'SELESAI'")->fetchColumn();

            return [
                'total' => $total,
                'belum_baca' => $belum_baca,
                'kritis' => $kritis,
                'peringatan' => $peringatan,
                'info' => $info,
                'belum_ditangani' => $belum_ditangani,
                'sedang_ditangani' => $sedang_ditangani,
                'selesai' => $selesai,
            ];
        } catch (Throwable $e) {
            return [
                'total' => 0, 'belum_baca' => 0, 'kritis' => 0, 'peringatan' => 0, 'info' => 0,
                'belum_ditangani' => 0, 'sedang_ditangani' => 0, 'selesai' => 0,
            ];
        }
    }
}
