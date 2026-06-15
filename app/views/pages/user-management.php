<?php
$rows = $data['user_management_rows'] ?? [];
$divisions = $data['divisions'] ?? [];
$filters = $data['user_management_filters'] ?? ['search' => '', 'status' => 'all'];
$search = (string) ($filters['search'] ?? '');
$status = (string) ($filters['status'] ?? 'all');
$pendingCount = (int) ($data['pending_user_count'] ?? 0);

function getPastelColor(string $str): string {
    $hash = md5($str);
    $h = hexdec(substr($hash, 0, 4)) % 360;
    $s = 65;
    $l = 83;
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

<style>
/* ============================================================
   KELOLA USER — Konsisten dengan halaman lain
   ============================================================ */

/* Jarak detail header dengan 3 stat label di bawahnya */
.page--user-management .detail-header--report {
    margin-bottom: 24px !important;
}

/* Ensure Poppins and Montserrat font families match other pages */
.um2-stat-card,
.um2-stat-card__label,
.um2-stat-card__value,
.um2-toolbar,
.um2-toolbar select,
.um2-toolbar input,
.um2-toolbar__counter,
.um2-avatar,
.um2-user-name,
.um2-user-meta,
.um2-badge,
.um2-role-select,
.um2-role-badge,
.um2-dropdown,
.um2-dropdown-btn,
.um2-dropdown-item,
.um2-dt-header,
.um2-dt-header__left,
.um2-dt-header__right,
.um2-export-btn,
.um2-export-menu,
.um2-export-item,
.um2-pagination,
.um2-pagination__info,
.um2-pagination__controls,
.um2-page-btn,
.um2-modal-overlay,
.um2-modal,
.um2-modal__title,
.um2-modal__text,
.um2-modal__footer,
.um2-table-wrap,
.um2-table-wrap table,
.um2-table-wrap th,
.um2-table-wrap td,
.export-dropdown,
.export-dropdown__toggle,
.export-dropdown__menu,
.export-dropdown__item {
    font-family: var(--font-sub) !important;
}
.detail-header h1 {
    font-family: var(--font-head) !important;
}

/* Summary stat cards di bawah judul */
.um2-stat-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
    margin: 24px auto 24px !important;
}
.um2-stat-card {
    flex: 1 1 160px;
    max-width: 220px;
    background: rgba(255,255,255,0.92);
    border-radius: 22px;
    padding: 18px 20px;
    box-shadow: 0 12px 30px rgba(13,51,108,0.11);
    border: 1px solid rgba(42,102,165,0.10);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: transform .18s, box-shadow .18s;
}
.um2-stat-card:hover { transform: translateY(-2px); box-shadow: 0 18px 40px rgba(13,51,108,0.17); }
.um2-stat-card__icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.um2-stat-card__icon--blue  { background: #e8f0fe; color: #1a73e8; }
.um2-stat-card__icon--green { background: #e6f7ed; color: #2e7d32; }
.um2-stat-card__icon--amber { background: #fff3e0; color: #e65100; }
.um2-stat-card__info { display: flex; flex-direction: column; }
.um2-stat-card__label { font-size: 11px; color: #70757a; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.um2-stat-card__value { font-size: 22px; font-weight: 700; color: #202124; line-height: 1; }

/* Filter/toolbar bar */
.um2-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: rgba(255,255,255,0.88);
    border-radius: 22px;
    padding: 14px 20px;
    box-shadow: 0 12px 30px rgba(31,83,139,0.10);
    border: 1px solid rgba(42,102,165,0.10);
    margin-bottom: 24px;
}
.um2-toolbar select, .um2-toolbar input[type="search"] {
    height: 44px; padding: 0 14px;
    border: 1px solid rgba(42,102,165,0.18); border-radius: 14px;
    background: #fff; color: #123f78; font: inherit; font-weight: 700;
    outline: none; min-width: 150px; cursor: pointer; box-sizing: border-box;
    transition: border-color .18s, box-shadow .18s;
}
.um2-toolbar select:focus, .um2-toolbar input[type="search"]:focus {
    border-color: #2a66a5;
    box-shadow: 0 0 0 3px rgba(42,102,165,0.12);
}
.um2-toolbar__search-wrap { position: relative; flex: 1 1 220px; display: flex; align-items: center; }
.um2-toolbar__search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(42,102,165,.45); font-size: 13px; pointer-events: none; }
.um2-toolbar__search-wrap input { padding-left: 38px !important; width: 100%; height: 44px; min-width: 0; border: 1px solid rgba(42,102,165,0.18); border-radius: 14px; background: #fff; color: #123f78; font: inherit; font-weight: 700; outline: none; box-sizing: border-box; transition: border-color .18s, box-shadow .18s; }
.um2-toolbar__search-wrap input:focus { border-color: #2a66a5; box-shadow: 0 0 0 3px rgba(42,102,165,0.12); }
.um2-toolbar__counter { margin-left: auto; font-size: 12px; color: #55729b; font-weight: 700; text-transform: uppercase; white-space: nowrap; }

/* Avatar in table */
.um2-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; color: #14395f; flex-shrink: 0;
}
.um2-user-cell { display: flex; align-items: center; gap: 10px; }
.um2-user-info { display: flex; flex-direction: column; gap: 1px; text-align: left; }
.um2-user-name { font-weight: 700; color: #14395f; font-size: 13px; white-space: nowrap; max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
.um2-user-meta { font-size: 11px; color: #7b8ea8; white-space: nowrap; max-width: 160px; overflow: hidden; text-overflow: ellipsis; }

/* Status badge */
.um2-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700;
}
.um2-badge--active  { background: #e6f7ed; color: #256f3a; border: 1px solid #b7e4c7; }
.um2-badge--inactive { background: #fce8e6; color: #b71c1c; border: 1px solid #f5c6c2; }
.um2-badge__dot { width: 7px; height: 7px; border-radius: 50%; }
.um2-badge--active .um2-badge__dot { background: #27ae60; }
.um2-badge--inactive .um2-badge__dot { background: #e84f5f; }

/* Role select in table */
.um2-role-select {
    padding: 5px 10px; border-radius: 8px; font: inherit; font-size: 12px; font-weight: 700;
    border: 1.5px solid rgba(42,102,165,0.20); outline: none; cursor: pointer;
    transition: border-color .15s; min-width: 90px;
}
.um2-role-select--admin { background: rgba(42,102,165,0.08); color: #2A66A5; border-color: rgba(42,102,165,0.3); }
.um2-role-select--user  { background: rgba(100,100,100,0.07); color: #455a7a; }
.um2-role-badge { display: inline-block; padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; }
.um2-role-badge--admin { background: rgba(42,102,165,0.12); color: #2A66A5; }
.um2-role-badge--user  { background: rgba(100,100,100,0.10); color: #455a7a; }

/* Action dropdown */
.um2-dropdown { position: relative; display: inline-block; }
.um2-dropdown-btn {
    padding: 6px 14px; border-radius: 10px; border: 1.5px solid rgba(42,102,165,0.22);
    background: #fff; color: #2A66A5; font: inherit; font-size: 12px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: background .15s, border-color .15s;
}
.um2-dropdown-btn:hover { background: rgba(42,102,165,0.06); border-color: rgba(42,102,165,0.4); }
.um2-dropdown-menu {
    position: absolute; right: 0; top: calc(100% + 6px); min-width: 160px;
    background: #fff; border: 1px solid rgba(42,102,165,0.16); border-radius: 14px;
    box-shadow: 0 12px 32px rgba(13,51,108,0.16); z-index: 60; overflow: hidden;
    display: none;
}
.um2-dropdown-menu.is-open { display: block; }
.um2-dropdown-item {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 11px 16px; border: none; background: transparent;
    font: inherit; font-size: 13px; color: #14395f; cursor: pointer;
    text-align: left; transition: background .12s; text-decoration: none;
}
.um2-dropdown-item:hover { background: rgba(42,102,165,0.07); }
.um2-dropdown-item--activate i { color: #27ae60; }
.um2-dropdown-item--deactivate i { color: #e65100; }
.um2-dropdown-item--delete i { color: #e84f5f; }
.um2-dropdown-item:disabled { opacity: .45; cursor: not-allowed; }

/* Datatable controls bar */
.um2-dt-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    background: #fff; padding: 12px 20px;
    border-radius: 12px; border: 1px solid rgba(42,102,165,0.10);
    box-shadow: 0 4px 14px rgba(13,51,108,0.05);
    margin-bottom: 10px;
}
.um2-dt-header__left { font-size: 13px; color: #55729b; display: flex; align-items: center; gap: 6px; }
.um2-dt-header__left select {
    padding: 4px 10px; border-radius: 8px;
    border: 1.5px solid rgba(42,102,165,0.20); background: #fff;
    color: #2A66A5; font: inherit; font-size: 13px; font-weight: 700;
    cursor: pointer; outline: none;
}
.um2-dt-header__right { display: flex; gap: 10px; align-items: center; }
/* Export dropdown */
.um2-export-dd { position: relative; display: inline-block; }
.um2-export-btn {
    display: inline-flex; align-items: center; gap: 6px;
    background: #15803d; color: #fff; border: none;
    padding: 8px 16px; border-radius: 10px;
    font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background .18s;
}
.um2-export-btn:hover { background: #166534; }
.um2-export-menu {
    position: absolute; right: 0; top: calc(100% + 6px);
    min-width: 150px; background: #fff;
    border: 1px solid rgba(42,102,165,0.16); border-radius: 12px;
    box-shadow: 0 12px 32px rgba(13,51,108,0.16); z-index: 80;
    overflow: hidden; display: none;
}
.um2-export-menu.is-open { display: block; }
.um2-export-item {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px; color: #14395f; text-decoration: none;
    font-size: 13px; transition: background .12s;
}
.um2-export-item:hover { background: rgba(42,102,165,0.07); }
/* Pagination */
.um2-pagination {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    margin-top: 10px; padding: 12px 20px;
    background: #fff; border-radius: 14px;
    border: 1px solid rgba(42,102,165,0.10);
    box-shadow: 0 4px 14px rgba(13,51,108,0.05);
}
.um2-pagination__info { font-size: 13px; color: #55729b; font-weight: 600; }
.um2-pagination__controls { display: flex; gap: 4px; }
.um2-page-btn {
    min-width: 34px; height: 34px; padding: 0 8px;
    border-radius: 8px; border: 1.5px solid rgba(42,102,165,0.20);
    background: #fff; color: #2A66A5; font: inherit; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.um2-page-btn:hover:not(:disabled) { background: rgba(42,102,165,0.08); border-color: rgba(42,102,165,0.4); }
.um2-page-btn.is-active { background: #2A66A5; color: #fff; border-color: #2A66A5; }
.um2-page-btn:disabled { opacity: .45; cursor: not-allowed; }

/* Delete confirm modal */
.um2-modal-overlay {
    position: fixed; inset: 0; background: rgba(10,20,35,0.52);
    backdrop-filter: blur(4px); z-index: 9000;
    display: flex; align-items: center; justify-content: center; padding: 24px;
}
.um2-modal-overlay[hidden] { display: none !important; }
.um2-modal {
    background: #fff; border-radius: 24px;
    box-shadow: 0 28px 70px rgba(13,51,108,0.24);
    padding: 32px 28px 24px; max-width: 420px; width: 100%;
    text-align: center; animation: um2PopIn .18s ease-out;
}
@keyframes um2PopIn {
    from { transform: translateY(10px) scale(.97); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}
.um2-modal__icon {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(232,79,95,.12); color: #e84f5f; font-size: 28px;
    display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;
}
.um2-modal__title { font-size: 20px; font-weight: 700; color: #14395f; margin: 0 0 8px; }
.um2-modal__text  { font-size: 14px; color: #55729b; line-height: 1.55; margin: 0 0 24px; }
.um2-modal__footer {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 24px;
}
.um2-modal__footer .btn {
    min-height: 42px;
    padding: 0 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    box-sizing: border-box;
    transition: all 0.2s ease;
    border: none;
}
.um2-modal__footer .btn--ghost {
    background: #fff !important;
    color: #55729b !important;
    border: 1.5px solid rgba(42,102,165,0.20) !important;
}
.um2-modal__footer .btn--ghost:hover {
    background: rgba(42,102,165,0.06) !important;
    border-color: rgba(42,102,165,0.35) !important;
}
.um2-modal__footer .btn--danger {
    background: #e84f5f !important;
    color: #fff !important;
    box-shadow: 0 8px 20px rgba(232,79,95,0.20) !important;
}
.um2-modal__footer .btn--danger:hover {
    background: #d63b4b !important;
    box-shadow: 0 10px 24px rgba(232,79,95,0.30) !important;
}

/* Rounded Table Wrap for User Data */
.um2-table-wrap {
    background: rgba(255,255,255,0.92);
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(42,102,165,0.12);
    box-shadow: 0 12px 30px rgba(13,51,108,0.06);
    margin-bottom: 0;
}
.um2-table-wrap .data-table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    width: 100% !important;
    border: none !important;
}
.um2-table-wrap .data-table th,
.um2-table-wrap .data-table td {
    border: none !important;
    border-bottom: 1px solid rgba(42,102,165,0.10) !important;
}
.um2-table-wrap .data-table th {
    background: #2A66A5 !important;
    color: #fff !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    padding: 14px 16px !important;
    border-bottom: 1.5px solid rgba(42,102,165,0.18) !important;
}
.um2-table-wrap .data-table td {
    padding: 12px 16px !important;
    background: #fff !important;
}
.um2-table-wrap .data-table tr:last-child td {
    border-bottom: none !important;
}

/* Responsive */
@media (max-width: 900px) {
    .um2-stat-row { gap: 10px; }
    .um2-stat-card { flex: 1 1 140px; }
    .um2-toolbar { gap: 10px; }
}
@media (max-width: 600px) {
    .um2-stat-row { flex-direction: column; align-items: stretch; }
    .um2-stat-card { max-width: 100%; }
    .um2-toolbar { flex-direction: column; align-items: stretch; }
    .um2-toolbar__search-wrap, .um2-toolbar select { width: 100%; min-width: 0; }
    .um2-toolbar__counter { margin-left: 0; }
    .um2-pagination { flex-direction: column; align-items: flex-start; }
}
</style>

<?php
// Compute stats
$totalUsers  = count($rows);
$activeUsers  = count(array_filter($rows, fn($r) => (int)($r['is_active'] ?? 0) === 1));
$pendingUsers = count(array_filter($rows, fn($r) => (int)($r['is_active'] ?? 0) === 0 && (int)($r['is_validated'] ?? 1) === 0));
?>

<!-- ============================================================ -->
<!-- PAGE HEADER — konsisten dengan detail-header halaman lain    -->
<!-- ============================================================ -->
<div class="detail-header detail-header--report">
    <h1>KELOLA USER</h1>
</div>

<!-- ============================================================ -->
<!-- STAT CARDS                                                   -->
<!-- ============================================================ -->
<div class="um2-stat-row">
    <div class="um2-stat-card">
        <div class="um2-stat-card__icon um2-stat-card__icon--blue">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="um2-stat-card__info">
            <span class="um2-stat-card__label">Total User</span>
            <span class="um2-stat-card__value js-um-total-stat"><?= $totalUsers; ?></span>
        </div>
    </div>
    <div class="um2-stat-card">
        <div class="um2-stat-card__icon um2-stat-card__icon--green">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="um2-stat-card__info">
            <span class="um2-stat-card__label">Akun Aktif</span>
            <span class="um2-stat-card__value js-um-active-stat"><?= $activeUsers; ?></span>
        </div>
    </div>
    <div class="um2-stat-card">
        <div class="um2-stat-card__icon um2-stat-card__icon--amber">
            <i class="fa-solid fa-user-clock"></i>
        </div>
        <div class="um2-stat-card__info">
            <span class="um2-stat-card__label">Menunggu Validasi</span>
            <span class="um2-stat-card__value js-um-pending-stat"><?= $pendingUsers; ?></span>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TOOLBAR — semua sejajar dalam satu baris                     -->
<!-- ============================================================ -->
<div class="um2-toolbar">
    <!-- Search -->
    <div class="um2-toolbar__search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" class="js-um-search" id="umSearch"
               placeholder="Cari nama, email, username, divisi..."
               value="<?= e($search); ?>" autocomplete="off">
    </div>

    <!-- Filter Status -->
    <select class="js-um-status-filter" id="umStatusFilter" aria-label="Filter Status">
        <option value="all">Semua Status</option>
        <option value="active">Aktif</option>
        <option value="pending">Nonaktif</option>
    </select>

    <!-- Filter Divisi -->
    <select class="js-um-division-filter" id="umDivisionFilter" aria-label="Filter Divisi">
        <option value="all">Semua Divisi</option>
        <?php foreach ($divisions as $div): ?>
            <option value="<?= e($div['division_label']); ?>"><?= e($div['division_label']); ?></option>
        <?php endforeach; ?>
    </select>

    <div class="um2-toolbar__counter">
        <span class="js-um-counter"><?= count($rows); ?></span> user ditampilkan
    </div>
</div>

<!-- ============================================================ -->
<!-- DATATABLE HEADER BAR                                        -->
<!-- ============================================================ -->
<div class="um2-dt-header">
    <div class="um2-dt-header__left">
        Tampilkan
        <select id="umPageSize">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        data
    </div>
    <?php if (AuthController::isAdminSpmt()): ?>
    <div class="um2-dt-header__right">
        <div class="export-dropdown um2-export-dd">
            <button type="button" class="btn btn--primary detail-action export-dropdown__toggle js-um-export-toggle" aria-expanded="false" style="border-radius: 12px; gap: 6px;">
                EXPORT <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="export-dropdown__menu js-um-export-menu" style="display: none;">
                <a href="index.php?page=user-management&action=export&format=pdf" class="export-dropdown__item">Export PDF</a>
                <a href="index.php?page=user-management&action=export&format=xlsx" class="export-dropdown__item">Export Excel</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- TABLE — konsisten dgn data-table halaman lain                -->
<!-- ============================================================ -->
<div class="um2-table-wrap">
    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="data-table js-um-table" id="umTable" style="min-width: 860px;">
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
                        <td colspan="7" style="padding: 40px; color: #7b8ea8; font-size: 13px;">Tidak ada data user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $userId       = (int) ($row['id'] ?? 0);
                        $isActive     = (int) ($row['is_active'] ?? 0) === 1;
                        $role         = (string) ($row['role'] ?? 'user');
                        $name         = (string) ($row['nama_lengkap'] ?? '-');
                        $username     = (string) ($row['username'] ?? '-');
                        $email        = (string) ($row['email'] ?? '-');
                        $divisionLabel = (string) ($row['division_label'] ?? $row['unit_kerja_default'] ?? '-');
                        $createdAt    = (string) ($row['created_at'] ?? '-');
                        $lastLoginAt  = (string) ($row['last_login_at'] ?? '-');
                        $isSelf       = ($userId === (int) ($_SESSION['auth']['id'] ?? 0));
                        $bgColor      = getPastelColor($username);
                        $initials     = getInitials($name);
                        $statusClass  = $isActive ? 'um2-badge--active' : 'um2-badge--inactive';
                        $statusLabel  = $isActive ? 'Aktif' : 'Nonaktif';
                        ?>
                        <tr class="js-um-row"
                            data-status="<?= $isActive ? 'active' : 'pending'; ?>"
                            data-division="<?= e($divisionLabel); ?>"
                            data-search="<?= e(mb_strtolower(trim($name . ' ' . $email . ' ' . $username . ' ' . $divisionLabel . ' ' . $role), 'UTF-8')); ?>">

                            <!-- User -->
                            <td style="text-align: left;">
                                <div class="um2-user-cell">
                                    <div class="um2-avatar" style="background-color: <?= $bgColor; ?>;">
                                        <?= $initials; ?>
                                    </div>
                                    <div class="um2-user-info">
                                        <span class="um2-user-name" title="<?= e($name); ?>"><?= e($name); ?></span>
                                        <span class="um2-user-meta"><?= e($email); ?></span>
                                        <span class="um2-user-meta">@<?= e($username); ?></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Divisi -->
                            <td><?= e($divisionLabel); ?></td>

                            <!-- Role -->
                            <td>
                                <?php if (AuthController::isAdminSpmt()): ?>
                                    <form method="post" style="margin: 0;">
                                        <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                        <input type="hidden" name="user_action" value="role">
                                        <select name="role" class="um2-role-select <?= $role === 'admin' ? 'um2-role-select--admin' : 'um2-role-select--user'; ?>" onchange="this.form.submit()">
                                            <option value="user"  <?= $role === 'user'  ? 'selected' : ''; ?>>user</option>
                                            <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>admin</option>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="um2-role-badge um2-role-badge--<?= e($role); ?>"><?= e($role); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td>
                                <span class="um2-badge <?= $statusClass; ?>">
                                    <span class="um2-badge__dot"></span>
                                    <?= $statusLabel; ?>
                                </span>
                            </td>

                            <!-- Tanggal Daftar -->
                            <td style="font-size: 12px; color: #55729b; white-space: nowrap;"><?= e($createdAt); ?></td>

                            <!-- Login Terakhir -->
                            <td style="font-size: 12px; color: #55729b; white-space: nowrap;"><?= $lastLoginAt !== '-' ? e($lastLoginAt) : '<span style="color:#c0c8d8;">–</span>'; ?></td>

                            <!-- Aksi -->
                            <td>
                                <?php if (AuthController::isAdminSpmt()): ?>
                                    <div class="um2-dropdown">
                                        <button type="button" class="um2-dropdown-btn js-um-dropdown-toggle">
                                            Aksi <i class="fa-solid fa-chevron-down" style="font-size: 9px;"></i>
                                        </button>
                                        <div class="um2-dropdown-menu js-um-dropdown-menu">
                                            <?php if (!$isActive): ?>
                                                <form method="post" style="margin:0;">
                                                    <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                                    <input type="hidden" name="user_action" value="approve">
                                                    <button type="submit" class="um2-dropdown-item um2-dropdown-item--activate">
                                                        <i class="fa-solid fa-user-check"></i> Aktifkan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" style="margin:0;">
                                                    <input type="hidden" name="user_id" value="<?= $userId; ?>">
                                                    <input type="hidden" name="user_action" value="suspend">
                                                    <button type="submit" class="um2-dropdown-item um2-dropdown-item--deactivate" <?= $isSelf ? 'disabled title="Tidak dapat menonaktifkan akun sendiri"' : ''; ?>>
                                                        <i class="fa-solid fa-user-slash"></i> Nonaktifkan
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button" class="um2-dropdown-item um2-dropdown-item--delete js-um-delete-btn"
                                                    data-user-id="<?= $userId; ?>"
                                                    data-user-name="<?= e($name); ?>"
                                                    <?= $isSelf ? 'disabled title="Tidak dapat menghapus akun sendiri"' : ''; ?>>
                                                <i class="fa-regular fa-trash-can"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #c0c8d8; font-size: 13px;">–</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    <!-- Empty state for client-side filtering -->
                    <tr class="js-um-empty-state" style="display: none;">
                        <td colspan="7" style="padding: 40px; color: #7b8ea8; font-size: 13px;">Tidak ada user sesuai filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================ -->
<!-- PAGINATION                                                   -->
<!-- ============================================================ -->
<div class="um2-pagination js-um-pagination" <?= empty($rows) ? 'style="display:none;"' : ''; ?>>
    <div class="um2-pagination__info">
        Menampilkan <span class="js-um-pagination-range">1–10</span> dari <span class="js-um-pagination-total"><?= count($rows); ?></span> user
    </div>
    <div class="um2-pagination__controls js-um-pagination-controls">
        <!-- Generated by JS -->
    </div>
</div>

<!-- ============================================================ -->
<!-- DELETE CONFIRM MODAL                                         -->
<!-- ============================================================ -->
<div class="um2-modal-overlay js-um-modal" hidden>
    <div class="um2-modal">
        <div class="um2-modal__icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="um2-modal__title">Konfirmasi Hapus</h3>
        <p class="um2-modal__text">Apakah Anda yakin ingin menghapus user <strong class="js-um-modal-username"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
        <form method="post" class="js-um-modal-form" style="margin: 0;">
            <div class="um2-modal__footer">
                <button type="button" class="btn btn--ghost js-um-modal-cancel" style="min-width: 120px;">Batal</button>
                <input type="hidden" name="user_id" value="" class="js-um-modal-userid">
                <input type="hidden" name="user_action" value="delete">
                <button type="submit" class="btn btn--danger" style="min-width: 120px;">
                    <i class="fa-regular fa-trash-can"></i> Ya, hapus
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    /* ---- DROPDOWN & EXPORT DROPDOWN ---- */
    document.addEventListener('click', function (e) {
        // Row actions dropdown
        var toggleBtn = e.target.closest('.js-um-dropdown-toggle');
        if (toggleBtn) {
            e.stopPropagation();
            var menu = toggleBtn.closest('.um2-dropdown').querySelector('.js-um-dropdown-menu');
            var isOpen = menu.classList.contains('is-open');
            // Close all dropdowns
            document.querySelectorAll('.js-um-dropdown-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            document.querySelectorAll('.js-um-export-menu').forEach(function (m) { m.style.display = 'none'; });
            if (!isOpen) { menu.classList.add('is-open'); }
            return;
        }

        // Export dropdown
        var exportToggle = e.target.closest('.js-um-export-toggle');
        if (exportToggle) {
            e.stopPropagation();
            var exportMenu = exportToggle.closest('.um2-export-dd').querySelector('.js-um-export-menu');
            var isExportOpen = exportMenu.style.display === 'block';
            // Close all dropdowns
            document.querySelectorAll('.js-um-dropdown-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            document.querySelectorAll('.js-um-export-menu').forEach(function (m) { m.style.display = 'none'; });
            if (!isExportOpen) {
                exportMenu.style.display = 'block';
                exportToggle.setAttribute('aria-expanded', 'true');
            } else {
                exportToggle.setAttribute('aria-expanded', 'false');
            }
            return;
        }

        // Close on outside click
        if (!e.target.closest('.um2-dropdown') && !e.target.closest('.um2-export-dd')) {
            document.querySelectorAll('.js-um-dropdown-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            document.querySelectorAll('.js-um-export-menu').forEach(function (m) { m.style.display = 'none'; });
            document.querySelectorAll('.js-um-export-toggle').forEach(function (btn) { btn.setAttribute('aria-expanded', 'false'); });
        }
    });

    /* ---- DELETE MODAL ---- */
    var modal    = document.querySelector('.js-um-modal');
    var modalName = modal ? modal.querySelector('.js-um-modal-username') : null;
    var modalId  = modal ? modal.querySelector('.js-um-modal-userid') : null;

    document.addEventListener('click', function (e) {
        var deleteBtn = e.target.closest('.js-um-delete-btn');
        if (deleteBtn && modal) {
            var name = deleteBtn.getAttribute('data-user-name') || '';
            var id   = deleteBtn.getAttribute('data-user-id') || '';
            if (modalName) modalName.textContent = name;
            if (modalId) modalId.value = id;
            modal.hidden = false;
        }
        if (e.target.closest('.js-um-modal-cancel') && modal) { modal.hidden = true; }
        if (e.target === modal) { modal.hidden = true; }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal && !modal.hidden) modal.hidden = true; });

    /* ---- FILTER + SEARCH + PAGINATION ---- */
    var searchInput  = document.getElementById('umSearch');
    var statusFilter = document.getElementById('umStatusFilter');
    var divFilter    = document.getElementById('umDivisionFilter');
    var counter      = document.querySelector('.js-um-counter');
    var allRows      = Array.from(document.querySelectorAll('.js-um-row'));
    var emptyStates  = document.querySelectorAll('.js-um-empty-state');
    var paginationEl = document.querySelector('.js-um-pagination');
    var paginationRange = document.querySelector('.js-um-pagination-range');
    var paginationTotal = document.querySelector('.js-um-pagination-total');
    var paginationControls = document.querySelector('.js-um-pagination-controls');
    
    var pageSizeSelect = document.getElementById('umPageSize');
    var PAGE_SIZE = pageSizeSelect ? (parseInt(pageSizeSelect.value, 10) || 10) : 10;
    var currentPage = 1;
    var visibleRows = allRows.slice();

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            PAGE_SIZE = parseInt(this.value, 10) || 10;
            currentPage = 1;
            renderPage();
        });
    }

    function updateExportLinks(q, st, dv) {
        var pdfLink = document.querySelector('a[href*="action=export&format=pdf"]');
        var xlsxLink = document.querySelector('a[href*="action=export&format=xlsx"]');
        
        var queryParams = '&search=' + encodeURIComponent(q) + '&status=' + encodeURIComponent(st) + '&division=' + encodeURIComponent(dv);
        
        if (pdfLink) {
            pdfLink.href = 'index.php?page=user-management&action=export&format=pdf' + queryParams;
        }
        if (xlsxLink) {
            xlsxLink.href = 'index.php?page=user-management&action=export&format=xlsx' + queryParams;
        }
    }

    function applyFilters() {
        var rawSearch = searchInput ? searchInput.value.trim() : '';
        var q = rawSearch.toLowerCase();
        var st = statusFilter ? statusFilter.value : 'all';
        var dv = divFilter ? divFilter.value : 'all';

        visibleRows = allRows.filter(function (row) {
            var matchSearch = !q || (row.dataset.search || '').indexOf(q) !== -1;
            var matchStatus = st === 'all' || (row.dataset.status || '') === st;
            var matchDiv    = dv === 'all' || (row.dataset.division || '') === dv;
            return matchSearch && matchStatus && matchDiv;
        });

        currentPage = 1;
        updateExportLinks(rawSearch, st, dv);
        renderPage();
    }

    function renderPage() {
        var total = visibleRows.length;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end   = Math.min(start + PAGE_SIZE, total);

        // Hide all rows
        allRows.forEach(function (r) { r.style.display = 'none'; });
        // Show current page
        visibleRows.slice(start, end).forEach(function (r) { r.style.display = ''; });

        // Empty state
        emptyStates.forEach(function (es) { es.style.display = total === 0 ? '' : 'none'; });

        // Counter
        if (counter) counter.textContent = total;

        // Pagination bar
        if (paginationEl) {
            paginationEl.style.display = total === 0 ? 'none' : '';
            if (paginationRange) paginationRange.textContent = (start + 1) + '–' + end;
            if (paginationTotal) paginationTotal.textContent = total;
            renderPaginationButtons(Math.ceil(total / PAGE_SIZE));
        }
    }

    function renderPaginationButtons(totalPages) {
        if (!paginationControls) return;
        paginationControls.innerHTML = '';

        function mkBtn(label, page, disabled, active) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'um2-page-btn' + (active ? ' is-active' : '');
            btn.textContent = label;
            btn.disabled = disabled;
            btn.addEventListener('click', function () { currentPage = page; renderPage(); });
            return btn;
        }

        paginationControls.appendChild(mkBtn('«', 1, currentPage === 1, false));
        paginationControls.appendChild(mkBtn('‹', currentPage - 1, currentPage === 1, false));

        var start = Math.max(1, currentPage - 2);
        var end   = Math.min(totalPages, currentPage + 2);
        for (var i = start; i <= end; i++) {
            paginationControls.appendChild(mkBtn(i, i, false, i === currentPage));
        }

        paginationControls.appendChild(mkBtn('›', currentPage + 1, currentPage === totalPages || totalPages === 0, false));
        paginationControls.appendChild(mkBtn('»', totalPages, currentPage === totalPages || totalPages === 0, false));
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);
    if (divFilter) divFilter.addEventListener('change', applyFilters);

    // Initial render
    applyFilters();
})();
</script>
