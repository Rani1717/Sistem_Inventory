<?php
$logFilters = $data['log_filters'] ?? ['selected' => ['year' => date('Y'), 'month' => (int)date('m'), 'month_year' => date('Y-m'), 'status' => '', 'sort' => 'newest', 'search' => '', 'division' => ''], 'years' => [date('Y')], 'months' => [], 'statuses' => ['' => 'Semua Status'], 'sorts' => ['newest' => 'Tanggal terbaru']];
$selected = $logFilters['selected'] ?? [];
$baseParams = [
    'page' => 'log-barang',
    'log_year' => $selected['year'] ?? date('Y'),
    'log_month' => ($selected['month'] ?? 0) === 0 ? 'all' : ($selected['month'] ?? date('n')),
    'log_status' => $selected['status'] ?? '',
    'log_sort' => $selected['sort'] ?? 'newest',
    'log_search' => $selected['search'] ?? '',
    'log_division' => $selected['division'] ?? '',
];
$exportPdfUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'export', 'format' => 'pdf']);
$exportXlsxUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'export', 'format' => 'xlsx']);
$shouldOpenModal = false;
$flash = $data['flash'] ?? null;

// Summary stats
$summary = $data['log_summary'] ?? ['total_transaksi' => 0, 'barang_masuk_qty' => 0, 'barang_keluar_qty' => 0, 'total_nilai_masuk' => 0.00];
$isAllMonths = (($selected['month'] ?? 0) === 0);
?>

