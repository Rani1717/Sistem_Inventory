<?php
$complaintFilters = $data['complaint_filters'] ?? [];
$itHandlerOptions = $data['it_handler_options'] ?? [];
$complaintBaseParams = [
    'page' => 'data-keluhan',
    'complaint_search' => (string) ($complaintFilters['search'] ?? ''),
    'complaint_status' => (string) ($complaintFilters['status'] ?? ''),
    'complaint_division' => (string) ($complaintFilters['division'] ?? ''),
    'complaint_date_from' => (string) ($complaintFilters['date_from'] ?? ''),
    'complaint_date_to' => (string) ($complaintFilters['date_to'] ?? ''),
];
$complaintExportPdfUrl = 'index.php?' . http_build_query($complaintBaseParams + ['action' => 'export', 'format' => 'pdf']);
$complaintExportXlsxUrl = 'index.php?' . http_build_query($complaintBaseParams + ['action' => 'export', 'format' => 'xlsx']);
$complaintRowCount = (int) ($complaintFilters['total'] ?? count($data['complaint_rows'] ?? []));
$complaintEmailMaxLength = 0;
foreach (($data['complaint_rows'] ?? []) as $emailWidthRow) {
    $emailForWidth = (string) ($emailWidthRow['email_plain'] ?? str_replace(["\r", "\n"], "", (string) ($emailWidthRow['email'] ?? "")));
    $complaintEmailMaxLength = max($complaintEmailMaxLength, function_exists('mb_strlen') ? mb_strlen($emailForWidth, 'UTF-8') : strlen($emailForWidth));
}
$complaintEmailColumnWidth = max(160, min(260, ($complaintEmailMaxLength * 7) + 28));
?>
<style>
/* Spacing out the toolbar row and the data table below it */
.complaint-page .detail-header--report {
    margin-bottom: 24px !important;
}

