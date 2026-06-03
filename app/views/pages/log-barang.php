<?php
$logFilters = $data['log_filters'] ?? ['selected' => ['year' => date('Y'), 'month' => 0, 'status' => '', 'sort' => 'newest', 'search' => ''], 'years' => [date('Y')], 'months' => [0 => 'Semua Bulan'], 'statuses' => ['' => 'Semua Status'], 'sorts' => ['newest' => 'Tanggal terbaru']];
$selected = $logFilters['selected'] ?? [];
$baseParams = [
    'page' => 'log-barang',
    'log_year' => $selected['year'] ?? date('Y'),
    'log_month' => $selected['month'] ?? 0,
    'log_date' => $selected['date'] ?? '',
    'log_status' => $selected['status'] ?? '',
    'log_sort' => $selected['sort'] ?? 'newest',
    'log_search' => $selected['search'] ?? '',
];
$exportPdfUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'export', 'format' => 'pdf']);
$exportXlsxUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'export', 'format' => 'xlsx']);
$shouldOpenModal = false;
$flash = $data['flash'] ?? null;
?>

<?php if (!empty($flash['message'])): ?>
    <div class="log-toast log-toast--<?= e($flash['type'] ?? 'success'); ?> js-log-toast" role="status" aria-live="polite">
        <div class="log-toast__content">
            <strong><?= e(($flash['type'] ?? 'success') === 'error' ? 'Gagal' : 'Berhasil'); ?></strong>
            <span><?= e($flash['message']); ?></span>
        </div>
        <button type="button" class="log-toast__close js-close-log-toast" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark"></i></button>
    </div>
<?php endif; ?>

<div class="detail-header detail-header--single-title">
    <h1>LOG BARANG</h1>
