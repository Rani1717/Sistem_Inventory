<?php 
/** @var array $data */
$filters = $data['alert_filters'] ?? [];
$rows = $data['alert_rows'] ?? [];
$stats = $data['alert_stats'] ?? ['total' => 0, 'belum_baca' => 0, 'kritis' => 0, 'peringatan' => 0, 'info' => 0];

renderMainHeader($data, 'RIWAYAT NOTIFIKASI & ALERT'); 
?>

<style>
@keyframes alertFlash {
    0% { background-color: rgba(245, 158, 11, 0.25) !important; }
    100% { background-color: transparent; }
}
.alert-row.is-focused-alert {
    animation: alertFlash 3s ease-in-out;
}
.alert-row.is-focused-alert td {
    border-top: 1.5px solid #f59e0b !important;
    border-bottom: 1.5px solid #f59e0b !important;
}
.alert-row.is-focused-alert td:first-child {
    border-left: 4px solid #f59e0b !important;
}
.alert-row.is-focused-alert td:last-child {
    border-right: 1.5px solid #f59e0b !important;
}

/* Custom filter bar override for Notifikasi & Alert */
.alert-filter-bar {
    grid-template-columns: repeat(4, 1fr) auto !important;
    margin-bottom: 1.5rem !important;
}

/* Custom dashboard summary grid for 5 cards in one row on desktop */
.alert-dashboard-summary {
    grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
}

/* ===== ZOOM-SAFE RESPONSIVE TABLE ===== */
.alert-table-outer {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--radius-xl, 16px);
    background: rgba(255, 255, 255, 0.95);
    box-shadow: var(--shadow-soft, 0 2px 12px rgba(0,0,0,0.06));
    border: 1px solid rgba(42, 102, 165, 0.12);
    margin-bottom: 1.5rem;
}
.alert-table-outer table.data-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    min-width: 900px;
    border: none;
    table-layout: auto;
}
.alert-table-outer th,
.alert-table-outer td {
    white-space: nowrap;
}
.alert-table-outer td.alert-td-judul {
    white-space: normal;
    min-width: 200px;
    max-width: 320px;
    word-break: break-word;
}

/* ===== ACTION CELL: compact layout ===== */
.alert-aksi-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
    justify-content: center;
    align-items: center;
}
.alert-aksi-row {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: center;
    flex-wrap: nowrap;
}
.alert-aksi-wrap select {
    height: 24px !important;
    max-height: 24px !important;
    min-height: 0 !important;
    border-radius: 5px !important;
    font-size: 9.5px !important;
    font-family: var(--font-head), sans-serif !important;
    font-weight: 600 !important;
    padding: 0 14px 0 6px !important;
    margin: 0 !important;
    border: 1px solid rgba(42, 102, 165, 0.25) !important;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='5' viewBox='0 0 8 5'%3E%3Cpath d='M0 0l4 5 4-5z' fill='%23666'/%3E%3C/svg%3E") no-repeat right 4px center !important;
    cursor: pointer;
    outline: none;
    min-width: 0;
    max-width: 130px;
    width: 12.5em !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    line-height: 22px !important;
    box-sizing: border-box !important;
}
.alert-aksi-wrap .btn--sm,
.alert-aksi-wrap .btn-action {
    height: 24px !important;
    min-height: 0 !important;
    font-size: 10px !important;
    padding: 0 6px !important;
    border-radius: 5px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 2px !important;
    box-sizing: border-box !important;
}
.alert-aksi-wrap .btn-action--delete {
    width: 24px !important;
    height: 24px !important;
    min-height: 0 !important;
    padding: 0 !important;
    justify-content: center !important;
}

