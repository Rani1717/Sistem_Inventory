<?php
$rows = $data['user_management_rows'] ?? [];
$filters = $data['user_management_filters'] ?? ['search' => '', 'status' => 'all'];
$search = (string) ($filters['search'] ?? '');
$status = (string) ($filters['status'] ?? 'all');
?>
<div class="account-page user-management-page">
    <div class="detail-header detail-header--report account-header">
        <h1>KELOLA USER</h1><br>
    </div>

    <form method="get" class="account-card user-management-filter js-user-management-filter" autocomplete="off">
        <input type="hidden" name="page" value="user-management">
        <label class="account-field">
            <span>Cari User</span>
            <input type="search" name="search" value="<?= e($search); ?>" placeholder="Ketik nama, email, username, atau divisi" data-user-live-search>
        </label>
        <label class="account-field">
            <span>Status</span>
            <select name="status" data-user-status-filter>
                <option value="all" <?= $status === 'all' ? 'selected' : ''; ?>>Semua User Terdaftar</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Menunggu Validasi</option>
                <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Aktif</option>
            </select>
        </label>
        <div class="account-card__actions user-management-filter__actions">
            <a href="index.php?page=user-management" class="btn btn--ghost" data-user-filter-reset>Reset</a>
            <button type="submit" class="btn btn--primary">Terapkan</button>
        </div>
    </form>

    <div class="user-management-count" aria-live="polite">
        <span data-user-visible-count><?= count($rows); ?></span> user tampil
    </div>

    <div class="table-wrap user-management-table-wrap">
        <table class="data-table data-table--user-management">
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
                <tr class="js-user-empty-row"><td colspan="7" class="table-empty-state">Tidak ada user sesuai filter.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $userId = (int) ($row['id'] ?? 0);
                    $isActive = (int) ($row['is_active'] ?? 0) === 1;
                    $role = (string) ($row['role'] ?? 'user');
                    ?>
                    <tr class="js-user-row" data-status="<?= $isActive ? 'active' : 'pending'; ?>" data-search="<?= e(mb_strtolower(trim((string) (($row['nama_lengkap'] ?? '') . ' ' . ($row['email'] ?? '') . ' ' . ($row['username'] ?? '') . ' ' . ($row['division_label'] ?? $row['unit_kerja_default'] ?? '') . ' ' . ($row['role'] ?? ''))), 'UTF-8')); ?>">
                        <td>
                            <strong><?= e((string) ($row['nama_lengkap'] ?? '-')); ?></strong><br>
                            <small><?= e((string) ($row['email'] ?? '-')); ?></small><br>
                            <small>@<?= e((string) ($row['username'] ?? '-')); ?></small>
                        </td>
                        <td><?= e((string) ($row['division_label'] ?? $row['unit_kerja_default'] ?? '-')); ?></td>
                        <td>
                            <form method="post" class="inline-admin-form">
                                <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                <input type="hidden" name="user_action" value="role">
                                <select name="role" onchange="this.form.submit()">
                                    <option value="user" <?= $role === 'user' ? 'selected' : ''; ?>>user</option>
                                    <option value="operator" <?= $role === 'operator' ? 'selected' : ''; ?>>operator</option>
                                    <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>admin</option>
                                </select>
                            </form>
                        </td>
                        <td><span class="badge <?= $isActive ? 'badge--ok' : 'badge--warn'; ?>"><?= $isActive ? 'Aktif' : 'Menunggu Validasi'; ?></span></td>
                        <td><?= e((string) ($row['created_at'] ?? '-')); ?></td>
                        <td><?= e((string) ($row['last_login_at'] ?? '-')); ?></td>
                        <td>
                            <div class="table-actions table-actions--stacked">
                                <?php if (!$isActive): ?>
                                    <form method="post" class="inline-admin-form">
                                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                        <input type="hidden" name="user_action" value="approve">
                                        <button type="submit" class="btn-action">Validasi</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline-admin-form" onsubmit="return confirm('Nonaktifkan user ini?');">
                                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                        <input type="hidden" name="user_action" value="suspend">
                                        <button type="submit" class="btn-action btn-action--danger">Nonaktifkan</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="inline-admin-form inline-admin-form--reset">
                                    <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                    <input type="hidden" name="user_action" value="reset_password">
                                    <input type="password" name="new_password" placeholder="Password baru" minlength="6" required>
                                    <button type="submit" class="btn-action">Reset</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="js-user-empty-row" style="display:none;"><td colspan="7" class="table-empty-state">Tidak ada user sesuai filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