</div>
<div class="log-layout">
    <div class="log-chart-panel log-chart-panel--compact">
        <form class="log-filter-form" method="get" action="index.php">
            <input type="hidden" name="page" value="log-barang">
            <div class="log-tools log-tools--full">
                <div class="mini-search mini-search--input">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="log_search" value="<?= e((string) ($selected['search'] ?? '')); ?>" placeholder="Cari data log barang..." class="js-log-live-search">
                </div>
                <button type="button" class="icon-round js-toggle-log-filters" title="Filter"><i class="fa-solid fa-filter"></i></button>
                <button type="button" class="icon-round icon-round--accent js-open-log-modal" title="Tambah log" aria-label="Tambah log"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="log-advanced-filters js-log-filters-panel">
                <label>
                    <span>Tahun</span>
                    <select name="log_year" onchange="this.form.submit()">
                        <?php foreach (($logFilters['years'] ?? []) as $year): ?>
                            <option value="<?= e((string) $year); ?>" <?= (int) ($selected['year'] ?? 0) === (int) $year ? 'selected' : ''; ?>><?= e((string) $year); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Bulan</span>
                    <select name="log_month" onchange="this.form.submit()">
                        <?php foreach (($logFilters['months'] ?? []) as $monthValue => $monthLabel): ?>
                            <option value="<?= e((string) $monthValue); ?>" <?= (int) ($selected['month'] ?? 0) === (int) $monthValue ? 'selected' : ''; ?>><?= e((string) $monthLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Tanggal</span>
                    <input type="date" name="log_date" value="<?= e((string) ($logFilters['date_value'] ?? '')); ?>" onchange="this.form.submit()">
                </label>
                <label>
                    <span>Status</span>
                    <select name="log_status" onchange="this.form.submit()">
                        <?php foreach (($logFilters['statuses'] ?? []) as $statusValue => $statusLabel): ?>
                            <option value="<?= e((string) $statusValue); ?>" <?= (string) ($selected['status'] ?? '') === (string) $statusValue ? 'selected' : ''; ?>><?= e((string) $statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Urutan</span>
                    <select name="log_sort" onchange="this.form.submit()">
                        <?php foreach (($logFilters['sorts'] ?? []) as $sortValue => $sortLabel): ?>
                            <option value="<?= e((string) $sortValue); ?>" <?= (string) ($selected['sort'] ?? '') === (string) $sortValue ? 'selected' : ''; ?>><?= e((string) $sortLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </form>
        <div class="log-chart-box">
            <canvas id="logChart"></canvas>
            <div class="chart-month"><span></span><?= e(($data['inventory_flow']['month'] ?? 'SEMUA BULAN') . ' ' . ($data['inventory_flow']['year'] ?? '')); ?></div>
        </div>
        <div class="log-export-group">
            <a href="<?= e($exportPdfUrl); ?>" class="btn btn--primary btn--lg log-export">EXPORT PDF</a>
            <a href="<?= e($exportXlsxUrl); ?>" class="btn btn--ghost btn--lg log-export">EXPORT EXCEL</a>
        </div>
    </div>
    <div class="table-wrap table-wrap--log table-wrap--log-compact">
        <div class="log-table-scroll">
        <table class="data-table data-table--log data-table--log-compact">
            <colgroup>
                <col style="width: 6%;">
                <col style="width: 12%;">
                <col style="width: 25%;">
                <col style="width: 6%;">
                <col style="width: 14%;">
                <col style="width: 10%;">
                <col style="width: 11%;">
                <col style="width: 10%;">
            </colgroup>
            <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>No. PO</th>
                <th>File PDF</th>
                <th>Divisi</th>
                <th>Waktu Input</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody class="js-log-table-body">
            <?php if (!empty($data['log_rows'])): ?>
                <?php foreach ($data['log_rows'] as $row): ?>
                    <tr data-log-row data-search-text="<?= e(strtolower(implode(' ', [
                            (string) ($row['date'] ?? ''),
                            (string) ($row['item'] ?? ''),
                            (string) ($row['qty'] ?? ''),
                            (string) ($row['no_po'] ?? ''),
                            (string) ($row['status'] ?? ''),
                            (string) ($row['division'] ?? ''),
                            (string) ($row['pdf_name'] ?? ''),
                        ]))); ?>">
                        <td><?= e((string) $row['no']); ?></td>
                        <td><?= e((string) $row['date']); ?></td>
                        <td><?= e((string) $row['item']); ?></td>
                        <td><?= e((string) $row['qty']); ?></td>
                        <td><?= e((string) ($row['no_po'] ?: '-')); ?></td>
                        <td>
                            <?php if (!empty($row['pdf'])): ?>
                                <a class="table-link" target="_blank" href="<?= e('index.php?' . http_build_query($baseParams + ['action' => 'download_po', 'file' => $row['pdf']])); ?>">Lihat PDF</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($row['division'] ?? '-')); ?></td>
                        <td><?= e((string) ($row['created_time'] ?? '-')); ?></td>
                        <td><span class="badge badge--<?= e((string) $row['status_class']); ?>"><?= e((string) $row['status']); ?></span></td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn-action btn-action--edit js-edit-log-btn"
                                        data-id="<?= e((string) $row['id']); ?>"
                                        data-tanggal="<?= e((string) ($row['raw_date'] ?? '')); ?>"
                                        data-nama="<?= e((string) $row['item']); ?>"
                                        data-status="<?= e((string) $row['status']); ?>"
                                        data-qty="<?= e((string) $row['qty']); ?>"
                                        data-no-po="<?= e((string) $row['no_po']); ?>"
                                        data-divisi="<?= e((string) $row['division']); ?>"
                                        data-pdf-name="<?= e((string) ($row['pdf_name'] ?? '')); ?>"
                                        data-keterangan="<?= e((string) ($row['keterangan'] ?? '')); ?>">Edit</button>
                                <form method="post" action="index.php?page=log-barang" class="js-confirm-delete" data-confirm-message="Hapus log barang ini?">
                                    <input type="hidden" name="action" value="delete_log_barang">
                                    <input type="hidden" name="id" value="<?= e((string) $row['id']); ?>">
                                    <button type="submit" class="btn-action btn-action--delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="js-log-empty-search" hidden>
                    <td colspan="8" class="empty-state">Data yang dicari tidak ditemukan pada tabel.</td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="empty-state">Data log barang tidak ditemukan.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="log-modal" id="logBarangModal" <?= $shouldOpenModal ? "" : "hidden"; ?> data-auto-open="<?= $shouldOpenModal ? "1" : "0"; ?>" aria-hidden="<?= $shouldOpenModal ? "false" : "true"; ?>">
    <div class="log-modal__dialog">
        <div class="log-modal__header">
            <h3 id="logModalTitle">Tambah Log Barang</h3>
            <button type="button" class="icon-round js-close-log-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" action="index.php?page=log-barang" enctype="multipart/form-data" class="log-modal__form">
            <input type="hidden" name="action" value="save_log_barang" id="logFormAction">
            <input type="hidden" name="id" value="" id="logId">
            <div class="log-modal__grid">
                <label><span>Tanggal</span><input type="date" name="tanggal" id="logTanggal" required></label>
                <label><span>Status</span>
                    <select name="status" id="logStatus" required>
                        <option value="MASUK">Barang Masuk</option>
                        <option value="KELUAR">Barang Keluar</option>
                    </select>
                </label>
                <label><span>Nama Barang</span><input type="text" name="nama_barang" id="logNamaBarang" placeholder="Masukkan nama barang" required></label>
                <label><span>Qty</span><input type="number" min="1" name="qty" id="logQty" value="1" required></label>
                <label><span>No. PO</span><input type="text" name="no_po" id="logNoPo" placeholder="Masukkan No. PO"></label>
                <label><span>Divisi</span>
                    <select name="divisi" id="logDivisi">
                        <option value="">-- Pilih Divisi --</option>
                        <?php foreach (($data['log_division_options'] ?? []) as $opt): ?>
                            <option value="<?= e((string) ($opt['division_label'] ?? '')); ?>">
                                <?= e((string) ($opt['division_label'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                        
                    </select>
                </label>
                <label class="log-modal__field--full"><span>Keterangan</span><textarea name="keterangan" id="logKeterangan"  placeholder="Masukkan keterangan" rows="3"></textarea></label>
            </div>
            <div class="log-modal__actions">
                <button type="button" class="btn btn--ghost js-close-log-modal">Batal</button>
                <button type="submit" class="btn btn--primary log-modal__save-btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
