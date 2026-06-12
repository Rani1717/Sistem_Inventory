<?php
$rows = $data['user_management_rows'] ?? [];
$divisions = $data['divisions'] ?? [];
$filters = $data['user_management_filters'] ?? ['search' => '', 'status' => 'all'];
$search = (string) ($filters['search'] ?? '');
$status = (string) ($filters['status'] ?? 'all');

function getPastelColor(string $str): string {
    $hash = md5($str);
    $h = hexdec(substr($hash, 0, 4)) % 360;
    $s = 70; // 70% saturation
    $l = 85; // 85% lightness
    return "hsl({$h}, {$s}%, {$l}%)";
}

function getInitials(string $name): string {
    $parts = explode(' ', preg_replace('/\s+/', ' ', trim($name)));
    $initials = '';
    if (!empty($parts[0])) {
        $initials .= mb_substr($parts[0], 0, 1, 'UTF-8');
    }
    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');
    }
    return $initials !== '' ? strtoupper($initials) : '?';
}
?>

<div class="um-container">
    <!-- Page Header -->
    <header class="um-header">
        <div class="um-header__title-group">
            <h1 class="um-header__title">Kelola User</h1>
            <p class="um-header__subtitle">Manajemen akun dan hak akses pengguna</p>
        </div>
        <div class="um-header__search-group">
            <div class="um-search-wrapper">
                <input type="search" class="um-search-input js-um-search" placeholder="Cari user..." value="<?= e($search); ?>" aria-label="Cari user">
                <i class="fa-solid fa-magnifying-glass um-search-icon"></i>
            </div>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="um-filter-bar">
        <div class="um-filter-group">
            <div class="um-select-wrapper">
                <select class="um-select js-um-status-filter" aria-label="Filter Status">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="pending">Nonaktif</option>
                </select>
            </div>
            <div class="um-select-wrapper">
                <select class="um-select js-um-division-filter" aria-label="Filter Divisi">
                    <option value="all">Semua Divisi</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?= e($div['division_label']); ?>"><?= e($div['division_label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="um-counter">
            <span class="um-counter__number js-um-counter"><?= count($rows); ?></span> user terdaftar
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="um-card">
        <div class="um-table-wrapper">
            <table class="um-table js-um-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Divisi</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th>Login Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr class="js-um-empty-state">
                            <td colspan="7" class="um-table-empty">Tidak ada data user.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $userId = (int) ($row['id'] ?? 0);
                            $isActive = (int) ($row['is_active'] ?? 0) === 1;
                            $role = (string) ($row['role'] ?? 'user');
                            $name = (string) ($row['nama_lengkap'] ?? '-');
                            $username = (string) ($row['username'] ?? '-');
                            $email = (string) ($row['email'] ?? '-');
                            $divisionLabel = (string) ($row['division_label'] ?? $row['unit_kerja_default'] ?? '-');
                            $createdAt = (string) ($row['created_at'] ?? '-');
                            $lastLoginAt = (string) ($row['last_login_at'] ?? '-');
                            $isDeleteActive = ($role === 'admin');
                            
                            // Generate unique pastel background color for avatar
                            $bgColor = getPastelColor($username);
                            $initials = getInitials($name);
                            ?>
                            <tr class="js-um-row" 
                                data-status="<?= $isActive ? 'active' : 'pending'; ?>" 
                                data-division="<?= e($divisionLabel); ?>"
                                data-search="<?= e(mb_strtolower(trim($name . ' ' . $email . ' ' . $username . ' ' . $divisionLabel . ' ' . $role), 'UTF-8')); ?>">
                                
                                <td class="um-td-user">
                                    <div class="um-user-cell">
                                        <div class="um-avatar" style="background-color: <?= $bgColor; ?>;">
                                            <span class="um-avatar__text"><?= $initials; ?></span>
                                        </div>
                                        <div class="um-user-info">
                                            <span class="um-user-name" title="<?= e($name); ?>"><?= e($name); ?></span>
                                            <span class="um-user-meta"><?= e($email); ?></span>
                                            <span class="um-user-meta">@<?= e($username); ?></span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="um-text-body"><?= e($divisionLabel); ?></span>
                                </td>
                                
                                <td>
                                    <?php if (AuthController::isAdminSpmt()): ?>
                                        <form method="post" class="um-role-form js-um-role-form">
                                            <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                            <input type="hidden" name="user_action" value="role">
                                            <select name="role" class="um-role-select <?= $role === 'admin' ? 'um-role-select--admin' : 'um-role-select--user'; ?>" onchange="this.form.submit()">
                                                <option value="user" <?= $role === 'user' ? 'selected' : ''; ?>>user</option>
                                                <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>admin</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <span class="um-role-badge um-role-badge--<?= $role; ?>"><?= e($role); ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="um-status-badge um-status-badge--<?= $isActive ? 'active' : 'inactive'; ?>">
                                        <span class="um-status-badge__dot"></span>
                                        <?= $isActive ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="um-text-meta"><?= e($createdAt); ?></span>
                                </td>
                                
                                <td>
                                    <span class="um-text-meta"><?= e($lastLoginAt); ?></span>
                                </td>
                                
                                <td>
                                    <?php if (AuthController::isAdminSpmt()): ?>
                                        <div class="um-dropdown">
                                            <button type="button" class="um-dropdown-btn js-um-dropdown-toggle">
                                                Aksi <i class="fa-solid fa-chevron-down" style="font-size: 8px;"></i>
                                            </button>
                                            <div class="um-dropdown-menu js-um-dropdown-menu" hidden>
                                                <?php if (!$isActive): ?>
                                                    <form method="post" class="um-dropdown-form">
                                                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                                        <input type="hidden" name="user_action" value="approve">
                                                        <button type="submit" class="um-dropdown-item um-dropdown-item--activate">
                                                            <i class="fa-solid fa-user-check"></i> Aktifkan
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="um-dropdown-form">
                                                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                                        <input type="hidden" name="user_action" value="suspend">
                                                        <button type="submit" class="um-dropdown-item um-dropdown-item--deactivate" <?= ($userId === (int)($_SESSION['auth']['id'] ?? 0)) ? 'disabled title="Tidak dapat menonaktifkan akun sendiri"' : ''; ?>>
                                                            <i class="fa-solid fa-user-slash"></i> Nonaktifkan
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($isDeleteActive): ?>
                                                    <button type="button" class="um-dropdown-item um-dropdown-item--delete js-um-delete-btn" 
                                                            data-user-id="<?= $userId; ?>" 
                                                            data-user-name="<?= e($name); ?>" 
                                                            <?= ($userId === (int)($_SESSION['auth']['id'] ?? 0)) ? 'disabled title="Tidak dapat menghapus akun sendiri"' : ''; ?>>
                                                        <i class="fa-regular fa-trash-can"></i> Hapus
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="um-dropdown-item um-dropdown-item--delete um-dropdown-item--disabled" disabled title="Hapus dinonaktifkan untuk role ini">
                                                        <i class="fa-regular fa-trash-can"></i> Hapus
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="um-text-meta">-</span>
                                    <?php endif; ?>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                        <!-- Dynamic empty state row for client-side search/filters -->
                        <tr class="js-um-empty-state" style="display: none;">
                            <td colspan="7" class="um-table-empty">Tidak ada user sesuai filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Row -->
        <div class="um-pagination-bar js-um-pagination" <?= empty($rows) ? 'style="display:none;"' : ''; ?>>
            <div class="um-pagination-info">
                Menampilkan <span class="js-um-pagination-range">1-10</span> dari <span class="js-um-pagination-total"><?= count($rows); ?></span> user
            </div>
            <div class="um-pagination-controls js-um-pagination-controls">
                <!-- Buttons will be generated dynamically by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="um-modal-overlay js-um-modal" hidden>
    <div class="um-modal">
        <div class="um-modal__body">
            <div class="um-modal__icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="um-modal__title">Konfirmasi Hapus</h3>
            <p class="um-modal__text">Apakah Anda yakin ingin menghapus user <strong class="js-um-modal-username"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="um-modal__footer">
            <button type="button" class="um-modal-btn um-modal-btn--cancel js-um-modal-cancel">Batal</button>
            <form method="post" class="um-modal-form js-um-modal-form">
                <input type="hidden" name="user_id" value="" class="js-um-modal-userid">
                <input type="hidden" name="user_action" value="delete">
                <button type="submit" class="um-modal-btn um-modal-btn--delete">Ya, hapus</button>
            </form>
        </div>
    </div>
</div>
