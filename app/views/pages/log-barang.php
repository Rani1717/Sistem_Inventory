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
        background: rgba(255, 255, 255, 0.92);
        border-radius: 28px;
        padding: 18px 20px;
        box-shadow: 0 16px 38px rgba(13, 51, 108, 0.12);
        border: 1px solid rgba(42, 102, 165, 0.10);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 48px rgba(13, 51, 108, 0.18);
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

    /* Override table header colors to match Routine Monitoring */
    .data-table--log th,
    .data-table--log-compact th {
        background-color: #2d69b2 !important;
    }

    /* Computed selesai badge color styling */
    .badge--selesai {
        background: #e8f0fe !important;
        color: #1a73e8 !important;
        border: 1px solid #d2e3fc !important;
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
        background-color: #244A84;
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
        background-color: #1b3864;
    }

    /* Custom Responsive Grid Layout for Log Barang Filters (unifying with Routine Monitoring) */
    .log-filter-card--custom {
        grid-template-columns: minmax(200px, 1.2fr) minmax(150px, 1fr) minmax(130px, 1fr) minmax(100px, 0.8fr) minmax(200px, 1.8fr) !important;
        align-items: end !important;
        gap: 12px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        margin-bottom: 24px !important;
    }
    
    @media (max-width: 1350px) {
        .log-filter-card--custom {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .log-filter-card--custom .routine-filter-search {
            grid-column: span 2 !important;
        }
    }
    
    @media (max-width: 860px) {
        .log-filter-card--custom {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .log-filter-card--custom label,
        .log-filter-card--custom .routine-filter-search {
            grid-column: span 1 !important;
            width: 100% !important;
        }
        .log-filter-card--custom .routine-filter-search {
            grid-column: 1 / -1 !important;
        }
    }
    
    @media (max-width: 480px) {
        .log-filter-card--custom {
            grid-template-columns: 1fr !important;
        }
        .log-filter-card--custom label,
        .log-filter-card--custom .routine-filter-search {
            grid-column: 1 / -1 !important;
        }
    }

    /* Button secondary style override for Reset button */
    .btn--secondary {
        background: #fff !important;
        color: #334155 !important;
        border: 1.5px solid #cbd5e1 !important;
    }
    .btn--secondary:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
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
        background-color: #15803d;
        color: #ffffff;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.2s;
    }
    .btn-export-trigger:hover {
        background-color: #166534;
        color: #ffffff;
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
    <h1>LOG MUTASI ASET</h1>
</div>

<div class="log-layout" style="display: block;">
    <!-- Bagian 1: Summary Cards -->
    <div class="log-summary-cards">
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--blue">
                <i class="fa-solid fa-box"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Transaksi</span>
                <span class="summary-card__value"><?= e($summary['total_transaksi']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--green">
                <i class="fa-solid fa-circle-arrow-down"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Barang Masuk</span>
                <span class="summary-card__value"><?= e($summary['barang_masuk_qty']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--red">
                <i class="fa-solid fa-circle-arrow-up"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Barang Mutasi</span>
                <span class="summary-card__value"><?= e($summary['keluar_count']); ?></span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-card__icon summary-card__icon--orange">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="summary-card__info">
                <span class="summary-card__label">Total Nilai Masuk</span>
                <span class="summary-card__value">Rp <?= number_format($summary['total_nilai_masuk'], 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Bagian 2: Baris Filter -->
    <form class="routine-filter-card log-filter-card--custom" method="get" action="index.php">
        <input type="hidden" name="page" value="log-barang">
        
        <label>
            <span>STATUS</span>
            <select name="log_status" style="width: 100%;">
                <option value="">Semua Status</option>
                <option value="MASUK" <?= ($selected['status'] ?? '') === 'MASUK' ? 'selected' : ''; ?>>Masuk (Barang Masuk)</option>
                <option value="SELESAI" <?= ($selected['status'] ?? '') === 'SELESAI' ? 'selected' : ''; ?>>Selesai (Barang Mutasi)</option>
            </select>
        </label>

        <label>
            <span>DIVISI</span>
            <select name="log_division">
                <option value="">Semua Divisi</option>
                <?php foreach (($data['log_distinct_divisions'] ?? []) as $div): ?>
                    <option value="<?= e($div); ?>" <?= ($selected['division'] ?? '') === $div ? 'selected' : ''; ?>><?= e($div); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>BULAN</span>
            <select name="log_month">
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
        </label>

        <label>
            <span>TAHUN</span>
            <select name="log_year">
                <option value="0" <?= (int)($selected['year'] ?? 0) === 0 ? 'selected' : ''; ?>>Semua Tahun</option>
                <?php foreach (($logFilters['years'] ?? [date('Y')]) as $yr): ?>
                    <option value="<?= $yr; ?>" <?= (int)($selected['year'] ?? 0) === (int)$yr ? 'selected' : ''; ?>><?= $yr; ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="routine-filter-search">
            <span>SEARCH</span>
            <input type="text" id="logLocalSearch" name="log_search" placeholder="Cari data log mutasi aset..." value="<?= e((string) ($selected['search'] ?? '')); ?>">
        </label>
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

    <div class="table-wrap table-wrap--log table-wrap--log-compact" style="margin-top: 0;">
        <div class="log-table-scroll">
            <table class="data-table data-table--log data-table--log-compact">
                <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 10%;">
                    <col style="width: 6%;">
                    <col style="width: 12%;">
                    <col style="width: 7%;">
                    <col style="width: 8%;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="white-space: nowrap;">No.</th>
                        <th style="white-space: nowrap;">Tanggal Masuk</th>
                        <th style="white-space: nowrap;">Tanggal Keluar</th>
                        <th style="white-space: nowrap;">Divisi Pengelola</th>
                        <th style="white-space: nowrap;">Nomor SPB/PO</th>
                        <th style="white-space: nowrap;">Divisi Peminta</th>
                        <th style="white-space: nowrap;">PIC</th>
                        <th style="white-space: nowrap;">Total Qty</th>
                        <th style="white-space: nowrap;">Harga Total</th>
                        <th style="white-space: nowrap;">Status</th>
                        <th style="white-space: nowrap;">Aksi</th>
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
                                (string) ($row['date_keluar'] ?? ''),
                                (string) ($row['created_time'] ?? ''),
                                (string) ($row['waktu_input_keluar'] ?? ''),
                                (string) ($row['item'] ?? ''),
                                (string) ($row['qty'] ?? ''),
                                (string) ($row['satuan'] ?? ''),
                                (string) ($row['harga'] ?? '0'),
                                (string) ($row['total_harga'] ?? '0'),
                                (string) $picName,
                                (string) ($row['no_po'] ?? ''),
                                (string) ($row['status'] ?? ''),
                                (string) ($row['division'] ?? ''),
                                (string) ($row['divisi_terkait'] ?? ''),
                                (string) ($row['keterangan'] ?? ''),
                            ]))); ?>">
                            <td><?= e((string) $row['no']); ?></td>
                            <td>
                                <span style="font-weight: 500; color: #202124;"><?= e($row['date']); ?></span><br>
                                <span style="font-size: 11px; color: #70757a;"><?= e($row['created_time'] ?: '-'); ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 500; color: #202124;"><?= e($row['date_keluar']); ?></span>
                                <?php if ($row['status'] === 'SELESAI' && $row['waktu_input_keluar'] !== '-'): ?>
                                    <br><span style="font-size: 11px; color: #70757a;"><?= e($row['waktu_input_keluar']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['division'] ?? '-'); ?></td>
                            <td>
                                <a href="#" class="js-open-po-detail" data-id="<?= e($row['id']); ?>" style="font-weight: 600; color: #1a73e8; text-decoration: none; cursor: pointer;" title="Lihat detail PO"><?= e($row['no_po'] ?: '-'); ?></a>
                            </td>
                            <td style="max-width: 120px;">
                                <span style="font-weight: 500; color: #202124; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($row['divisi_terkait'] ?: '-'); ?>">
                                    <?= e($row['divisi_terkait'] ?: '-'); ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px; font-weight: 500; color: #3c4043;"><?= e($picName); ?></span>
                            </td>
                            <td style="text-align: center; font-weight: 500; color: #202124;">
                                <?= number_format($row['total_qty'] ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td style="text-align: right; font-weight: 500; color: #202124;">
                                Rp <?= number_format($row['total_harga'] ?? 0.00, 0, ',', '.'); ?>
                            </td>
                            <td><span class="badge badge--<?= e((string) $row['status_class']); ?>"><?= e((string) $row['status']); ?></span></td>
                            <td>
                                <?php if (AuthController::role() !== 'user'): ?>
                                <div class="table-actions" style="display: flex; gap: 8px; align-items: center; flex-direction: row; justify-content: center; padding: 0 6px;">
                                    <button type="button" class="btn-action btn-action--edit js-edit-log-btn"
                                            style="border: none; background: #e8f0fe; color: #1a73e8; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;"
                                            data-id="<?= e((string) $row['id']); ?>"
                                            data-tanggal-masuk="<?= e((string) ($row['raw_date'] ?? '')); ?>"
                                            data-tanggal-keluar="<?= e((string) ($row['raw_date_keluar'] ?? '')); ?>"

                                            data-pic="<?= e((string) $picName); ?>"
                                            data-no-po="<?= e((string) $row['no_po']); ?>"
                                            data-divisi="<?= e((string) $row['division']); ?>"
                                            data-divisi-terkait="<?= e((string) ($row['divisi_terkait'] ?? '')); ?>"
                                            data-pdf-name="<?= e((string) ($row['pdf_name'] ?? '')); ?>"
                                            data-keterangan="<?= e((string) ($row['keterangan'] ?? '')); ?>"
                                            title="Edit Log">
                                        <i class="fa-solid fa-pencil" style="font-size: 12px;"></i>
                                    </button>
                                    <?php if (!empty($row['pdf'])): ?>
                                        <a href="index.php?page=log-barang&action=download_po&file=<?= urlencode($row['pdf']); ?>"
                                           target="_blank"
                                           class="btn-action"
                                           style="border: none; background: #fce8e6; color: #c5221f; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;"
                                           title="Lihat PDF">
                                            <i class="fa-solid fa-file-pdf" style="font-size: 12px;"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" action="index.php?page=log-barang" class="js-confirm-delete" data-confirm-message="Hapus log mutasi aset ini?" style="margin: 0;">
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
                                    <div class="table-actions" style="display: flex; gap: 8px; align-items: center; flex-direction: row; justify-content: center; padding: 0 6px;">
                                        <a href="#" class="js-open-po-detail btn-action"
                                           data-id="<?= e((string) $row['id']); ?>"
                                           style="border: none; background: #e8f0fe; color: #1a73e8; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;"
                                           title="Lihat Dokumen PO">
                                            <i class="fa-solid fa-eye" style="font-size: 12px;"></i>
                                        </a>
                                        <?php if (!empty($row['pdf'])): ?>
                                            <a href="index.php?page=log-barang&action=download_po&file=<?= urlencode($row['pdf']); ?>"
                                               target="_blank"
                                               class="btn-action"
                                               style="border: none; background: #fce8e6; color: #c5221f; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;"
                                               title="Lihat PDF">
                                                <i class="fa-solid fa-file-pdf" style="font-size: 12px;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="js-log-empty-search" hidden>
                        <td colspan="10" class="empty-state">Data yang dicari tidak ditemukan pada tabel.</td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty-state">Data log mutasi aset tidak ditemukan.</td>
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
    <div class="log-modal__dialog" style="max-width: 1200px !important; width: 95% !important;">
        <div class="log-modal__header">
            <h3 id="logModalTitle">Tambah Log Mutasi Aset</h3>
            <button type="button" class="icon-round js-close-log-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" action="index.php?page=log-barang" enctype="multipart/form-data" class="log-modal__form">
            <input type="hidden" name="action" value="save_log_barang" id="logFormAction">
            <input type="hidden" name="id" value="" id="logId">
            <div class="log-modal__grid">
                <label><span>Tanggal Masuk <span style="color:red;">*</span></span><input type="date" name="tanggal_masuk" id="logTanggalMasuk" required></label>
                
                <label id="logTanggalKeluarField"><span>Tanggal Keluar</span><input type="date" name="tanggal_keluar" id="logTanggalKeluar"></label>
                


                <label><span>PIC <span style="color:red;">*</span></span>
                    <input type="text" name="pic" id="logPic" placeholder="Masukkan nama PIC" required data-default-user-name="<?= e($_SESSION['auth']['nama_lengkap'] ?? $_SESSION['auth']['username'] ?? ''); ?>">
                </label>
                
                <label><span>Nomor SPB/PO</span><input type="text" name="no_po" id="logNoPo" placeholder="Masukkan Nomor SPB/PO"></label>
                
                <label><span>File PDF</span>
                    <input type="file" name="surat_pemesanan_pdf" id="logPdf" accept=".pdf">
                    <small id="logPdfHint" style="color: #70757a; font-size:11px; margin-top:2px;">Upload PDF jika ada.</small>
                </label>

                <label><span>Divisi Pengelola</span>
                    <input type="text" name="divisi" id="logDivisi" value="Teknologi Informasi" placeholder="Masukkan divisi pengelola">
                </label>

                <label><span>Divisi Peminta</span>
                    <input type="text" name="divisi_terkait" id="logDivisiTerkait" placeholder="Masukkan divisi peminta">
                </label>
                
                <label class="log-modal__field--full"><span>Keterangan Utama</span><textarea name="keterangan" id="logKeterangan" placeholder="Masukkan keterangan" rows="2"></textarea></label>

                <!-- Data Vendor (Penyedia Barang/Jasa) -->
                <div style="grid-column: 1 / -1; margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0;">Data Penyedia (Vendor) & PPN</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <label><span>Nama Vendor</span><input type="text" name="nama_vendor" id="logNamaVendor" placeholder="Masukkan nama vendor"></label>
                        <label><span>Telepon Vendor</span><input type="text" name="telepon_vendor" id="logTeleponVendor" placeholder="Masukkan nomor telepon vendor"></label>
                        <label><span>PPN (Rupiah)</span><input type="text" name="ppn_nominal" id="logPpnNominal" class="js-number-format" placeholder="Contoh: 50.000" value="0"></label>
                    </div>
                </div>





                <!-- Rincian Item PO (Banyak Item) -->
                <div style="grid-column: 1 / -1; margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0;">Rincian Item dalam PO</h4>
                        <button type="button" class="btn btn-add-log js-add-item-row" style="background-color: #10b981; padding: 8px 16px; font-size: 13px;">
                            <i class="fa-solid fa-plus"></i> Tambah Item
                        </button>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; min-width: 800px; font-size: 11px;" id="formItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Nomor</th>
                                    <th style="width: 30%;">Deskripsi *</th>
                                    <th style="width: 10%;">Kuantitas *</th>
                                    <th style="width: 12%;">Satuan *</th>
                                    <th style="width: 15%;">Harga Satuan *</th>
                                    <th style="width: 15%;">Harga Total</th>
                                    <th style="width: 8%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="formItemsTbody">
                                <!-- Dynamic rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="log-modal__actions">
                <button type="button" class="btn btn--ghost js-close-log-modal">Batal</button>
                <button type="submit" class="btn btn--primary log-modal__save-btn">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Popup dialog for "Serahkan Barang" -->
<div class="log-modal" id="deliverBarangModal" hidden aria-hidden="true">
    <div class="log-modal__dialog">
        <div class="log-modal__header">
            <h3>Serahkan Barang</h3>
            <button type="button" class="icon-round js-close-deliver-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" action="index.php?page=log-barang" class="log-modal__form">
            <input type="hidden" name="action" value="complete_transfer">
            <input type="hidden" name="id" id="deliverLogId">
            <div class="log-modal__grid" style="display: flex; flex-direction: column; gap: 16px;">
                <p style="font-size: 14px; color: #3c4043; margin: 0;">
                    Konfirmasi penyerahan barang <strong id="deliverItemName" style="color: #1a73e8;"></strong> ke Divisi Peminta.
                </p>
                <label style="width: 100%;">
                    <span>Tanggal Keluar <span style="color:red;">*</span></span>
                    <input type="date" name="tanggal_keluar" id="deliverTanggalKeluar" required>
                </label>
            </div>
            <div class="log-modal__actions" style="margin-top: 24px;">
                <button type="button" class="btn btn--ghost js-close-deliver-modal">Batal</button>
                <button type="submit" class="btn btn--primary">Konfirmasi Penyerahan</button>
            </div>
        </form>
</div>

<!-- Modal Popup dialog for "Detail PO" -->
<div class="log-modal" id="poDetailModal" hidden aria-hidden="true">
    <div class="log-modal__dialog" style="max-width: 1200px !important; width: 95% !important;">
        <div class="log-modal__header">
            <h3>Detail Surat Pemesanan Barang/Jasa (SPB/PO)</h3>
            <button type="button" class="icon-round js-close-po-detail-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="log-modal__form" style="padding: 24px; max-height: 80vh; overflow-y: auto;">
            <!-- Document Header Layout: Pemberi Tugas vs Penyedia -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px;">
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #1e3a8a; font-size: 14px; font-weight: 700; text-transform: uppercase;">Pemberi Tugas</h4>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #334155;">PELINDO MULTI TERMINAL</p>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #475569; line-height: 1.4;" id="detailPemberiTugasDivisi">-</p>
                </div>
                <div style="border-left: 1px solid #cbd5e1; padding-left: 24px;">
                    <h4 style="margin: 0 0 8px 0; color: #1e3a8a; font-size: 14px; font-weight: 700; text-transform: uppercase;">Penyedia Barang/Jasa (Vendor)</h4>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #334155;" id="detailVendorNama">-</p>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #475569;" id="detailVendorTelepon">-</p>

                </div>
            </div>

            <!-- PO Document Metadata -->
            <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px; background-color: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Nomor SPB/PO</span>
                    <strong style="font-size: 13px; color: #1e293b;" id="detailNoPo">-</strong>
                </div>
            </div>

            <!-- Items Table -->
            <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: #1e293b;">Rincian Item Pekerjaan</h4>
            <div style="overflow-x: auto; margin-bottom: 24px;">
                <table class="data-table" style="width: 100%; min-width: 800px; font-size: 11px;">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Nomor</th>
                            <th style="width: 40%;">Deskripsi</th>
                            <th style="width: 10%;">Kuantitas</th>
                            <th style="width: 10%;">Satuan</th>
                            <th style="width: 15%;">Harga Satuan</th>
                            <th style="width: 15%;">Harga Total</th>
                        </tr>
                    </thead>
                    <tbody id="detailPoItemsBody">
                        <!-- Dynamic items -->
                    </tbody>
                </table>
            </div>

            <!-- Financial summary & document download -->
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
                <div>
                    <div id="detailPoAttachmentContainer" style="display: none;">
                        <h4 style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #1e293b;">Dokumen Lampiran</h4>
                        <a href="#" id="detailPoAttachmentLink" target="_blank" class="btn btn-export-trigger" style="background-color: #d32f2f; padding: 6px 12px; font-size: 12px; border-radius: 6px;">
                            <i class="fa-regular fa-file-pdf"></i> Lihat Dokumen PDF PO
                        </a>
                    </div>
                </div>
                <div>
                    <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                        <tr style="border-top: 1.5px solid #cbd5e1;">
                            <td style="padding: 6px 0; color: #475569; font-weight: 600;">Subtotal</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #1e293b;" id="detailPoSubtotal">Rp 0</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #475569; font-weight: 600;" id="detailPoPpnLabel">PPN (0%)</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #1e293b;" id="detailPoPpnAmount">Rp 0</td>
                        </tr>
                        <tr style="border-top: 1.5px solid #cbd5e1;">
                            <td style="padding: 10px 0 0 0; color: #1e3a8a; font-weight: 700; font-size: 14px;">TOTAL</td>
                            <td style="padding: 10px 0 0 0; text-align: right; font-weight: 800; font-size: 15px; color: #1e3a8a;" id="detailPoTotal">Rp 0</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="log-modal__actions" style="border-top: 1px solid #e2e8f0; padding: 16px 24px;">
            <button type="button" class="btn btn--secondary js-close-po-detail-modal">Tutup</button>
        </div>
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
        
        // Auto-submit search query after debounce
        var debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                var form = searchInput.closest('form');
                if (form) {
                    form.submit();
                }
            }, 400); // 400ms debounce
        });
        
        // Preserve focus and position cursor at the end on page load
        if (searchInput.value !== '') {
            searchInput.focus();
            var val = searchInput.value;
            searchInput.value = '';
            searchInput.value = val;
        }
    }
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            currentPage = 1;
            renderTable();
        });
    }
    
    // Auto-submit filter form on select change
    var filterForm = document.querySelector('.log-filter-card--custom');
    if (filterForm) {
        var selects = filterForm.querySelectorAll('select');
        selects.forEach(function(select) {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
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
