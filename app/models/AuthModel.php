<?php
require_once __DIR__ . '/Database.php';

class AuthModel
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findActiveUserByEmail(string $email): ?array
    {
        if (!$this->pdo instanceof PDO) {
            return null;
        }

        $sql = "
            SELECT
                id,
                username,
                nama_lengkap,
                email,
                password_hash,
                role,
                default_divisi_id,
                unit_kerja_default,
                is_active,
                must_change_password,
                last_login_at
            FROM users
            WHERE email = :email
              AND is_active = 1
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', trim($email), PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function verifyLogin(string $email, string $password): ?array
    {
        $user = $this->findActiveUserByEmail($email);

        if (!$user) {
            return null;
        }

        $inputHash = hash('sha256', $password);

        if (!hash_equals($user['password_hash'], $inputHash)) {
            return null;
        }

        return $user;
    }

    public function updateLastLogin(int $userId): void
    {
        if (!$this->pdo instanceof PDO) {
            return;
        }

        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    public function findUserById(int $userId): ?array
    {
        if (!$this->pdo instanceof PDO) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT id, username, nama_lengkap, email, password_hash, role, default_divisi_id, unit_kerja_default FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function isEmailUsedByOtherUser(string $email, int $userId): bool
    {
        if (!$this->pdo instanceof PDO) {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $stmt->execute(['email' => trim($email), 'id' => $userId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAccount(int $userId, string $name, string $email, ?string $password = null): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        $params = [
            'id' => $userId,
            'nama_lengkap' => trim($name),
            'email' => trim($email),
        ];
        $passwordSql = '';
        if ($password !== null && $password !== '') {
            $passwordSql = ', password_hash = :password_hash, must_change_password = 0';
            $params['password_hash'] = hash('sha256', $password);
        }
        $stmt = $this->pdo->prepare('UPDATE users SET nama_lengkap = :nama_lengkap, email = :email' . $passwordSql . ' WHERE id = :id LIMIT 1');
        $stmt->execute($params);
    }


    public function findUserByEmailAny(string $email): ?array
    {
        if (!$this->pdo instanceof PDO) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT id, username, nama_lengkap, email, role, default_divisi_id, unit_kerja_default, is_active, must_change_password, created_at, last_login_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => trim($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function updatePasswordByEmail(string $email, string $password): bool
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE email = :email LIMIT 1');
        $stmt->execute([
            'password_hash' => hash('sha256', $password),
            'email' => trim($email),
        ]);
        return $stmt->rowCount() > 0;
    }

    public function fetchActiveDivisions(): array
    {
        if (!$this->pdo instanceof PDO) {
            return [];
        }
        $stmt = $this->pdo->query('SELECT id, division_label, division_group_name, sheet_sumber FROM master_divisi WHERE is_active = 1 ORDER BY sheet_sumber ASC, division_label ASC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function registerOrActivateUser(string $name, string $email, string $password, int $divisionId): array
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        $name = trim($name);
        $email = trim($email);
        $divisionId = max(1, $divisionId);
        $divisionStmt = $this->pdo->prepare('SELECT id, division_label, sheet_sumber FROM master_divisi WHERE id = :id AND is_active = 1 LIMIT 1');
        $divisionStmt->execute(['id' => $divisionId]);
        $division = $divisionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$division) {
            throw new RuntimeException('Divisi tidak ditemukan di master divisi.');
        }
        $existing = $this->findUserByEmailAny($email);
        if ($existing) {
            if ((int) ($existing['is_active'] ?? 0) === 1) {
                throw new RuntimeException('Email sudah terdaftar dan aktif. Silakan gunakan menu lupa password untuk mengganti password.');
            }
            $stmt = $this->pdo->prepare('UPDATE users SET nama_lengkap = :nama_lengkap, password_hash = :password_hash, default_divisi_id = :default_divisi_id, unit_kerja_default = :unit_kerja_default, sheet_sumber = :sheet_sumber, is_active = 0, must_change_password = 0 WHERE id = :id LIMIT 1');
            $stmt->execute([
                'nama_lengkap' => $name,
                'password_hash' => hash('sha256', $password),
                'default_divisi_id' => (int) $division['id'],
                'unit_kerja_default' => (string) $division['division_label'],
                'sheet_sumber' => (string) $division['sheet_sumber'],
                'id' => (int) $existing['id'],
            ]);
            return ['mode' => 'updated', 'id' => (int) $existing['id']];
        }
        $baseUsername = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $name), '.'));
        if ($baseUsername === '') {
            $emailUser = strtok($email, '@') ?: 'user';
            $baseUsername = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $emailUser), '.')) ?: 'user';
        }
        $username = $baseUsername;
        $suffix = 1;
        $checkStmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        while (true) {
            $checkStmt->execute(['username' => $username]);
            if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
                break;
            }
            $suffix++;
            $username = $baseUsername . '.' . $suffix;
        }
        $stmt = $this->pdo->prepare('INSERT INTO users (username, nama_lengkap, email, password_hash, role, default_divisi_id, unit_kerja_default, sheet_sumber, is_active, must_change_password) VALUES (:username, :nama_lengkap, :email, :password_hash, "user", :default_divisi_id, :unit_kerja_default, :sheet_sumber, 0, 0)');
        $stmt->execute([
            'username' => $username,
            'nama_lengkap' => $name,
            'email' => $email,
            'password_hash' => hash('sha256', $password),
            'default_divisi_id' => (int) $division['id'],
            'unit_kerja_default' => (string) $division['division_label'],
            'sheet_sumber' => (string) $division['sheet_sumber'],
        ]);
        $userId = (int) $this->pdo->lastInsertId();
        $accessStmt = $this->pdo->prepare('INSERT IGNORE INTO user_divisi_akses (user_id, divisi_id) VALUES (:user_id, :divisi_id)');
        $accessStmt->execute(['user_id' => $userId, 'divisi_id' => (int) $division['id']]);
        return ['mode' => 'created', 'id' => $userId];
    }

    public function fetchUsersForAdmin(string $search = '', string $status = ''): array
    {
        if (!$this->pdo instanceof PDO) {
            return [];
        }

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(u.nama_lengkap LIKE :search OR u.email LIKE :search OR u.username LIKE :search OR u.unit_kerja_default LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status === 'pending') {
            $where[] = 'u.is_active = 0';
        } elseif ($status === 'active') {
            $where[] = 'u.is_active = 1';
        }

        $sql = 'SELECT u.id, u.username, u.nama_lengkap, u.email, u.role, u.default_divisi_id, u.unit_kerja_default, u.is_active, u.must_change_password, u.last_login_at, u.created_at, d.division_label
                FROM users u
                LEFT JOIN master_divisi d ON d.id = u.default_divisi_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.is_active ASC, u.created_at DESC, u.nama_lengkap ASC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countPendingUsers(): int
    {
        if (!$this->pdo instanceof PDO) {
            return 0;
        }
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 0');
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    public function updateUserStatus(int $userId, bool $isActive): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        $stmt = $this->pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id LIMIT 1');
        $stmt->execute(['is_active' => $isActive ? 1 : 0, 'id' => $userId]);
    }

    public function updateUserRole(int $userId, string $role): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        if (!in_array($role, ['admin', 'user'], true)) {
            throw new RuntimeException('Role tidak valid.');
        }
        $stmt = $this->pdo->prepare('UPDATE users SET role = :role WHERE id = :id LIMIT 1');
        $stmt->execute(['role' => $role, 'id' => $userId]);
    }

    public function resetUserPassword(int $userId, string $password): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        if (strlen($password) < 6) {
            throw new RuntimeException('Password minimal 6 karakter.');
        }
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id LIMIT 1');
        $stmt->execute(['password_hash' => hash('sha256', $password), 'id' => $userId]);
    }

    public function deleteUser(int $userId): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    }

}
