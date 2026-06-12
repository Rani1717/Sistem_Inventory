<?php
require_once __DIR__ . '/../models/AuthModel.php';

class AuthController
{
    private AuthModel $authModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->authModel = new AuthModel();
    }

    public function loginProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        if (!empty($errors)) {
            $_SESSION['login_errors'] = $errors;
            $_SESSION['login_old'] = [
                'email' => $email,
            ];

            header('Location: index.php?page=login');
            exit;
        }

        $knownUser = $this->authModel->findUserByEmailAny($email);
        if ($knownUser && (int) ($knownUser['is_active'] ?? 0) !== 1) {
            $_SESSION['flash_error'] = 'Akun Anda belum aktif. Silakan menunggu validasi dari admin.spmt.';
            $_SESSION['login_old'] = [
                'email' => $email,
            ];

            header('Location: index.php?page=login');
            exit;
        }

        $user = $this->authModel->verifyLogin($email, $password);

        if (!$user) {
            $_SESSION['flash_error'] = 'Email atau password salah.';
            $_SESSION['login_old'] = [
                'email' => $email,
            ];

            header('Location: index.php?page=login');
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'role' => $user['role'],
            'default_divisi_id' => $user['default_divisi_id'],
            'unit_kerja_default' => $user['unit_kerja_default'],
            'must_change_password' => (int) $user['must_change_password'],
            'is_logged_in' => true,
        ];

        $this->authModel->updateLastLogin((int) $user['id']);

        header('Location: index.php?page=' . self::defaultPageForCurrentUser());
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: index.php?page=login');
        exit;
    }


    public function forgotPasswordProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login&auth=forgot');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email wajib diisi dengan format valid.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }
        if (!$errors && !$this->authModel->findUserByEmailAny($email)) {
            $errors['email'] = 'Email belum terdaftar pada data master.';
        }

        if ($errors) {
            $_SESSION['forgot_errors'] = $errors;
            $_SESSION['forgot_old'] = ['email' => $email];
            header('Location: index.php?page=login&auth=forgot');
            exit;
        }

        try {
            $this->authModel->updatePasswordByEmail($email, $password);
            $_SESSION['flash_success'] = 'Password berhasil diganti. Silakan login dengan password baru.';
        } catch (Throwable $e) {
            $_SESSION['forgot_errors'] = ['global' => 'Gagal mengganti password: ' . $e->getMessage()];
            $_SESSION['forgot_old'] = ['email' => $email];
            header('Location: index.php?page=login&auth=forgot');
            exit;
        }

        header('Location: index.php?page=login');
        exit;
    }

    public function registerProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login&auth=register');
            exit;
        }

        $name = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $divisionId = (int) ($_POST['default_divisi_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email wajib diisi dengan format valid.';
        }
        if ($divisionId <= 0) {
            $errors['default_divisi_id'] = 'Divisi wajib dipilih dari data master.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }

        if ($errors) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old'] = ['nama_lengkap' => $name, 'email' => $email, 'default_divisi_id' => $divisionId];
            header('Location: index.php?page=login&auth=register');
            exit;
        }

        try {
            $result = $this->authModel->registerOrActivateUser($name, $email, $password, $divisionId);
            $_SESSION['flash_success'] = $result['mode'] === 'created'
                ? 'Akun berhasil didaftarkan dan menunggu validasi admin.spmt.'
                : 'Data akun berhasil diperbarui dan masih menunggu validasi admin.spmt.';
        } catch (Throwable $e) {
            $_SESSION['register_errors'] = ['global' => 'Gagal mendaftarkan akun: ' . $e->getMessage()];
            $_SESSION['register_old'] = ['nama_lengkap' => $name, 'email' => $email, 'default_divisi_id' => $divisionId];
            header('Location: index.php?page=login&auth=register');
            exit;
        }

        header('Location: index.php?page=login');
        exit;
    }

    public static function role(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return strtolower(trim((string) ($_SESSION['auth']['role'] ?? '')));
    }

    public static function canAccessPage(string $page): bool
    {
        if (!self::check()) {
            return false;
        }

        if ($page === 'account-settings') {
            return true;
        }

        $role = self::role();
        $inventoryPages = ['inventory-pc', 'inventory-other'];
        $dataInventoryPages = ['data-inventaris', 'data-inventaris-subreg', 'inventaris-detail'];
        $userPages = array_merge($inventoryPages, $dataInventoryPages, ['log-barang', 'routine-monitoring', 'dashboard']);
        $adminPages = array_merge($userPages, ['data-keluhan', 'dashboard', 'laporan']);

        if (self::isAdminSpmt()) {
            $adminPages[] = 'user-management';
            $adminPages[] = 'peminjaman-laptop';
        } elseif ($role === 'admin') {
            $adminPages[] = 'peminjaman-laptop';
        }

        if ($role === 'admin') {
            return in_array($page, $adminPages, true) || $page === 'user-management';
        }

        if ($role === 'user') {
            return in_array($page, $userPages, true);
        }

        return false;
    }

    public static function defaultPageForCurrentUser(): string
    {
        if (self::canAccessPage('dashboard')) {
            return 'dashboard';
        }
        if (self::canAccessPage('inventory-pc')) {
            return 'inventory-pc';
        }
        if (self::canAccessPage('data-inventaris')) {
            return 'data-inventaris';
        }
        return 'account-settings';
    }

    public static function canAccessItSupport(): bool
    {
        return self::canAccessPage('data-keluhan');
    }

    public static function accessiblePages(): array
    {
        $pages = ['account-settings'];
        foreach (['inventory-pc', 'data-inventaris', 'data-inventaris-subreg', 'inventaris-detail', 'log-barang', 'routine-monitoring', 'data-keluhan', 'dashboard', 'laporan', 'user-management', 'peminjaman-laptop'] as $page) {
            if (self::canAccessPage($page)) {
                $pages[] = $page;
            }
        }
        return array_values(array_unique($pages));
    }

    public static function isItDivision(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $auth = $_SESSION['auth'] ?? [];
        $role = (string) ($auth['role'] ?? '');
        $unit = strtoupper(trim((string) ($auth['unit_kerja_default'] ?? '')));
        $divisionId = (int) ($auth['default_divisi_id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($divisionId === 2) {
            return true;
        }

        return in_array($unit, ['IT', 'TEKNIK & IT', 'TEKNIK/IT'], true);
    }

    public static function isAdminSpmt(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $auth = $_SESSION['auth'] ?? [];
        $role = strtolower(trim((string) ($auth['role'] ?? '')));
        $username = strtolower(trim((string) ($auth['username'] ?? '')));
        $email = strtolower(trim((string) ($auth['email'] ?? '')));

        return $role === 'admin' && (
            $username === 'admin.spmt'
            || $email === 'admin.spmt@pelindo.local'
            || $email === 'admin.spmt@spmt.local'
        );
    }

    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['auth']['is_logged_in']);
    }
}