/* Spacing out the title and the toolbar row below it */
.complaint-page .detail-header--report .detail-header__row {
    margin-top: 24px !important;
}
</style>
<div class="complaint-page">
    <div class="detail-header detail-header--report">
        <h1>LAPORAN IT SUPPORT REQUEST ISSUE</h1>
        <div class="detail-header__row detail-header__row--search complaint-toolbar">
            <div class="complaint-toolbar__meta">
                <span class="detail-header__updated complaint-toolbar__updated">LAST UPDATED : <span class="js-live-date">-</span> <span class="js-live-time">-</span></span>
            </div>
            <div class="report-tools report-tools--complaint complaint-toolbar__actions" style="gap: 14px;">
                <form method="get" action="index.php" class="mini-search mini-search--input mini-search--complaint">
                    <input type="hidden" name="page" value="data-keluhan">
                    <input type="hidden" name="complaint_status" value="<?= e((string) ($complaintFilters['status'] ?? '')); ?>">
                    <input type="hidden" name="complaint_division" value="<?= e((string) ($complaintFilters['division'] ?? '')); ?>">
                    <input type="hidden" name="complaint_date_from" value="<?= e((string) ($complaintFilters['date_from'] ?? '')); ?>">
                    <input type="hidden" name="complaint_date_to" value="<?= e((string) ($complaintFilters['date_to'] ?? '')); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="complaint_search" value="<?= e((string) ($complaintFilters['search'] ?? '')); ?>" placeholder="Search live tiket, email, nama, divisi, aset, lokasi..." class="js-complaint-live-search" autocomplete="off">
                </form>
                <button type="button" class="icon-round js-toggle-complaint-filters" title="Filter" aria-label="Filter tiket" style="border-radius: 12px;"><i class="fa-solid fa-filter"></i></button>
                <div class="export-dropdown js-export-dropdown">
                    <button type="button" class="btn btn--primary detail-action export-dropdown__toggle js-toggle-export-menu" aria-expanded="false" style="border-radius: 12px; gap: 6px;">EXPORT <i class="fa-solid fa-chevron-down"></i></button>
                    <div class="export-dropdown__menu" hidden>
                        <a href="<?= e($complaintExportPdfUrl); ?>" class="export-dropdown__item">Export PDF</a>
                        <a href="<?= e($complaintExportXlsxUrl); ?>" class="export-dropdown__item">Export Excel</a>
                    </div>
                </div>
                <!-- Google Form Integration -->
                <div style="display: inline-flex; gap: 12px; align-items: center;">
                    <form method="post" action="index.php?page=data-keluhan" style="display: inline-block; margin: 0;">
                        <input type="hidden" name="action" value="sync_google_form">
                        <button type="submit" class="btn btn--success detail-action js-sync-gform-btn" title="Sinkronisasi Google Form" style="padding: 0 16px; height: 38px; font-size: 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-sync"></i> SYNC GFORM</button>
                    </form>
                    <button type="button" class="icon-round js-open-gform-settings" title="Pengaturan Google Form" aria-label="Pengaturan Google Form" style="border: 1.5px solid var(--c-line); color: var(--c-primary); border-radius: 12px;"><i class="fa-solid fa-cog"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-wrap table-wrap--complaints">
        <div class="complaint-table-scroll">
            <table class="data-table data-table--complaints" id="complaintTable">
                <thead>
                <tr>
                    <th>No. Tiket</th>
                    <th>Tanggal &amp; Jam</th>
                    <th>Pelapor</th>
                    <th>Divisi</th>
                    <th>Aset</th>
                    <th>Lokasi</th>
                    <th>Issue</th>
                    <th>Dokumentasi</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody class="js-complaint-table-body">
                <?php if (!empty($data['complaint_rows'])): ?>
                    <?php foreach ($data['complaint_rows'] as $row): ?>
                        <?php
                        $handlingEmailStatus = trim((string) ($row['handling_email_status'] ?? ''));
                        if ($handlingEmailStatus === 'QUEUED_LOG') {
                            $emailStatusFriendly = 'Email belum terkirim — server sedang tidak aktif.';
                        } elseif ($handlingEmailStatus === 'SENT') {
                            $emailStatusFriendly = 'Email terkirim';
                        } elseif ($handlingEmailStatus === 'FAILED') {
                            $emailStatusFriendly = 'Gagal dikirim - ' . ($row['handling_email_message'] ?? '');
                        } elseif ($handlingEmailStatus !== '') {
                            $emailStatusFriendly = $handlingEmailStatus . ' - ' . ($row['handling_email_message'] ?? '');
                        } else {
                            $emailStatusFriendly = 'Belum dikirim';
                        }

                        $detailPayload = [
                            'id' => (int) ($row['id'] ?? 0),
                            'ticket_no' => (string) ($row['ticket_no'] ?? '-'),
                            'datetime' => (string) ($row['datetime_plain'] ?? str_replace("\n", ' ', (string) ($row['datetime'] ?? '-'))),
                            'email' => (string) ($row['email_plain'] ?? str_replace("\n", '', (string) ($row['email'] ?? '-'))),
                            'name' => (string) ($row['name'] ?? '-'),
                            'division' => (string) ($row['division'] ?? '-'),
                            'item' => (string) ($row['item'] ?? '-'),
                            'location' => (string) ($row['location'] ?? '-'),
                            'description' => (string) ($row['description'] ?? '-'),
                            'status' => (string) ($row['status'] ?? '-'),
                            'notes' => (string) ($row['catatan_penanganan'] ?? ''),
                            'handled_by' => (string) ($row['handled_by_name'] ?? ''),
                            'handled_by_user_id' => (int) ($row['handled_by_user_id'] ?? 0),
                            'email_status' => $emailStatusFriendly,
                            'doc_image' => !empty($row['doc_image']) ? asset((string) $row['doc_image']) : '',
                            'history' => $data['complaint_history_map'][(int) ($row['id'] ?? 0)] ?? [],
                        ];
                        $detailJson = htmlspecialchars((string) json_encode($detailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        $rowSearch = strtolower(trim(implode(' ', [
                            (string) ($row['ticket_no'] ?? ''),
                            (string) ($row['email_plain'] ?? ''),
                            (string) ($row['name'] ?? ''),
                            (string) ($row['division'] ?? ''),
                            (string) ($row['item'] ?? ''),
                            (string) ($row['location'] ?? ''),
                            (string) ($row['description'] ?? ''),
                            (string) ($row['catatan_penanganan'] ?? ''),
                            (string) ($row['handled_by_name'] ?? ''),
                        ])));
                        ?>
                        <tr class="js-complaint-row" data-ticket-id="<?= (int) ($row['id'] ?? 0); ?>" data-search="<?= e($rowSearch); ?>" data-status="<?= e(strtoupper((string) ($row['status'] ?? ''))); ?>" data-division="<?= e(strtolower((string) ($row['division'] ?? ''))); ?>" data-date="<?= e((string) ($row['date_value'] ?? '')); ?>">
                            <td><div class="complaint-ticket"><?= e($row['ticket_no'] ?? '-'); ?></div></td>
                            <td><div class="complaint-datetime-cell"><span class="complaint-date-cell"><?= e((string) ($row['date_value'] ?? '-')); ?></span><span class="complaint-time-cell"><?= e(substr((string) ($row['time_value'] ?? '-'), 0, 8)); ?></span></div></td>
                            <td><?= nl2br(e($row['name'])); ?></td>
                            <td><?= e($row['division']); ?></td>
                            <td><?= nl2br(e($row['item'])); ?></td>
                            <td><?= nl2br(e($row['location'])); ?></td>
                            <td><div class="complaint-description-cell"><?= nl2br(e($row['description'])); ?></div></td>
                            <td>
                                <?php if (!empty($row['doc_image'])): ?>
                                    <?php $docImageUrl = asset((string) $row['doc_image']); ?>
                                    <?php if (preg_match('#^https?://#i', $docImageUrl)): ?>
                                        <a href="<?= e($docImageUrl); ?>" target="_blank" class="btn btn--ghost btn--xs" title="Buka Link Dokumentasi" style="display:inline-flex; align-items:center; gap:4px; padding:4px 8px; font-size:11px; white-space:nowrap; border: 1.5px solid var(--c-line);"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link</a>
                                    <?php else: ?>
                                        <button type="button" class="doc-thumb doc-thumb--image js-open-complaint-image" data-image-src="<?= e($docImageUrl); ?>" data-image-title="<?= e($row['ticket_no'] ?? 'Dokumentasi'); ?>" aria-label="Lihat dokumentasi <?= e($row['ticket_no'] ?? ''); ?>"><img src="<?= e($docImageUrl); ?>" alt="Dokumentasi <?= e(preg_replace('/\s+/', ' ', (string) ($row['name'] ?? ''))); ?>" loading="lazy"></button>
                                    <?php endif; ?>
                                <?php else: ?><div class="doc-thumb doc-thumb--empty">Tidak ada</div><?php endif; ?>
                            </td>
                            <td>
                                <div class="complaint-status-cell-wrap">
                                    <?php $rowStatus = strtoupper(trim((string) ($row['status'] ?? ''))); ?>
                                    <span class="badge badge--<?= e($row['status_class']); ?><?= $rowStatus === 'ON PROGRESS' ? ' badge--on-progress' : ''; ?>"><?= e($row['status']); ?></span>
                                    <button type="button" class="btn btn--ghost btn--xs js-open-complaint-detail" data-complaint='<?= $detailJson; ?>'><i class="fa-solid fa-screwdriver-wrench"></i> Tindak Lanjut</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="js-complaint-empty-row"<?= !empty($data['complaint_rows']) ? ' hidden' : ''; ?>><td colspan="10"><div class="table-empty-state"><?= !empty($complaintFilters['search']) || !empty($complaintFilters['status']) || !empty($complaintFilters['division']) || !empty($complaintFilters['date_from']) || !empty($complaintFilters['date_to']) ? 'Belum ada tiket yang cocok dengan filter.' : 'Belum ada data tiket IT support.'; ?></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="complaint-filter-summary complaint-filter-summary--bottom">
        <strong class="js-complaint-count"><?= $complaintRowCount; ?></strong> tiket ditemukan
    </div>
</div>

<div class="complaint-filter-overlay" id="complaintFilterOverlay" hidden aria-hidden="true">
    <div class="complaint-filter-overlay__backdrop js-close-complaint-filters"></div>
    <div class="complaint-filter-overlay__dialog" role="dialog" aria-modal="true" aria-labelledby="complaintFilterTitle">
        <div class="complaint-filter-overlay__header">
            <div><p class="complaint-modal__eyebrow">Filter Data</p><h2 id="complaintFilterTitle">Filter Laporan Keluhan</h2></div>
            <button type="button" class="icon-round js-close-complaint-filters" aria-label="Tutup filter"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="get" class="complaint-filter-bar complaint-filter-bar--overlay js-complaint-filter-form">
            <input type="hidden" name="page" value="data-keluhan">
            <input type="hidden" name="complaint_search" value="<?= e((string) ($complaintFilters['search'] ?? '')); ?>">
            <label class="complaint-filter-bar__field">
                <span>Status</span>
                <select name="complaint_status" class="js-complaint-filter-input">
                    <option value="">Semua Status</option>
                    <?php foreach (['NOT YET', 'ON PROGRESS', 'DONE'] as $statusOption): ?>
                        <option value="<?= e($statusOption); ?>" <?= (string) ($complaintFilters['status'] ?? '') === $statusOption ? 'selected' : ''; ?>><?= e($statusOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="complaint-filter-bar__field">
                <span>Divisi</span>
                <select name="complaint_division" class="js-complaint-filter-input">
                    <option value="">Semua Divisi</option>
                    <?php foreach (($complaintFilters['division_options'] ?? []) as $divisionOption): ?>
                        <option value="<?= e($divisionOption); ?>" <?= (string) ($complaintFilters['division'] ?? '') === (string) $divisionOption ? 'selected' : ''; ?>><?= e((string) $divisionOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="complaint-filter-bar__field">
                <span>Dari tanggal</span>
                <input type="date" name="complaint_date_from" value="<?= e((string) ($complaintFilters['date_from'] ?? '')); ?>" class="js-complaint-filter-input">
            </label>
            <label class="complaint-filter-bar__field">
                <span>Sampai tanggal</span>
                <input type="date" name="complaint_date_to" value="<?= e((string) ($complaintFilters['date_to'] ?? '')); ?>" class="js-complaint-filter-input">
            </label>
            <div class="complaint-filter-bar__actions">
                <button type="submit" class="btn btn--primary">Terapkan</button>
                <a href="index.php?page=data-keluhan" class="btn btn--ghost complaint-filter-bar__reset">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="complaint-modal" id="complaintDetailModal" hidden aria-hidden="true">
    <div class="complaint-modal__backdrop js-close-complaint-modal"></div>
    <div class="complaint-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="complaintDetailTitle">
        <div class="complaint-modal__header">
            <div><p class="complaint-modal__eyebrow">Detail Tiket</p><h2 id="complaintDetailTitle">-</h2></div>
            <button type="button" class="icon-round js-close-complaint-modal" aria-label="Tutup detail tiket"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="complaint-modal__content">
            <div class="complaint-modal__left-panel">
                <h3 class="complaint-modal__section-title"><i class="fa-solid fa-circle-info"></i> Informasi Laporan</h3>
                <div class="complaint-modal__grid">
                    <div class="complaint-modal__field"><span>Tanggal &amp; Jam</span><strong id="complaintDetailDatetime">-</strong></div>
                    <div class="complaint-modal__field"><span>Status</span><strong id="complaintDetailStatus">-</strong></div>
                    <div class="complaint-modal__field"><span>Nama Pelapor</span><strong id="complaintDetailName">-</strong></div>
                    <div class="complaint-modal__field"><span>Email</span><strong id="complaintDetailEmail">-</strong></div>
                    <div class="complaint-modal__field"><span>Divisi</span><strong id="complaintDetailDivision">-</strong></div>
                    <div class="complaint-modal__field"><span>Aset/Barang</span><strong id="complaintDetailItem">-</strong></div>
                    <div class="complaint-modal__field"><span>Lokasi</span><strong id="complaintDetailLocation">-</strong></div>
                    <div class="complaint-modal__field"><span>Ditangani Oleh</span><strong id="complaintDetailHandledBy">-</strong></div>
                    <div class="complaint-modal__field complaint-modal__field--full"><span>Status Email</span><strong id="complaintDetailEmailStatus">-</strong></div>
                    <div class="complaint-modal__field complaint-modal__field--full"><span>Deskripsi Kerusakan</span><p id="complaintDetailDescription">-</p></div>
                    <div class="complaint-modal__field complaint-modal__field--full"><span>Catatan Penanganan Terakhir</span><p id="complaintDetailNotes">-</p></div>
                </div>
                <div class="complaint-modal__image-wrap" id="complaintDetailImageWrap" hidden>
                    <div class="complaint-modal__image-header"><span>Dokumentasi</span><button type="button" class="btn btn--ghost btn--xs js-open-complaint-image-from-detail" id="complaintDetailImageBtn">Lihat ukuran penuh</button></div>
                    <img id="complaintDetailImage" src="" alt="Dokumentasi tiket">
                    <div id="complaintDetailImageLinkWrap" style="padding:15px; text-align:center; background:#f8f9fa; border-radius:4px; border:1px solid #e9ecef; margin-top:8px;" hidden>
                        <a id="complaintDetailImageLink" href="#" target="_blank" class="btn btn--primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link Google Drive / Dokumentasi</a>
                    </div>
                </div>
                <div class="complaint-modal__history">
                    <button type="button" class="complaint-modal__history-header js-toggle-complaint-history" aria-expanded="false" aria-controls="complaintHistoryListWrap">
                        <span class="complaint-modal__history-title">
                            <i class="fa-solid fa-history"></i> Riwayat Perubahan
                        </span>
                        <i class="fa-solid fa-chevron-down complaint-modal__history-chevron"></i>
                    </button>
                    <div class="complaint-modal__history-body-wrap" id="complaintHistoryListWrap" style="display: none;">
                        <div class="complaint-history-list" id="complaintHistoryList">
                            <div class="complaint-history-empty">Belum ada riwayat perubahan tiket.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="complaint-modal__right-panel">
                <div class="complaint-modal__form-card">
                    <h3 class="complaint-modal__form-title"><i class="fa-solid fa-screwdriver-wrench"></i> Tindak Lanjut</h3>
                    <form method="post" class="complaint-action-form" id="complaintModalActionForm">
                        <input type="hidden" name="action" value="update_it_support_status">
                        <input type="hidden" name="ticket_id" id="complaintModalTicketId" value="0">
                        <label class="complaint-action-form__label">
                            <span>Status Tiket</span>
                            <select name="status" id="complaintModalStatus" class="complaint-action-form__select">
                                <?php foreach (['NOT YET', 'ON PROGRESS', 'DONE'] as $statusOption): ?>
                                    <option value="<?= e($statusOption); ?>"><?= e($statusOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="complaint-action-form__label">
                            <span>PIC Penanganan</span>
                            <select name="handled_by_user_id" id="complaintModalPIC" class="complaint-action-form__select">
                                <option value="">Pilih PIC IT</option>
                                <?php foreach ($itHandlerOptions as $handlerOption): ?>
                                    <?php $handlerId = (int) ($handlerOption['id'] ?? 0); ?>
                                    <option value="<?= $handlerId; ?>"><?= e((string) ($handlerOption['name'] ?? 'PIC IT')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="complaint-action-form__label">
                            <span>Catatan Penanganan</span>
                            <textarea name="catatan_penanganan" id="complaintModalCatatan" rows="4" placeholder="Contoh: sudah dilakukan pengecekan, reset konfigurasi, penggantian kabel, dll."></textarea>
                        </label>
                        <label class="complaint-action-form__check">
                            <input type="checkbox" name="send_email_notification" value="1" checked>
                            <span>Kirim email ke pelapor</span>
                        </label>
                        <div class="complaint-action-form__email-status" id="complaintModalEmailStatusInfo">
                            Validasi: email pelapor dicek sebelum dikirim.
                        </div>
                        <button type="submit" class="btn btn--primary complaint-action-form__submit">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan &amp; Proses
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="complaint-image-viewer" id="complaintImageViewer" hidden aria-hidden="true">
    <div class="complaint-image-viewer__backdrop js-close-complaint-image"></div>
    <div class="complaint-image-viewer__dialog" role="dialog" aria-modal="true" aria-labelledby="complaintImageTitle">
        <div class="complaint-image-viewer__header"><h2 id="complaintImageTitle">Dokumentasi Tiket</h2><button type="button" class="icon-round js-close-complaint-image" aria-label="Tutup dokumentasi"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="complaint-image-viewer__body"><img id="complaintImageViewerImg" src="" alt="Dokumentasi tiket ukuran penuh"></div>
    </div>
</div>

<!-- Modal Pengaturan Google Form Sync -->
<div class="complaint-modal" id="gformSettingsModal" hidden aria-hidden="true">
    <div class="complaint-modal__backdrop js-close-gform-settings"></div>
    <div class="complaint-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="gformSettingsTitle" style="max-width: 600px;">
        <div class="complaint-modal__header">
            <div>
                <p class="complaint-modal__eyebrow">Integrasi Google Form</p>
                <h2 id="gformSettingsTitle">Pengaturan Google Form</h2>
            </div>
            <button type="button" class="icon-round js-close-gform-settings" aria-label="Tutup pengaturan"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="complaint-modal__content" style="display: block; padding: 20px;">
            <form method="post" action="index.php?page=data-keluhan" class="complaint-action-form" style="width: 100%;">
                <input type="hidden" name="action" value="update_gform_settings">
                
                <div style="margin-bottom: 20px;">
                    <label class="complaint-action-form__label" style="display: block; margin-bottom: 8px;">
                        <span style="font-weight: 600; display: block; margin-bottom: 6px; text-align: left;">Google Sheet CSV URL</span>
                        <textarea name="google_sheet_csv_url" rows="4" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--c-muted); font-family: monospace; font-size: 13px;" placeholder="https://docs.google.com/spreadsheets/d/e/2PACX-.../pub?output=csv"><?= e($data['google_sheet_csv_url'] ?? ''); ?></textarea>
                    </label>
                    <p style="font-size: 12px; color: var(--c-primary); margin-top: 6px; line-height: 1.5; text-align: left;">
                        <i class="fa-solid fa-circle-info"></i> <strong>Cara mendapatkan URL CSV Google Sheets Respon:</strong><br>
                        1. Di halaman respon Google Sheets Anda, buka menu <strong>File &gt; Share (Bagikan) &gt; Publish to web (Publikasikan ke web)</strong>.<br>
                        2. Pilih tab respon Google Form Anda.<br>
                        3. Ubah tipe output dari Halaman Web menjadi <strong>Comma-separated values (.csv)</strong>.<br>
                        4. Klik <strong>Publish</strong>, lalu salin link URL dan tempelkan di atas.
                    </p>
                </div>
                
                <div style="background: #eef7fc; border-left: 4px solid var(--c-primary-2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; line-height: 1.5; text-align: left; color: var(--c-primary);">
                    <strong>Struktur Kolom Form:</strong><br>
                    Sistem akan mencocokkan kolom secara otomatis berdasarkan nama kolom berikut:<br>
                    • Timestamp (Waktu)<br>
                    • Email (Email Pelapor)<br>
                    • Nama (Nama Pelapor)<br>
                    • Divisi (Divisi Pelapor)<br>
                    • Aset (Aset/Perangkat)<br>
                    • Lokasi (Lokasi perbaikan)<br>
                    • Deskripsi / Kerusakan (Detail keluhan)<br>
                    • Dokumentasi / Gambar / Upload (Link Google Drive)
                </div>

                <div class="modal__footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="button" class="btn btn--ghost js-close-gform-settings" style="height:36px; padding:0 16px; border-radius:18px;">Batal</button>
                    <button type="submit" class="btn btn--primary" style="height:36px; padding:0 16px; border-radius:18px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>
