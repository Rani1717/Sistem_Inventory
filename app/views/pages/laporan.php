<?php
$reportCards = [
    ['type' => 'inventory', 'title' => "LAPORAN DATA\nINVENTARIS", 'icon' => 'fa-regular fa-file-lines'],
    ['type' => 'complaint', 'title' => "LAPORAN\nTIKET KELUHAN", 'icon' => 'fa-regular fa-message'],
    ['type' => 'log', 'title' => "LAPORAN LOG\nMUTASI ASET", 'icon' => 'fa-regular fa-clipboard'],
    ['type' => 'routine', 'title' => "LAPORAN ROUTINE\nMONITORING", 'icon' => 'fa-solid fa-list-check'],
    ['type' => 'user', 'title' => "LAPORAN\nUSER", 'icon' => 'fa-regular fa-user'],
];
$reportView = $data['report_view'] ?? null;
$reportFilters = $data['report_filters'] ?? [
    'date_from' => (string) ($_GET['report_date_from'] ?? ''),
    'date_to' => (string) ($_GET['report_date_to'] ?? ''),
    'division' => (string) ($_GET['report_division'] ?? ''),
    'month' => (string) ($_GET['report_month'] ?? 'all'),
    'year' => (string) ($_GET['report_year'] ?? date('Y')),
    'user_role' => (string) ($_GET['report_user_role'] ?? ''),
    'user_division' => (string) ($_GET['report_user_division'] ?? ''),
];
$divisionOptions = $data['report_division_options'] ?? [];
$monthOptions = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$currentYear = (int) date('Y');
$filterParams = [];
foreach (['report_date_from' => 'date_from', 'report_date_to' => 'date_to', 'report_division' => 'division', 'report_month' => 'month', 'report_year' => 'year', 'report_user_role' => 'user_role', 'report_user_division' => 'user_division'] as $param => $key) {
    $value = trim((string) ($reportFilters[$key] ?? ''));
    if ($value !== '') { $filterParams[$param] = $value; }
}
$renderReportCell = static function ($cell): void {
    if (is_array($cell) && (($cell['type'] ?? '') === 'image')) {
        $src = trim((string) ($cell['src'] ?? ''));
        $alt = trim((string) ($cell['alt'] ?? 'Gambar'));
        if ($src !== '') {
            $srcUrl = strpos($src, 'uploads/inventory/') === 0 ? ('public/assets/' . $src) : asset($src);
            echo '<img class="report-word-image" src="' . e($srcUrl) . '" alt="' . e($alt) . '">';
        } else {
            echo '-';
        }
        return;
    }
    echo e((string) $cell);
};
?>
<style>
/* Centering report cards on bottom rows */
@media (min-width: 1281px) {
    .report-card-grid--actions {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 20px !important;
    }
    .report-card-grid--actions .report-card {
        flex: 0 1 calc(33.333% - 14px) !important;
        box-sizing: border-box !important;
    }
}
@media (max-width: 1280px) and (min-width: 1025px) {
    .report-card-grid--actions {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 20px !important;
    }
    .report-card-grid--actions .report-card {
        flex: 0 1 calc(50% - 10px) !important;
        box-sizing: border-box !important;
    }
}
@media (max-width: 1024px) {
    .report-card-grid--actions {
        display: flex !important;
        flex-direction: column !important;
        gap: 20px !important;
    }
    .report-card-grid--actions .report-card {
        width: 100% !important;
        box-sizing: border-box !important;
    }
}
</style>
<div class="detail-header detail-header--single-title report-main-header">
    <h1>LAPORAN IT ASSET MANAGEMENT</h1>
    <span class="detail-header__updated detail-header__updated--center">LAST UPDATED : <span data-report-live-updated><?= e($data['updated']); ?></span></span>
</div>

