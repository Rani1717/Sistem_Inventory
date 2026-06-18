<?php 
/** @var array $data */
$filters = $data['alert_filters'] ?? [];
$rows = $data['alert_rows'] ?? [];
$stats = $data['alert_stats'] ?? ['total' => 0, 'belum_baca' => 0, 'kritis' => 0, 'peringatan' => 0, 'info' => 0];

renderMainHeader($data, 'RIWAYAT NOTIFIKASI & ALERT'); 
?>

<style>
/* Custom filter bar override for Notifikasi & Alert */
.alert-filter-bar {
    grid-template-columns: repeat(3, 1fr) auto !important;
    margin-bottom: 24px !important;
}

@media (max-width: 1200px) {
    .alert-filter-bar {
        grid-template-columns: repeat(3, 1fr) !important;
    }
    .alert-filter-bar__actions-wrapper {
        grid-column: span 3;
        justify-content: flex-end;
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
}
</style>

<!-- Stat Cards (4 card horizontal) -->
<div class="dashboard-summary" style="margin-top: 24px; margin-bottom: 24px;">
    <!-- Total Alert -->
    <article class="metric-card" style="flex: 1; min-width: 200px;">
        <h3>TOTAL ALERT</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--c-primary); margin-top: 10px;">
            <?= (int) $stats['total']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Total riwayat alert terdeteksi</div>
    </article>

    <!-- Belum Dibaca -->
    <article class="metric-card" style="flex: 1; min-width: 200px;">
        <h3>BELUM DIBACA</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: <?= $stats['belum_baca'] > 0 ? 'var(--c-red-deep)' : 'var(--c-primary)'; ?>; margin-top: 10px;">
            <?= (int) $stats['belum_baca']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Butuh perhatian / tindak lanjut</div>
    </article>

    <!-- Kritis -->
    <article class="metric-card" style="flex: 1; min-width: 200px;">
        <h3>KRITIS</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--c-red-deep); margin-top: 10px;">
            <?= (int) $stats['kritis']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Prioritas utama & mendesak</div>
    </article>

    <!-- Peringatan -->
    <article class="metric-card" style="flex: 1; min-width: 200px;">
        <h3>PERINGATAN</h3>
        <div style="font-size: 2.2rem; font-weight: 700; color: #b45309; margin-top: 10px;">
            <?= (int) $stats['peringatan']; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--c-muted-text); margin-top: 6px;">Tinjau kembali dalam 24 jam</div>
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
            <button type="submit" class="btn btn--primary" style="height: 38px; border-radius: 8px; font-weight: 600; padding: 0 16px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
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
    <div class="table-wrap" style="background: rgba(255, 255, 255, 0.95); border-radius: var(--radius-xl); box-shadow: var(--shadow-soft); border: 1px solid rgba(42, 102, 165, 0.12); margin-bottom: 24px;">
        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="data-table" style="border-collapse: separate; border-spacing: 0; width: 100%; border: none;">
                <thead>
                    <tr>
                        <th style="padding: 14px 16px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 60px;">No</th>
                        <th style="padding: 14px 16px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 140px;">Waktu</th>
                        <th style="padding: 14px 16px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 120px;">Kategori</th>
                        <th style="padding: 14px 16px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 120px;">Level</th>
                        <th style="padding: 14px 16px; text-align: left; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15);">Judul &amp; Keterangan</th>
                        <th style="padding: 14px 16px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 130px;">Status</th>
                        <th style="padding: 14px 16px; text-align: center; font-weight: 600; border-bottom: 1.5px solid rgba(42, 102, 165, 0.15); width: 180px;">Aksi</th>
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
                    ?>
                        <tr class="alert-row" style="transition: background 0.2s; <?= $isRead === 0 ? 'background: rgba(27, 62, 111, 0.03);' : ''; ?>">
                            <td style="padding: 16px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); font-weight: 600;"><?= $no++; ?></td>
                            <td style="padding: 16px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); white-space: nowrap; font-size: 13.5px; font-weight: 500;">
                                <?= e(date('d/m/Y H:i', strtotime($row['created_at']))); ?>
                            </td>
                            <td style="padding: 16px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1); font-weight: 600; font-size: 13.5px;">
                                <?= e($row['kategori']); ?>
                            </td>
                            <td style="padding: 16px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <span class="badge <?= $levelClass; ?>" style="font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; display: inline-block;">
                                    <?= e($level); ?>
                                </span>
                            </td>
                            <td style="padding: 16px; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <strong style="display: block; font-size: 14.5px; color: var(--c-primary); margin-bottom: 4px;"><?= e($row['judul']); ?></strong>
                                <span style="font-size: 13px; color: var(--c-primary); display: block; opacity: 0.85; line-height: 1.4;"><?= e($row['keterangan']); ?></span>
                                <?php if ($isRead === 1 && !empty($row['dibaca_oleh'])): ?>
                                    <small style="display: block; margin-top: 6px; font-size: 11px; color: var(--c-muted-text); font-style: italic;">
                                        Dibaca oleh: <?= e($row['dibaca_oleh']); ?> pada <?= e(date('d/m/Y H:i', strtotime($row['dibaca_at']))); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <?php if ($isRead === 0): ?>
                                    <span class="badge badge--bad" style="font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; display: inline-block;">Belum Dibaca</span>
                                <?php else: ?>
                                    <span class="badge badge--good" style="font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; display: inline-block;">Sudah Dibaca</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px; text-align: center; vertical-align: middle; border-bottom: 1px solid rgba(42, 102, 165, 0.1);">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center; flex-wrap: wrap;">
                                    <?php if ($isRead === 0): ?>
                                        <form method="post" action="index.php?page=notifikasi-alert" style="margin: 0;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                            <button type="submit" class="btn btn--primary btn--sm" style="height: 32px; border-radius: 6px; font-size: 12px; padding: 0 12px; display: inline-flex; align-items: center; gap: 4px;" title="Tandai Dibaca">
                                                <i class="fa-solid fa-check"></i> Dibaca
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($row['link_url'])): ?>
                                        <a href="<?= e($row['link_url']); ?>" class="btn btn--ghost btn--sm" style="height: 32px; border-radius: 6px; font-size: 12px; padding: 0 12px; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Detail Halaman Terkait">
                                            <i class="fa-solid fa-eye"></i> Lihat
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (AuthController::isAdminSpmt()): ?>
                                        <form method="post" action="index.php?page=notifikasi-alert" class="js-confirm-delete" data-confirm-message="Apakah Anda yakin ingin menghapus alert ini?" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete_alert">
                                            <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                            <button type="submit" class="btn-action btn-action--delete" style="border: none; background: #fce8e6; color: #c5221f; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;" title="Hapus Notifikasi">
                                                <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