<style>
    /* Premium Summary Cards Grid */
    .log-summary-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    .summary-card {
        display: flex;
        align-items: center;
        background: #fff;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #eef2f6;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.06);
    }
    .summary-card__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 10px;
        font-size: 24px;
        margin-right: 16px;
        flex-shrink: 0;
    }
    .summary-card__icon--blue {
        background: #e8f0fe;
        color: #1a73e8;
    }
    .summary-card__icon--green {
        background: #e6f7ed;
        color: #2e7d32;
    }
    .summary-card__icon--red {
        background: #fce8e6;
        color: #c5221f;
    }
    .summary-card__icon--orange {
        background: #fef7e0;
        color: #b06000;
    }
    .summary-card__info {
        display: flex;
        flex-direction: column;
    }
    .summary-card__label {
        font-size: 11px;
        font-weight: 600;
        color: #70757a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .summary-card__value {
        font-size: 18px;
        font-weight: 700;
        color: #202124;
    }

    /* Redesigned Filter & Actions Bar */
    .log-controls-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #eef2f6;
        margin-bottom: 24px;
    }
    .log-filters-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .log-filter-select {
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #dadce0;
        font-size: 13px;
        font-family: inherit;
        color: #3c4043;
        background-color: #fff;
        outline: none;
        min-width: 140px;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .log-filter-select:focus {
        border-color: #1a73e8;
    }
    .log-actions-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .btn-add-log {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background-color: #1a73e8;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-add-log:hover {
        background-color: #1557b0;
    }

    /* Unified Export Dropdown */
    .export-dropdown {
        position: relative;
        display: inline-block;
    }
    .btn-export-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background-color: #f1f3f4;
        color: #3c4043;
        border: 1px solid #dadce0;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s;
    }
    .btn-export-trigger:hover {
        background-color: #e8eaed;
    }
    .export-dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 6px;
        background-color: #fff;
        border: 1px solid #dadce0;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        z-index: 100;
        min-width: 140px;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-10px);
        pointer-events: none;
        transition: opacity 0.2s, transform 0.2s;
    }
    .export-dropdown.is-open .export-dropdown-menu {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .export-dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        font-size: 13px;
        color: #3c4043;
        text-decoration: none;
        transition: background-color 0.1s;
    }
    .export-dropdown-item:hover {
        background-color: #f1f3f4;
        color: #202124;
    }

    /* DataTable Headers & Footers styling */
    .datatable-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        background: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        border: 1px solid #eef2f6;
    }
    .datatable-length {
        font-size: 13px;
        color: #5f6368;
    }
    .datatable-length select {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #dadce0;
        font-size: 13px;
        font-family: inherit;
        color: #3c4043;
        cursor: pointer;
        margin: 0 4px;
    }
    .datatable-filter-wrap {
        position: relative;
    }
    .datatable-filter-wrap i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #80868b;
        font-size: 12px;
    }
    .datatable-filter-wrap input {
        padding: 6px 12px 6px 30px;
        border-radius: 6px;
        border: 1px solid #dadce0;
        font-size: 13px;
        font-family: inherit;
        color: #3c4043;
        width: 220px;
        transition: border-color 0.2s;
    }
    .datatable-filter-wrap input:focus {
        border-color: #1a73e8;
        outline: none;
    }
    .datatable-footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 16px;
        background: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        border: 1px solid #eef2f6;
    }
    .datatable-info {
        font-size: 13px;
        color: #5f6368;
    }
    .datatable-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .btn-paginate {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 6px;
        border-radius: 6px;
        border: 1px solid #dadce0;
        background: #fff;
        color: #3c4043;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-paginate:hover:not(:disabled) {
        background: #f1f3f4;
        border-color: #c4c7c5;
    }
    .btn-paginate.is-active {
        background: #1a73e8;
        color: #fff;
        border-color: #1a73e8;
    }
    .btn-paginate:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    @media (max-width: 1024px) {
        .log-summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        .log-summary-cards {
            grid-template-columns: 1fr;
        }
        .log-controls-row, .datatable-header-row, .datatable-footer-row {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

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

<div class="log-layout" style="display: block;">
    <!-- Bagian 1: Summary Cards -->
    <div class="log-summary-cards">
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--blue">
                <i class="ti ti-box"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Transaksi</span>
                <span class="summary-card__value"><?= e($summary['total_transaksi']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--green">
                <i class="ti ti-package-import"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Barang Masuk (<?= $isAllMonths ? 'Semua Bulan' : 'Bulan ini'; ?>)</span>
                <span class="summary-card__value"><?= e($summary['barang_masuk_qty']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--red">
                <i class="ti ti-package-export"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Barang Keluar (<?= $isAllMonths ? 'Semua Bulan' : 'Bulan ini'; ?>)</span>
                <span class="summary-card__value"><?= e($summary['barang_keluar_qty']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--orange">
                <i class="ti ti-currency-dollar"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Nilai Masuk (<?= $isAllMonths ? 'Semua Bulan' : 'Bulan ini'; ?>)</span>
                <span class="summary-card__value">Rp <?= number_format($summary['total_nilai_masuk'], 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Bagian 2: Baris Filter & Aksi -->
    <form class="log-filter-form" method="get" action="index.php">
        <input type="hidden" name="page" value="log-barang">
        
        <div class="log-controls-row">
            <div class="log-filters-group">
                <!-- Dropdown Status -->
                <select name="log_status" class="log-filter-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="MASUK" <?= ($selected['status'] ?? '') === 'MASUK' ? 'selected' : ''; ?>>Barang Masuk</option>
                    <option value="KELUAR" <?= ($selected['status'] ?? '') === 'KELUAR' ? 'selected' : ''; ?>>Barang Keluar</option>
                </select>

                <!-- Dropdown Divisi -->
                <select name="log_division" class="log-filter-select" onchange="this.form.submit()">
                    <option value="">Semua Divisi</option>
                    <?php foreach (($data['log_distinct_divisions'] ?? []) as $div): ?>
                        <option value="<?= e($div); ?>" <?= ($selected['division'] ?? '') === $div ? 'selected' : ''; ?>><?= e($div); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Dropdown Bulan -->
                <select name="log_month" class="log-filter-select" onchange="this.form.submit()">
                    <option value="all" <?= ($selected['month'] ?? 0) === 0 ? 'selected' : ''; ?>>Semua Bulan</option>
                    <?php
                    $monthsList = [
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
                        12 => 'Desember'
                    ];
                    foreach ($monthsList as $num => $name):
                    ?>
                        <option value="<?= $num; ?>" <?= ($selected['month'] ?? 0) === $num ? 'selected' : ''; ?>><?= $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="log-actions-group">
                <button type="button" class="btn-add-log js-open-log-modal">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
                
                <!-- Dropdown Export -->
                <div class="export-dropdown">
                    <button type="button" class="btn-export-trigger">
                        <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down" style="font-size:10px;"></i>
                    </button>
                    <div class="export-dropdown-menu">
                        <a href="<?= e($exportPdfUrl); ?>" class="export-dropdown-item">
                            <i class="fa-regular fa-file-pdf" style="color:#d32f2f;"></i> Export PDF
                        </a>
                        <a href="<?= e($exportXlsxUrl); ?>" class="export-dropdown-item">
                            <i class="fa-regular fa-file-excel" style="color:#2e7d32;"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Bagian 3: Tabel Data (Style DataTables) -->
    <div class="datatable-header-row">
        <div class="datatable-length">
            Tampilkan
            <select id="logPageSize">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            data
        </div>
        <div class="datatable-filter-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="logLocalSearch" placeholder="Cari data log barang..." value="<?= e((string) ($selected['search'] ?? '')); ?>">
        </div>
    </div>

    <div class="table-wrap table-wrap--log table-wrap--log-compact" style="margin-top: 0;">
        <div class="log-table-scroll">
            <table class="data-table data-table--log data-table--log-compact">
                <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 10%;">
                    <col style="width: 8%;">
                    <col style="width: 18%;">
                    <col style="width: 5%;">
                    <col style="width: 6%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 11%;">
                    <col style="width: 10%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Waktu Input</th>
                        <th>Nama Barang</th>
                        <th>Qty</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Divisi</th>
                        <th>No. PO</th>
                        <th>Divisi Terkait</th>
                        <th>PIC</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="js-log-table-body">
                <?php if (!empty($data['log_rows'])): ?>
                    <?php foreach ($data['log_rows'] as $row): ?>
                        <?php
                        $picName = $row['pic_nama'] ?: 'Admin';
                        $initial = strtoupper(substr(trim($picName), 0, 1));
                        ?>
                        <tr data-log-row data-search-text="<?= e(strtolower(implode(' ', [
                                (string) ($row['date'] ?? ''),
                                (string) ($row['created_time'] ?? ''),
                                (string) ($row['item'] ?? ''),
                                (string) ($row['qty'] ?? ''),
                                (string) ($row['satuan'] ?? ''),
                                (string) ($row['harga'] ?? '0'),
                                (string) $picName,
                                (string) ($row['no_po'] ?? ''),
                                (string) ($row['status'] ?? ''),
                                (string) ($row['division'] ?? ''),
                                (string) ($row['divisi_terkait'] ?? ''),
                                (string) ($row['keterangan'] ?? ''),
                            ]))); ?>">
                            <td><?= e((string) $row['no']); ?></td>
                            <td><span style="font-weight: 500; color: #202124;"><?= e($row['date']); ?></span></td>
                            <td><span style="font-size: 13px; color: #5f6368;"><?= e($row['created_time'] ?: '-'); ?></span></td>
                            <td><div style="font-weight: 600; color: #1a73e8;"><?= e($row['item']); ?></div></td>
                            <td><?= e((string) $row['qty']); ?></td>
                            <td><?= e((string) ($row['satuan'] ?: '-')); ?></td>
                            <td style="text-align: right; font-weight: 500;">
                                <?= e($row['harga'] > 0 ? 'Rp ' . number_format($row['harga'], 0, ',', '.') : '-'); ?>
                            </td>
                            <td><?= e($row['division'] ?? '-'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span><?= e($row['no_po'] ?: '-'); ?></span>
                                    <?php if (!empty($row['pdf'])): ?>
                                        <a class="table-link" target="_blank" href="<?= e('index.php?' . http_build_query($baseParams + ['action' => 'download_po', 'file' => $row['pdf']])); ?>" title="Download PDF PO" style="color: #d32f2f; font-size: 16px; display: inline-flex; align-items: center;"><i class="ti ti-file-type-pdf"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 11px; color: #70757a; font-weight: 500; text-transform: uppercase; margin-bottom: 2px;">
                                    <?= $row['status'] === 'KELUAR' ? 'Ke' : 'Dari'; ?>
                                </div>
                                <div style="font-weight: 500; color: #202124;"><?= e($row['divisi_terkait'] ?: '-'); ?></div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 26px; height: 26px; border-radius: 50%; background-color: #e8f0fe; color: #1a73e8; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 11px; border: 1px solid #d2e3fc; flex-shrink: 0;">
                                        <?= e($initial); ?>
                                    </div>
                                    <span style="font-size: 13px; font-weight: 500; color: #3c4043;"><?= e($picName); ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge--<?= e((string) $row['status_class']); ?>"><?= e((string) $row['status']); ?></span></td>
                            <td>
                                <?php if (AuthController::role() !== 'user'): ?>
                                <div class="table-actions" style="display: flex; gap: 8px; align-items: center;">
                                    <button type="button" class="btn-action btn-action--edit js-edit-log-btn"
                                            style="border: none; background: #e8f0fe; color: #1a73e8; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;"
                                            data-id="<?= e((string) $row['id']); ?>"
                                            data-tanggal="<?= e((string) ($row['raw_date'] ?? '')); ?>"
                                            data-nama="<?= e((string) $row['item']); ?>"
                                            data-status="<?= e((string) $row['status']); ?>"
                                            data-qty="<?= e((string) $row['qty']); ?>"
                                            data-satuan="<?= e((string) ($row['satuan'] ?? 'Unit')); ?>"
                                            data-harga="<?= e((string) ($row['harga'] ?? '')); ?>"
                                            data-pic="<?= e((string) ($row['pic_id'] ?? '')); ?>"
                                            data-no-po="<?= e((string) $row['no_po']); ?>"
                                            data-divisi="<?= e((string) $row['division']); ?>"
                                            data-divisi-terkait="<?= e((string) ($row['divisi_terkait'] ?? '')); ?>"
                                            data-pdf-name="<?= e((string) ($row['pdf_name'] ?? '')); ?>"
                                            data-keterangan="<?= e((string) ($row['keterangan'] ?? '')); ?>"
                                            title="Edit Log">
                                        <i class="fa-solid fa-pencil" style="font-size: 12px;"></i>
                                    </button>
                                    <form method="post" action="index.php?page=log-barang" class="js-confirm-delete" data-confirm-message="Hapus log barang ini?" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_log_barang">
                                        <input type="hidden" name="id" value="<?= e((string) $row['id']); ?>">
                                        <button type="submit" class="btn-action btn-action--delete"
                                                style="border: none; background: #fce8e6; color: #c5221f; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;"
                                                title="Hapus Log">
                                            <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="js-log-empty-search" hidden>
                        <td colspan="13" class="empty-state">Data yang dicari tidak ditemukan pada tabel.</td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="empty-state">Data log barang tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bagian 4: Datatable Footer -->
    <div class="datatable-footer-row">
        <div class="datatable-info" id="logTableInfo">
            Menampilkan 0 sampai 0 dari 0 data
        </div>
        <div class="datatable-pagination" id="logTablePagination">
            <!-- Paginate buttons rendered by JS -->
        </div>
    </div>
</div>

<!-- Bagian 5: Update Form Popup "Tambah Barang" -->
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
                <label><span>Tanggal <span style="color:red;">*</span></span><input type="date" name="tanggal" id="logTanggal" required></label>
                
                <label><span>Status <span style="color:red;">*</span></span>
                    <select name="status" id="logStatus" required>
                        <option value="MASUK">Barang Masuk</option>
                        <option value="KELUAR">Barang Keluar</option>
                    </select>
                </label>
                
                <label><span>Nama Barang <span style="color:red;">*</span></span><input type="text" name="nama_barang" id="logNamaBarang" placeholder="Masukkan nama barang" required></label>
                
                <label><span>Qty <span style="color:red;">*</span></span><input type="number" min="1" name="qty" id="logQty" value="1" required></label>
                
                <!-- 3 FIELD BARU -->
                <label><span>Satuan <span style="color:red;">*</span></span>
                    <input type="text" name="satuan" id="logSatuan" placeholder="Contoh: Unit, Pcs, Box, Rim" required>
                </label>

                <label><span>Harga Satuan (Rupiah) <span class="req-star" style="color:red;">*</span></span>
                    <input type="number" min="0" name="harga" id="logHarga" placeholder="Contoh: 150000" required>
                </label>

                <label><span>PIC <span style="color:red;">*</span></span>
                    <select name="pic_id" id="logPic" required data-default-user-id="<?= e($_SESSION['auth']['id'] ?? $_SESSION['auth']['user_id'] ?? 1); ?>">
                        <option value="">-- Pilih PIC --</option>
                        <?php foreach (($data['users_options'] ?? []) as $usr): ?>
                            <option value="<?= e((string) $usr['id']); ?>">
                                <?= e((string) ($usr['nama_lengkap'] ?: $usr['username'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label><span>No. PO</span><input type="text" name="no_po" id="logNoPo" placeholder="Masukkan No. PO"></label>
                
                <label><span>File PDF</span>
                    <input type="file" name="surat_pemesanan_pdf" id="logPdf" accept=".pdf">
                    <small id="logPdfHint" style="color: #70757a; font-size:11px; margin-top:2px;">Upload PDF jika ada.</small>
                </label>

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

                <label><span id="logDivisiTerkaitLabel">Dari Divisi</span>
                    <select name="divisi_terkait" id="logDivisiTerkait">
                        <option value="">-- Pilih Divisi Terkait --</option>
                        <?php foreach (($data['log_division_options'] ?? []) as $opt): ?>
                            <option value="<?= e((string) ($opt['division_label'] ?? '')); ?>">
                                <?= e((string) ($opt['division_label'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label class="log-modal__field--full"><span>Keterangan</span><textarea name="keterangan" id="logKeterangan" placeholder="Masukkan keterangan" rows="2"></textarea></label>
            </div>
            <div class="log-modal__actions">
                <button type="button" class="btn btn--ghost js-close-log-modal">Batal</button>
                <button type="submit" class="btn btn--primary log-modal__save-btn">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Client-side Datatable Logic Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tableBody = document.querySelector('.js-log-table-body');
    if (!tableBody) return;
    var originalRows = Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-log-row]'));
    var emptyRow = tableBody.querySelector('.js-log-empty-search');
    
    var searchInput = document.getElementById('logLocalSearch');
    var pageSizeSelect = document.getElementById('logPageSize');
    var infoEl = document.getElementById('logTableInfo');
    var paginationEl = document.getElementById('logTablePagination');
    
    var currentPage = 1;
    var filteredRows = originalRows;
    
    function renderTable() {
        var pageSize = parseInt(pageSizeSelect.value) || 10;
        var totalRows = filteredRows.length;
        var totalPages = Math.ceil(totalRows / pageSize) || 1;
        
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        
        var startIdx = (currentPage - 1) * pageSize;
        var endIdx = startIdx + pageSize;
        if (endIdx > totalRows) {
            endIdx = totalRows;
        }
        
        originalRows.forEach(function(row) {
            row.style.display = 'none';
        });
        
        if (totalRows === 0) {
            if (emptyRow) emptyRow.hidden = false;
            if (infoEl) infoEl.textContent = 'Menampilkan 0 sampai 0 dari 0 data';
            renderPagination(1, 1);
            return;
        }
        
        if (emptyRow) emptyRow.hidden = true;
        
        for (var i = startIdx; i < endIdx; i++) {
            filteredRows[i].style.display = '';
        }
        
        if (infoEl) {
            infoEl.textContent = 'Menampilkan ' + (startIdx + 1) + ' sampai ' + endIdx + ' dari ' + totalRows + ' data';
        }
        
        renderPagination(currentPage, totalPages);
    }
    
    function renderPagination(current, total) {
        if (!paginationEl) return;
        paginationEl.innerHTML = '';
        
        // Prev button
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'btn-paginate';
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.disabled = current === 1;
        prevBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });
        paginationEl.appendChild(prevBtn);
        
        // Page numbers
        // Render up to 5 page numbers around the current page
        var startPage = Math.max(1, current - 2);
        var endPage = Math.min(total, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var i = startPage; i <= endPage; i++) {
            (function(pageNum) {
                var pageBtn = document.createElement('button');
                pageBtn.type = 'button';
                pageBtn.className = 'btn-paginate' + (pageNum === current ? ' is-active' : '');
                pageBtn.textContent = pageNum;
                pageBtn.addEventListener('click', function() {
                    currentPage = pageNum;
                    renderTable();
                });
                paginationEl.appendChild(pageBtn);
            })(i);
        }
        
        // Next button
        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'btn-paginate';
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.disabled = current === total;
        nextBtn.addEventListener('click', function() {
            if (currentPage < total) {
                currentPage++;
                renderTable();
            }
        });
        paginationEl.appendChild(nextBtn);
    }
    
    function filterTable() {
        var query = (searchInput.value || '').toLowerCase().replace(/\s+/g, ' ').trim();
        var tokens = query === '' ? [] : query.split(' ');
        
        filteredRows = originalRows.filter(function(row) {
            var haystack = (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase().replace(/\s+/g, ' ').trim();
            return tokens.every(function(token) {
                return haystack.indexOf(token) !== -1;
            });
        });
        
        currentPage = 1;
        renderTable();
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            currentPage = 1;
            renderTable();
        });
    }
    
    // Unified Export dropdown click handler
    var exportDropdown = document.querySelector('.export-dropdown');
    var exportTrigger = document.querySelector('.btn-export-trigger');
    if (exportDropdown && exportTrigger) {
        exportTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            exportDropdown.classList.toggle('is-open');
        });
        document.addEventListener('click', function(e) {
            if (!exportDropdown.contains(e.target)) {
                exportDropdown.classList.remove('is-open');
            }
        });
    }
    
    // Trigger initial render
    filterTable();
});
</script>