<?php if (is_array($reportView)): ?>
    <?php
    $viewType = (string) ($reportView['type'] ?? 'inventory');
    $exportBase = ['page' => 'laporan', 'type' => $viewType] + $filterParams;
    ?>
    <section class="report-print-shell">
        <div class="report-print-toolbar no-print">
            <form class="report-print-filter" method="get" action="index.php">
                <input type="hidden" name="page" value="laporan">
                <input type="hidden" name="report_view" value="<?= e($viewType); ?>">
                <?php if (in_array($viewType, ['inventory', 'complaint', 'log'], true)): ?>
                    <label>Dari Tanggal
                        <input type="date" name="report_date_from" value="<?= e((string) ($reportFilters['date_from'] ?? '')); ?>">
                    </label>
                    <label>Sampai Tanggal
                        <input type="date" name="report_date_to" value="<?= e((string) ($reportFilters['date_to'] ?? '')); ?>">
                    </label>
                    <label>Divisi
                        <select name="report_division">
                            <option value="">Semua Divisi</option>
                            <?php foreach ($divisionOptions as $division): ?>
                                <?php
                                $code = (string) ($division['division_code'] ?? '');
                                $label = (string) ($division['division_label'] ?? $code);
                                $dbName = (string) ($division['inventory_db_name'] ?? '');
                                $optionValue = $dbName !== '' ? $dbName : ($code !== '' ? $code : $label);
                                $optionTextSource = $code !== '' ? $code : ($label !== '' ? $label : $dbName);
                                $optionText = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $optionTextSource), '_'));
                                if ($optionText === '') { $optionText = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $optionValue), '_')); }
                                $currentDivision = (string) ($reportFilters['division'] ?? '');
                                $isSelected = in_array($currentDivision, array_filter([$optionValue, $code, $label, $dbName]), true);
                                ?>
                                <option value="<?= e($optionValue); ?>" <?= $isSelected ? 'selected' : ''; ?>><?= e($optionText); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php elseif ($viewType === 'routine'): ?>
                    <label>Bulan
                        <select name="report_month">
                            <option value="all" <?= ((string) ($reportFilters['month'] ?? 'all')) === 'all' ? 'selected' : ''; ?>>Semua Bulan</option>
                            <?php foreach ($monthOptions as $monthNumber => $monthName): ?>
                                <?php $monthValue = str_pad((string) $monthNumber, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= e($monthValue); ?>" <?= ((string) ($reportFilters['month'] ?? 'all')) === $monthValue ? 'selected' : ''; ?>><?= e($monthName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Tahun
                        <select name="report_year">
                            <?php for ($yearOption = $currentYear - 3; $yearOption <= $currentYear + 3; $yearOption++): ?>
                                <option value="<?= $yearOption; ?>" <?= (int) ($reportFilters['year'] ?? $currentYear) === $yearOption ? 'selected' : ''; ?>><?= $yearOption; ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                <?php elseif ($viewType === 'user'): ?>
                    <label>Role
                        <select name="report_user_role">
                            <option value="">Semua Role</option>
                            <?php foreach (['admin' => 'Admin', 'user' => 'User'] as $roleValue => $roleLabel): ?>
                                <option value="<?= e($roleValue); ?>" <?= ((string) ($reportFilters['user_role'] ?? '')) === $roleValue ? 'selected' : ''; ?>><?= e($roleLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Divisi
                        <select name="report_user_division">
                            <option value="">Semua Divisi</option>
                            <?php foreach ($divisionOptions as $division): ?>
                                <?php
                                $code = (string) ($division['division_code'] ?? '');
                                $label = (string) ($division['division_label'] ?? $code);
                                $optionValue = $code !== '' ? $code : $label;
                                $optionText = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', ($code !== '' ? $code : $label)), '_'));
                                $currentUserDivision = (string) ($reportFilters['user_division'] ?? '');
                                ?>
                                <option value="<?= e($optionValue); ?>" <?= $currentUserDivision === $optionValue || $currentUserDivision === $label ? 'selected' : ''; ?>><?= e($optionText ?: $optionValue); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <button class="btn btn--primary btn--lg" type="submit">TERAPKAN</button>
            </form>
            <div class="report-toolbar-actions">
                <a class="btn btn--ghost btn--lg" href="index.php?page=laporan">KEMBALI</a>
                <button class="btn btn--accent btn--lg" type="button" onclick="printReportOnly()">PRINT / SIMPAN PDF</button>
            </div>
        </div>
        <div class="report-preview-scroll">
            <?php $reportPages = !empty($reportView['pages']) ? $reportView['pages'] : [['title' => (string) ($reportView['title'] ?? 'Laporan'), 'sections' => ($reportView['sections'] ?? [])]]; ?>
            <?php foreach ($reportPages as $pageIndex => $reportPage): ?>
                <article class="report-word-page <?= $pageIndex > 0 ? 'report-word-page--break' : ''; ?>">
                    <header class="report-word-header">
                        <h2><?= e((string) ($reportPage['title'] ?? ($reportView['title'] ?? 'Laporan'))); ?></h2>
                        <p>Last Updated : <span data-report-live-updated><?= e($data['updated']); ?></span></p>
                        <?php if (!empty($reportView['filter_text'])): ?><p><?= e((string) $reportView['filter_text']); ?></p><?php endif; ?>
                    </header>
                    <?php if ($viewType === 'inventory' && isset($reportPage['groups'])): ?>
                        <?php foreach (($reportPage['groups'] ?? []) as $groupIndex => $group): ?>
                            <section class="report-inventory-detail <?= $groupIndex > 0 ? 'report-inventory-detail--break' : ''; ?>">
                                <?php $summary = (array) ($group['summary'] ?? []); ?>
                                <div class="report-inventory-summary">
                                    <?php foreach ([
                                        ['Computer Name', (string) ($summary['computer_name'] ?? '-')], ['User', (string) ($summary['user'] ?? '-')],
                                        ['Processor', (string) ($summary['processor'] ?? '-')], ['RAM', (string) ($summary['ram'] ?? '-')],
                                        ['Harddisk', (string) ($summary['harddisk'] ?? '-')], ['IP Address', (string) ($summary['ip'] ?? '-')],
                                        ['Sistem Operasi', (string) ($summary['os'] ?? '-')], ['Licensed Windows', (string) ($summary['license'] ?? '-')],
                                        ['MS Office', (string) ($summary['office'] ?? '-')], ['Licensed Office', (string) ($summary['office_license'] ?? '-')],
                                    ] as $item): ?>
                                        <div class="report-inventory-summary__cell"><strong><?= e($item[0]); ?>:</strong><span><?= e($item[1]); ?></span></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="report-word-table-wrap">
                                    <table class="report-word-table report-inventory-detail-table">
                                        <thead><tr><?php foreach (($group['headers'] ?? []) as $header): ?><th><?= e((string) $header); ?></th><?php endforeach; ?></tr></thead>
                                        <tbody>
                                            <?php if (empty($group['rows'])): ?>
                                                <tr><td colspan="<?= max(1, count($group['headers'] ?? [])); ?>">Tidak ada data inventaris untuk user ini.</td></tr>
                                            <?php else: ?>
                                                <?php foreach (($group['rows'] ?? []) as $row): ?>
                                                    <tr><?php foreach ($row as $cell): ?><td><?php $renderReportCell($cell); ?></td><?php endforeach; ?></tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach (($reportPage['sections'] ?? []) as $section): ?>
                            <section class="report-word-section">
                                <h3><?= e((string) ($section['title'] ?? '-')); ?></h3>
                                <?php if (!empty($section['subtitle'])): ?><p class="report-word-subtitle"><?= e((string) $section['subtitle']); ?></p><?php endif; ?>
                                <div class="report-word-table-wrap">
                                    <table class="report-word-table">
                                        <thead><tr><?php foreach (($section['headers'] ?? []) as $header): ?><th><?= e((string) $header); ?></th><?php endforeach; ?></tr></thead>
                                        <tbody>
                                            <?php if (empty($section['rows'])): ?>
                                                <tr><td colspan="<?= max(1, count($section['headers'] ?? [])); ?>">Tidak ada data.</td></tr>
                                            <?php else: ?>
                                                <?php foreach (($section['rows'] ?? []) as $row): ?>
                                                    <tr><?php foreach ($row as $cell): ?><td><?php $renderReportCell($cell); ?></td><?php endforeach; ?></tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <div class="report-card-grid report-card-grid--actions">
        <?php foreach ($reportCards as $card): ?>
            <?php
            $cardType = (string) ($card['type'] ?? 'inventory');
            $baseParams = ['page' => 'laporan', 'type' => $cardType];
            if ($cardType === 'routine') {
                $baseParams['report_all'] = '1';
            }
            $pdfUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'report_export', 'format' => 'pdf']);
            $excelUrl = 'index.php?' . http_build_query($baseParams + ['action' => 'report_export', 'format' => 'xlsx']);
            $viewUrl = 'index.php?' . http_build_query(['page' => 'laporan', 'report_view' => $cardType]);
            ?>
            <article class="report-card">
                <div class="report-card__icon"><i class="<?= e($card['icon']); ?>"></i></div>
                <h3><?= nl2br(e($card['title'])); ?></h3>
                <div class="report-card__actions report-card__actions--export">
                    <a class="btn btn--primary btn--sm report-export-btn" href="<?= e($pdfUrl); ?>">EXPORT PDF <i class="fa-regular fa-file-pdf"></i></a>
                    <a class="btn btn--success btn--sm report-export-btn" href="<?= e($excelUrl); ?>">EXPORT EXCEL <i class="fa-regular fa-file-excel"></i></a>
                </div>
                <a class="btn btn--accent btn--lg report-view-btn" href="<?= e($viewUrl); ?>">VIEW</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php if (is_array($reportView)): ?>