/* ===== MARK ALL READ BUTTON: sized to match filter bar ===== */
.alert-mark-all-btn {
    height: 34px;
    border-radius: 8px;
    font-weight: 600;
    padding: 0 14px;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

@media (max-width: 1200px) {
    .alert-filter-bar {
        grid-template-columns: repeat(4, 1fr) !important;
    }
    .alert-filter-bar__actions-wrapper {
        grid-column: span 4;
        justify-content: flex-end;
    }
    .alert-dashboard-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 768px) {
    .alert-filter-bar {
        grid-template-columns: 1fr !important;
    }
    .alert-filter-bar__actions-wrapper {
        grid-column: span 1;
        justify-content: stretch;
    }
    .alert-filter-bar__actions-wrapper .btn,
    .alert-filter-bar__actions-wrapper .btn--ghost {
        flex: 1;
    }
    .alert-dashboard-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 480px) {
    .alert-dashboard-summary {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- Stat Cards (5 card horizontal) -->
<div class="dashboard-summary alert-dashboard-summary" style="margin-top: 24px; margin-bottom: 24px;">
    <!-- Total Alert -->
    <article class="metric-card" style="flex: 1; min-width: 180px;">
        <h3>TOTAL ALERT</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--c-primary); margin-top: 10px;">
            <?= (int) $stats['total']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Total riwayat alert terdeteksi</div>
    </article>

    <!-- Belum Dibaca -->
    <article class="metric-card" style="flex: 1; min-width: 180px;">
        <h3>BELUM DIBACA</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: <?= $stats['belum_baca'] > 0 ? 'var(--c-red-deep)' : 'var(--c-primary)'; ?>; margin-top: 10px;">
            <?= (int) $stats['belum_baca']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Butuh perhatian / dibaca</div>
    </article>

    <!-- Kritis -->
    <article class="metric-card" style="flex: 1; min-width: 180px;">
        <h3>KRITIS</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--c-red-deep); margin-top: 10px;">
            <?= (int) $stats['kritis']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Prioritas utama & mendesak</div>
    </article>

    <!-- Peringatan -->
    <article class="metric-card" style="flex: 1; min-width: 180px;">
        <h3>PERINGATAN</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: #b45309; margin-top: 10px;">
            <?= (int) $stats['peringatan']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Tinjau kembali dalam 24 jam</div>
    </article>

    <!-- Belum Ditangani -->
    <article class="metric-card" style="flex: 1; min-width: 180px;">
        <h3>BELUM DITANGANI</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: <?= ($stats['belum_ditangani'] ?? 0) > 0 ? 'var(--c-red-deep)' : 'var(--c-primary)'; ?>; margin-top: 10px;">
            <?= (int) ($stats['belum_ditangani'] ?? 0); ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Alert belum ditindaklanjuti</div>
    </article>
</div>

<!-- Filter Bar (GET Form) -->
<form method="get" action="index.php" class="complaint-filter-bar alert-filter-bar">
    <input type="hidden" name="page" value="notifikasi-alert">
    
    <div class="complaint-filter-bar__field">
        <span>Kategori</span>
        <select name="alert_kategori">
            <option value="">Semua Kategori</option>
            <?php foreach (['PC', 'CCTV', 'KELUHAN', 'LOG', 'MONITORING'] as $k): ?>
                <option value="<?= e($k); ?>" <?= ($filters['kategori'] ?? '') === $k ? 'selected' : ''; ?>><?= e($k); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="complaint-filter-bar__field">
        <span>Level</span>
        <select name="alert_level">
            <option value="">Semua Level</option>
            <?php foreach (['KRITIS', 'PERINGATAN', 'INFO'] as $l): ?>
                <option value="<?= e($l); ?>" <?= ($filters['level'] ?? '') === $l ? 'selected' : ''; ?>><?= e($l); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="complaint-filter-bar__field">
        <span>Status</span>
        <select name="alert_read">
            <option value="">Semua Status</option>
            <option value="0" <?= ($filters['is_read'] ?? '') === '0' ? 'selected' : ''; ?>>Belum Dibaca</option>
            <option value="1" <?= ($filters['is_read'] ?? '') === '1' ? 'selected' : ''; ?>>Sudah Dibaca</option>
        </select>
    </div>

    <div class="complaint-filter-bar__field">
        <span>Tindak Lanjut</span>
        <select name="alert_tindak_lanjut">
            <option value="">Semua</option>
            <option value="BELUM_DITANGANI" <?= ($filters['tindak_lanjut'] ?? '') === 'BELUM_DITANGANI' ? 'selected' : ''; ?>>Belum Ditangani</option>
            <option value="SEDANG_DITANGANI" <?= ($filters['tindak_lanjut'] ?? '') === 'SEDANG_DITANGANI' ? 'selected' : ''; ?>>Sedang Ditangani</option>
            <option value="SELESAI" <?= ($filters['tindak_lanjut'] ?? '') === 'SELESAI' ? 'selected' : ''; ?>>Selesai</option>
        </select>
    </div>

    <div class="complaint-filter-bar__actions alert-filter-bar__actions-wrapper" style="margin-top: 0;">
        <button type="submit" class="btn btn--primary" style="min-height: 46px; padding: 0 24px; border-radius: 12px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer;">
            <i class="fa-solid fa-filter"></i> Terapkan
        </button>
        <a href="index.php?page=notifikasi-alert&reset_context=1" class="btn btn--ghost" style="min-height: 46px; padding: 0 24px; border-radius: 12px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </a>
    </div>
</form>

<!-- Action Toolbar -->
<?php
$hasUnread = false;
foreach ($rows as $r) {
    if ((int) ($r['is_read'] ?? 0) === 0) {
        $hasUnread = true;
        break;
    }
}
?>
<?php if ($hasUnread): ?>
    <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
        <form method="post" action="index.php?page=notifikasi-alert" style="margin: 0;">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn--primary alert-mark-all-btn">
                <i class="fa-solid fa-check-double"></i> Tandai Semua Sudah Dibaca
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Alerts Table or Empty State -->
<?php if (empty($rows)): ?>
    <div class="empty-state" style="background: rgba(255, 255, 255, 0.95); padding: 48px; border-radius: var(--radius-xl); box-shadow: var(--shadow-soft); text-align: center; margin-top: 20px; border: 1px solid rgba(42, 102, 165, 0.12);">
        <i class="fa-solid fa-bell-slash" style="font-size: 48px; color: var(--c-muted-text); margin-bottom: 16px;"></i>
        <p style="font-size: 16px; color: var(--c-primary); font-weight: 600; margin: 0;">Tidak ada notifikasi saat ini. Sistem berjalan normal.</p>
    </div>
<?php else: ?>
    <div class="alert-table-outer">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">No</th>
                        <th style="padding: 12px 10px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Waktu</th>
                        <th style="padding: 12px 10px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Kategori</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Level</th>
                        <th style="padding: 12px 10px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Judul &amp; Keterangan</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Status</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Tindak Lanjut</th>
                        <th style="padding: 12px 10px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($rows as $row): 
                        $isRead = (int) ($row['is_read'] ?? 0);
                        $level = strtoupper(trim((string) ($row['level'] ?? 'INFO')));
                        
                        $levelClass = 'alert-level-info';
                        if ($level === 'KRITIS') {
                            $levelClass = 'alert-level-kritis';
                        } elseif ($level === 'PERINGATAN') {
                            $levelClass = 'alert-level-peringatan';
                        }

                        $tindakLanjut = $row['status_tindak_lanjut'] ?? 'BELUM_DITANGANI';
                        if ($tindakLanjut === 'SELESAI') {
                            $tlText = 'Selesai';
                            $tlStyle = 'background: rgba(30, 136, 67, 0.12) !important; color: #1e8843 !important; border: 1px solid rgba(30, 136, 67, 0.25);';
                        } elseif ($tindakLanjut === 'SEDANG_DITANGANI') {
                            $tlText = 'Sedang Ditangani';
                            $tlStyle = 'background: rgba(245, 158, 11, 0.14) !important; color: #b45309 !important; border: 1px solid rgba(245, 158, 11, 0.25);';
                        } else {
                            $tlText = 'Belum Ditangani';
                            $tlStyle = 'background: rgba(201, 30, 30, 0.12) !important; color: var(--c-red-deep) !important; border: 1px solid rgba(201, 30, 30, 0.25);';
                        }
                    ?>
                        <tr class="alert-row" data-alert-id="<?= (int) $row['id']; ?>" style="transition: background 0.5s; <?= $isRead === 0 ? 'background: rgba(27, 62, 111, 0.03);' : ''; ?>">
                            <td style="padding: 10px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); font-weight: 600;"><?= $no++; ?></td>
                            <td style="padding: 10px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); font-size: 13px; font-weight: 500;">
                                <?= e(date('d/m/Y H:i', strtotime($row['created_at']))); ?>
                            </td>
                            <td style="padding: 10px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); font-weight: 600; font-size: 13px;">
                                <?= e($row['kategori']); ?>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <span class="badge <?= $levelClass; ?>" style="font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 11px; display: inline-block;">
                                    <?= e($level); ?>
                                </span>
                            </td>
                            <td class="alert-td-judul" style="padding: 10px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <strong style="display: block; font-size: 13.5px; color: var(--c-primary); margin-bottom: 3px;"><?= e($row['judul']); ?></strong>
                                <span style="font-size: 12.5px; color: var(--c-primary); display: block; opacity: 0.85; line-height: 1.4;"><?= e($row['keterangan']); ?></span>
                                <?php if ($isRead === 1 && !empty($row['dibaca_oleh'])): ?>
                                    <small style="display: block; margin-top: 4px; font-size: 10.5px; color: var(--c-muted-text); font-style: italic;">
                                        Dibaca oleh: <?= e($row['dibaca_oleh']); ?> pada <?= e(date('d/m/Y H:i', strtotime($row['dibaca_at']))); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <?php if ($isRead === 0): ?>
                                    <span class="badge badge--bad" style="font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; display: inline-block;">Belum Dibaca</span>
                                <?php else: ?>
                                    <span class="badge badge--good" style="font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; display: inline-block;">Sudah Dibaca</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <span class="badge" style="font-weight: 700; padding: 3px 8px; border-radius: 6px; font-size: 11px; display: inline-block; <?= $tlStyle; ?>">
                                    <?= e($tlText); ?>
                                </span>
                                <?php if ($tindakLanjut !== 'BELUM_DITANGANI' && !empty($row['ditangani_oleh'])): ?>
                                    <small style="display: block; margin-top: 4px; font-size: 10.5px; color: var(--c-muted-text); font-style: italic; text-align: center;">
                                        Oleh: <?= e($row['ditangani_oleh']); ?><br>pada <?= e(date('d/m/Y H:i', strtotime($row['ditangani_at']))); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <div class="alert-aksi-wrap">
                                    <!-- Top Row: Select Dropdown & Dibaca Button -->
                                    <div class="alert-aksi-row alert-aksi-row--top">
                                        <form method="post" action="index.php?page=notifikasi-alert" style="margin: 0;">
                                            <input type="hidden" name="action" value="update_status_tindak_lanjut">
                                            <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                            <select name="status_tindak_lanjut" onchange="this.form.submit()" title="Ubah Status Tindak Lanjut">
                                                <option value="BELUM_DITANGANI" <?= $tindakLanjut === 'BELUM_DITANGANI' ? 'selected' : ''; ?>>Belum Ditangani</option>
                                                <option value="SEDANG_DITANGANI" <?= $tindakLanjut === 'SEDANG_DITANGANI' ? 'selected' : ''; ?>>Sedang Ditangani</option>
                                                <option value="SELESAI" <?= $tindakLanjut === 'SELESAI' ? 'selected' : ''; ?>>Selesai</option>
                                            </select>
                                        </form>

                                        <?php if ($isRead === 0): ?>
                                            <form method="post" action="index.php?page=notifikasi-alert" style="margin: 0;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                                <button type="submit" class="btn btn--primary btn--sm" title="Tandai Dibaca">
                                                    <i class="fa-solid fa-check"></i> Dibaca
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Bottom Row: Lihat & Hapus Buttons -->
                                    <?php if (!empty($row['link_url']) || AuthController::isAdminSpmt()): ?>
                                        <div class="alert-aksi-row alert-aksi-row--bottom">
                                            <?php if (!empty($row['link_url'])): ?>
                                                <a href="<?= e($row['link_url']); ?>" class="btn btn--ghost btn--sm" title="Lihat Detail Halaman Terkait">
                                                    <i class="fa-solid fa-eye"></i> Lihat
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (AuthController::isAdminSpmt()): ?>
                                                <form method="post" action="index.php?page=notifikasi-alert" class="js-confirm-delete" data-confirm-message="Apakah Anda yakin ingin menghapus alert ini?" style="margin: 0;">
                                                    <input type="hidden" name="action" value="delete_alert">
                                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                                    <button type="submit" class="btn-action btn-action--delete" style="border: none; background: #fce8e6; color: #c5221f; cursor: pointer; transition: background 0.2s;" title="Hapus Notifikasi">
                                                        <i class="fa-solid fa-trash-can" style="font-size: 11px;"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
    </div>
<?php endif; ?>