<script>
function printReportOnly() {
    var source = document.querySelector('.report-preview-scroll');
    if (!source) { window.print(); return; }

    var clone = source.cloneNode(true);
    clone.querySelectorAll('script, .no-print, .report-print-toolbar').forEach(function (el) { el.remove(); });

    if (!clone.querySelector('table, .report-inventory-summary, .report-word-section, .report-inventory-detail')) {
        window.spmtPopup('Data laporan belum terbaca. Silakan refresh halaman View lalu coba Print / Simpan PDF lagi.', 'warning');
        return;
    }

    var iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.position = 'fixed';
    iframe.style.left = '-10000px';
    iframe.style.top = '0';
    iframe.style.width = '1px';
    iframe.style.height = '1px';
    iframe.style.border = '0';
    document.body.appendChild(iframe);

    var printWindow = iframe.contentWindow;
    var printDocument = printWindow.document;
    var baseHref = document.baseURI.replace(/"/g, '&quot;');

    printDocument.open();
    printDocument.write('<!doctype html><html><head><meta charset="utf-8"><title></title><base href="' + baseHref + '">');
    printDocument.write('<style>');
    printDocument.write('@page{size:A4 landscape;margin:8mm;}*{box-sizing:border-box;}html,body{margin:0!important;padding:0!important;background:#fff!important;color:#001b43;font-family:Arial,Helvetica,sans-serif;font-size:10px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}');
    printDocument.write('.report-preview-scroll{display:block!important;width:100%!important;max-height:none!important;overflow:visible!important;margin:0!important;padding:0!important;background:#fff!important;border:0!important;box-shadow:none!important;}');
    printDocument.write('.report-word-page{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;background:#fff!important;border:0!important;box-shadow:none!important;page-break-after:auto;}');
    printDocument.write('.report-word-page+.report-word-page,.report-word-page--break,.report-inventory-detail--break{page-break-before:always!important;break-before:page!important;}');
    printDocument.write('.report-word-header{text-align:center;margin:0 0 12px 0;padding:0 0 8px 0;border-bottom:2px solid #1f5f9f;}');
    printDocument.write('.report-word-header h2{margin:0 0 4px 0;font-size:16px;font-weight:800;text-transform:uppercase;color:#1f5f9f;}.report-word-header p{margin:2px 0;font-size:9px;color:#001b43;}');
    printDocument.write('.report-word-section{display:block;margin:0 0 14px 0;page-break-inside:auto;}.report-word-section h3{margin:0 0 6px 0;font-size:12px;font-weight:800;text-transform:uppercase;color:#1f5f9f;}.report-word-subtitle{margin:0 0 6px 0;font-size:9px;color:#001b43;}');
    printDocument.write('.report-word-table-wrap{display:block;width:100%;overflow:visible!important;background:#fff!important;}table,.report-word-table{border-collapse:collapse!important;width:100%!important;table-layout:auto!important;background:#fff!important;font-size:8px;line-height:1.2;}');
    printDocument.write('th,td,.report-word-table th,.report-word-table td{border:1px solid #9fb0c3!important;padding:4px!important;vertical-align:top!important;white-space:normal!important;word-break:break-word!important;overflow-wrap:anywhere!important;color:#001b43;}th,.report-word-table th{background:#1f5f9f!important;color:#fff!important;font-weight:800!important;text-align:center!important;}');
    printDocument.write('.report-word-table tbody tr:nth-child(even) td{background:#f7f9fc!important;}.report-word-image{display:block!important;width:44px!important;height:34px!important;object-fit:cover!important;border:1px solid #b6cae2!important;border-radius:4px!important;margin:0 auto!important;background:#fff!important;}');
    printDocument.write('.report-inventory-detail{display:block;margin:0 0 14px 0;page-break-inside:avoid;}.report-inventory-summary{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:6px!important;margin:0 0 12px 0!important;}');
    printDocument.write('.report-inventory-summary__cell{display:grid!important;grid-template-columns:100px 1fr!important;gap:6px!important;min-height:26px!important;padding:5px 8px!important;border:1px solid #b6cae2!important;background:#eaf4fc!important;color:#001b43!important;font-size:9px!important;}.report-inventory-summary__cell strong{color:#1c5dab!important;}');
    printDocument.write('.report-inventory-detail-table td:nth-child(2),.report-inventory-detail-table th:nth-child(2){text-align:center!important;width:64px!important;}.report-inventory-detail-table td:last-child{font-weight:800!important;text-align:center!important;}.report-inventory-detail-table td:last-child:not(:empty){background:#3dae4f!important;color:#fff!important;}');
    printDocument.write('</style></head><body></body></html>');
    printDocument.close();
    printDocument.body.appendChild(clone);

    var images = Array.prototype.slice.call(printDocument.images || []);
    var waitForImages = Promise.all(images.map(function (img) {
        if (img.complete) return Promise.resolve();
        return new Promise(function (resolve) { img.onload = img.onerror = resolve; setTimeout(resolve, 1200); });
    }));
    var cleanup = function () { setTimeout(function () { if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe); }, 600); };
    waitForImages.then(function () {
        printWindow.focus();
        if ('onafterprint' in printWindow) printWindow.onafterprint = cleanup;
        setTimeout(function () { printWindow.print(); setTimeout(cleanup, 1800); }, 250);
    });
}
</script>
<?php endif; ?>
