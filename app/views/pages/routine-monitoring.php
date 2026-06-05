<?php
$routine = $data['routine_monitoring'] ?? [];
$context = $routine['context'] ?? [];
$groupedItems = $routine['grouped_items'] ?? [];
$manageItems = $routine['manage_items'] ?? [];
$categories = $routine['categories'] ?? [];
$manageCategories = $routine['manage_categories'] ?? $categories;
$recapByDate = $routine['recap_by_date'] ?? [];
$monthValue = (string) ($context['month_value'] ?? date('m'));
$yearValue = (string) ($context['year_value'] ?? date('Y'));
$searchValue = (string) ($context['search'] ?? '');
$days = $context['days'] ?? [];
$canManageRoutineItems = AuthController::isAdminSpmt();
$monthLabel = (string) ($context['month_label'] ?? date('F Y'));
$statusOptions = [
    '' => ['label' => '-', 'class' => 'empty'],
    'BAIK' => ['label' => 'Baik', 'class' => 'baik'],
    'KURANG BAIK' => ['label' => 'Kurang Baik', 'class' => 'kurang-baik'],
    'BURUK' => ['label' => 'Buruk', 'class' => 'buruk'],
];
$defaultIcons = [
    'GATE' => 'fa-solid fa-door-open',
    'CCTV' => 'fa-solid fa-video',
    'SERVER' => 'fa-solid fa-server',
];
$categoryMeta = [];
foreach ($categories as $cat) {
    $catName = strtoupper((string) ($cat['category_name'] ?? 'GATE'));
    if ($catName === 'UMUM') { continue; }
    $categoryMeta[$catName] = [
        'label' => $catName,
        'icon' => (string) ($cat['icon_class'] ?? ($defaultIcons[$catName] ?? 'fa-solid fa-list-check')),
    ];
    if (!isset($groupedItems[$catName])) { $groupedItems[$catName] = []; }
}
if (empty($categoryMeta)) {
    foreach ($defaultIcons as $catName => $icon) {
        $categoryMeta[$catName] = ['label' => $catName, 'icon' => $icon];
        if (!isset($groupedItems[$catName])) { $groupedItems[$catName] = []; }
    }
}
$returnQuery = http_build_query(['routine_month' => $monthValue, 'routine_year' => $yearValue, 'routine_search' => $searchValue]);
$monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$yearNow = (int) date('Y');
$recapCounts = [];
foreach ($days as $day) {
    $dateKey = (string) ($day['date'] ?? '');
    $count = 0;
    foreach (($recapByDate[$dateKey] ?? []) as $statusRows) {
        $count += count($statusRows['BAIK'] ?? []) + count($statusRows['KURANG BAIK'] ?? []) + count($statusRows['BURUK'] ?? []);
    }
    $recapCounts[$dateKey] = $count;
}
$monthPdfUrl = 'index.php?' . http_build_query([
    'page' => 'routine-monitoring',
    'action' => 'export_routine_pdf',
    'recap_scope' => 'month',
    'routine_month' => $monthValue,
    'routine_year' => $yearValue,
    'routine_search' => $searchValue,
]);
$weekPdfLinks = [];
if (!empty($days)) {
    $firstDate = new DateTimeImmutable((string) ($days[0]['date'] ?? date('Y-m-01')));
    $lastDate = new DateTimeImmutable((string) ($days[count($days) - 1]['date'] ?? date('Y-m-t')));
    $weekStart = $firstDate;
    $weekNo = 1;
    while ($weekStart <= $lastDate) {
        $weekEnd = $weekStart->modify('sunday this week');
        if ($weekEnd > $lastDate) {
            $weekEnd = $lastDate;
        }
        $weekPdfLinks[] = [
            'label' => 'PDF Minggu ' . $weekNo,
            'range' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
            'url' => 'index.php?' . http_build_query([
                'page' => 'routine-monitoring',
                'action' => 'export_routine_pdf',
                'recap_scope' => 'week',
                'week_start' => $weekStart->format('Y-m-d'),
                'routine_month' => $monthValue,
                'routine_year' => $yearValue,
                'routine_search' => $searchValue,
            ]),
        ];
        $weekStart = $weekEnd->modify('+1 day');
        $weekNo++;
    }
}
?>

<div class="routine-page routine-page--matrix">
    <div class="detail-header detail-header--single-title routine-header">
        <div>
            <h1>ROUTINE MONITORING</h1>
        </div>
        <?php if ($canManageRoutineItems): ?>
            <button type="button" class="btn btn--secondary routine-manage-link js-open-routine-manager"><i class="fa-solid fa-list-check"></i> Kelola List Checking</button>
        <?php endif; ?>
    </div>

    <form class="routine-filter-card routine-filter-card--matrix" method="get" action="index.php">
        <input type="hidden" name="page" value="routine-monitoring">
        <label>
            <span>Bulan</span>
            <select name="routine_month">
                <?php foreach ($monthNames as $number => $name): ?>
                    <option value="<?= str_pad((string) $number, 2, '0', STR_PAD_LEFT); ?>" <?= (int) $monthValue === $number ? 'selected' : ''; ?>><?= e($name); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Tahun</span>
            <select name="routine_year">
                <?php for ($year = $yearNow - 3; $year <= $yearNow + 3; $year++): ?>
                    <option value="<?= $year; ?>" <?= (int) $yearValue === $year ? 'selected' : ''; ?>><?= $year; ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label class="routine-filter-search">
            <span>Search</span>
            <input type="text" name="routine_search" value="<?= e($searchValue); ?>" placeholder="Cari list monitoring..." class="js-routine-live-search" autocomplete="off">
        </label>
        <div class="routine-filter-actions routine-filter-actions--double">
            <button type="submit" class="btn btn--primary routine-apply-btn"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>Terapkan</span></button>
            <a class="btn btn--secondary routine-reset-btn" href="index.php?page=routine-monitoring"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Reset</span></a>
        </div>
        <div class="routine-filter-card__current">
            <small>Periode aktif</small>
            <strong><?= e($monthLabel); ?></strong>
        </div>
    </form>

    <?php if ($canManageRoutineItems): ?>
        <div class="routine-manager-modal" id="routineItemManagerModal" hidden aria-hidden="true">
            <div class="routine-manager-dialog" role="dialog" aria-modal="true" aria-labelledby="routineManagerTitle">
                <div class="routine-manager-card__head routine-manager-modal__head">
                    <div>
                        <h2 id="routineManagerTitle">Kelola List Checking</h2>
                    </div>
                    <button type="button" class="icon-round js-close-routine-manager" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="routine-manager-scroll">
                    <section class="routine-manager-section">
                        <div class="routine-manager-section__title"><h3>Kategori</h3></div>
                        <form class="routine-add-form routine-add-form--category" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                            <input type="hidden" name="action" value="add_routine_category">
                            <label class="routine-add-form__name"><span>Nama Kategori</span><input type="text" name="category_name" placeholder="Contoh: GATE" required></label>
                            <button type="submit" class="btn btn--primary routine-btn-lg"><i class="fa-solid fa-plus"></i> Tambah Kategori</button>
                        </form>
                        <div class="routine-manage-list routine-manage-list--modal routine-manage-list--form-cards routine-category-manage-list">
                            <?php foreach ($manageCategories as $cat): ?>
                                <?php
                                $categoryId = (int) ($cat['id'] ?? 0);
                                $categoryName = strtoupper((string) ($cat['category_name'] ?? ''));
                                if ($categoryName === 'UMUM') { continue; }
                                $categoryActive = (int) ($cat['is_active'] ?? 1) === 1;
                                ?>
                                <form class="routine-manage-row routine-manage-row--category <?= $categoryActive ? '' : 'is-inactive'; ?>" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                    <input type="hidden" name="action" value="update_routine_category">
                                    <input type="hidden" name="category_id" value="<?= $categoryId; ?>">
                                    <label class="routine-manage-row__name"><span>Nama Kategori</span><input type="text" name="category_name" value="<?= e($categoryName); ?>" required></label>
                                    <label class="routine-active-toggle"><input type="checkbox" name="is_active" value="1" <?= $categoryActive ? 'checked' : ''; ?>><span>Aktif</span></label>
                                    <div class="routine-manage-row__actions">
                                        <button type="submit" class="btn btn--secondary routine-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                                        <button type="submit" name="action" value="delete_routine_category" class="btn btn--danger routine-btn-lg" onclick="return confirm('Hapus kategori ini dari list aktif?');"><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="routine-manager-section">
                        <div class="routine-manager-section__title"><h3>Item Checking</h3></div>
                        <form class="routine-add-form routine-add-form--modal routine-add-form--no-sort" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                            <input type="hidden" name="action" value="add_routine_item">
                            <label><span>Kategori</span><select name="item_group"><?php foreach ($categoryMeta as $group => $meta): ?><option value="<?= e($group); ?>"><?= e($meta['label']); ?></option><?php endforeach; ?></select></label>
                            <label class="routine-add-form__name"><span>Nama Checking</span><input type="text" name="item_name" placeholder="Contoh: GATE 1" required></label>
                            <button type="submit" class="btn btn--primary routine-btn-lg"><i class="fa-solid fa-plus"></i> Tambah Item</button>
                        </form>
                        <?php
                        $manageItemsByCategory = [];
                        foreach ($categoryMeta as $group => $meta) { $manageItemsByCategory[$group] = []; }
                        foreach ($manageItems as $manageItem) {
                            $manageGroup = strtoupper((string) ($manageItem['item_group'] ?? 'GATE'));
                            if ($manageGroup === 'UMUM' || !isset($manageItemsByCategory[$manageGroup])) { continue; }
                            $manageItemsByCategory[$manageGroup][] = $manageItem;
                        }
                        ?>
                        <div class="routine-manage-category-table">
                            <?php foreach ($manageItemsByCategory as $group => $categoryItems): ?>
                                <section class="routine-manage-category-column">
                                    <div class="routine-manage-category-column__head"><span><i class="<?= e($categoryMeta[$group]['icon'] ?? 'fa-solid fa-list-check'); ?>"></i> <?= e($categoryMeta[$group]['label'] ?? $group); ?></span><strong><?= count($categoryItems); ?> item</strong></div>
                                    <div class="routine-manage-category-column__body">
                                        <?php if (!empty($categoryItems)): ?>
                                            <?php foreach ($categoryItems as $manageItem): ?>
                                                <?php $manageId = (int) ($manageItem['id'] ?? 0); $manageActive = (int) ($manageItem['is_active'] ?? 1) === 1; ?>
                                                <form class="routine-manage-row routine-manage-row--no-sort routine-manage-row--category-field <?= $manageActive ? '' : 'is-inactive'; ?>" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                                    <input type="hidden" name="action" value="update_routine_item">
                                                    <input type="hidden" name="item_id" value="<?= $manageId; ?>">
                                                    <label><span>Kategori</span><select name="item_group"><?php foreach ($categoryMeta as $selectGroup => $meta): ?><option value="<?= e($selectGroup); ?>" <?= $group === $selectGroup ? 'selected' : ''; ?>><?= e($meta['label']); ?></option><?php endforeach; ?></select></label>
                                                    <label class="routine-manage-row__name"><span>Nama Checking</span><input type="text" name="item_name" value="<?= e((string) ($manageItem['item_name'] ?? '')); ?>" required></label>
                                                    <label class="routine-active-toggle"><input type="checkbox" name="is_active" value="1" <?= $manageActive ? 'checked' : ''; ?>><span>Aktif</span></label>
                                                    <div class="routine-manage-row__actions">
                                                        <button type="submit" class="btn btn--secondary routine-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                                                        <button type="submit" name="action" value="delete_routine_item" class="btn btn--danger routine-btn-lg" onclick="return confirm('Hapus item checking ini dari list aktif?');"><i class="fa-solid fa-trash"></i> Hapus</button>
                                                    </div>
                                                </form>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="routine-empty-mini">Belum ada item.</div>
                                        <?php endif; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form class="routine-matrix-form" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
        <input type="hidden" name="action" value="save_routine_monitoring">
        <input type="hidden" name="routine_month" value="<?= e($monthValue); ?>">
        <input type="hidden" name="routine_year" value="<?= e($yearValue); ?>">
        <input type="hidden" name="routine_search" value="<?= e($searchValue); ?>">

        <?php foreach ($categoryMeta as $groupName => $meta): ?>
            <?php $rows = $groupedItems[$groupName] ?? []; ?>
            <section class="routine-matrix-card">
                <div class="routine-matrix-card__head">
                    <div class="routine-matrix-card__title"><span class="routine-matrix-card__icon"><i class="<?= e($meta['icon']); ?>"></i></span><h2><?= e($meta['label']); ?></h2></div>
                    <button type="submit" name="save_category" value="<?= e($groupName); ?>" class="btn btn--primary routine-save-btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Checklist <?= e($meta['label']); ?></button>
                </div>
                <div class="routine-matrix-table-wrap">
                    <table class="routine-matrix-table">
                        <thead>
                            <tr>
                                <th class="sticky-col">List Monitoring</th>
                                <?php foreach ($days as $day): ?>
                                    <th><?= e((string) ($day['day'] ?? '')); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td class="routine-empty-row" colspan="<?= count($days) + 1; ?>">Belum ada item checking.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $item): ?>
                                    <?php $itemId = (int) ($item['id'] ?? 0); ?>
                                    <tr class="routine-monitoring-row" data-routine-search="<?= e(strtolower((string) ($groupName . ' ' . ($item['item_name'] ?? '')))); ?>">
                                        <td class="sticky-col routine-item-name-cell"><strong><?= e((string) ($item['item_name'] ?? '-')); ?></strong></td>
                                        <?php foreach ($days as $day): ?>
                                            <?php
                                            $dateKey = (string) ($day['date'] ?? '');
                                            $cell = $item['calendar'][$dateKey] ?? ['condition_status' => ''];
                                            $selectedStatus = (string) ($cell['condition_status'] ?? '');
                                            ?>
                                            <td>
                                                <?php
                                                $keteranganVal = (string) ($cell['keterangan'] ?? '');
                                                $needsNote = in_array($selectedStatus, ['KURANG BAIK', 'BURUK'], true);
                                                ?>
                                                <select class="routine-cell-select routine-cell-select--<?= e(strtolower(str_replace(' ', '-', $selectedStatus !== '' ? $selectedStatus : 'empty'))); ?>" name="items[<?= $itemId; ?>][<?= e($dateKey); ?>][condition_status]" onchange="routineCellChange(this)">
                                                    <?php foreach ($statusOptions as $value => $metaStatus): ?>
                                                        <option value="<?= e($value); ?>" <?= $selectedStatus === $value ? 'selected' : ''; ?>><?= e($metaStatus['label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text"
                                                    class="routine-cell-note<?= ($needsNote || $keteranganVal !== '') ? '' : ' routine-cell-note--hidden'; ?>"
                                                    name="items[<?= $itemId; ?>][<?= e($dateKey); ?>][keterangan]"
                                                    value="<?= e($keteranganVal); ?>"
                                                    placeholder="Catatan..."
                                                    maxlength="255">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    </form>

    <section class="routine-recap-launcher">
        <div class="routine-recap-launcher__head routine-recap-launcher__head--with-actions">
            <h2>Rekap Per Hari</h2>
            <div class="routine-pdf-actions">
                <a class="btn btn--secondary routine-pdf-action" href="<?= e($monthPdfUrl); ?>"><i class="fa-solid fa-file-pdf"></i> PDF Bulanan</a>
                <?php foreach ($weekPdfLinks as $weekLink): ?>
                    <a class="btn btn--secondary routine-pdf-action" href="<?= e($weekLink['url']); ?>"><i class="fa-solid fa-file-pdf"></i> <?= e($weekLink['label']); ?> <small><?= e($weekLink['range']); ?></small></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="routine-day-button-list">
            <?php foreach ($days as $day): ?>
                <?php $dateKey = (string) ($day['date'] ?? ''); ?>
                <button type="button" class="routine-day-button js-open-routine-recap" data-recap-date="<?= e($dateKey); ?>">
                    <strong><?= e((string) ($day['day'] ?? '')); ?></strong>
                    <span><?= e(substr((string) ($day['day_name'] ?? ''), 0, 3)); ?></span>
                    <small><?= (int) ($recapCounts[$dateKey] ?? 0); ?> data</small>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="routine-recap-modal" id="routineRecapModal" hidden aria-hidden="true">
        <div class="routine-recap-dialog" role="dialog" aria-modal="true" aria-labelledby="routineRecapTitle">
            <div class="routine-recap-dialog__head">
                <div>
                    <h2 id="routineRecapTitle">Rekap Harian</h2>
                </div>
                <div class="routine-recap-dialog__actions">
                    <a href="#" class="btn btn--secondary js-routine-recap-pdf"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
                    <button type="button" class="icon-round js-close-routine-recap" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="routine-recap-dialog__body js-routine-recap-body"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var recapData = <?= json_encode($recapByDate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};
    var categoryMeta = <?= json_encode($categoryMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};
    var modal = document.getElementById('routineRecapModal');
    var body = modal ? modal.querySelector('.js-routine-recap-body') : null;
    var title = modal ? modal.querySelector('#routineRecapTitle') : null;
    var pdfLink = modal ? modal.querySelector('.js-routine-recap-pdf') : null;
    var month = <?= json_encode($monthValue); ?>;
    var year = <?= json_encode($yearValue); ?>;
    var search = <?= json_encode($searchValue); ?>;
    var groupOrder = Object.keys(categoryMeta);

    function formatDate(dateKey) {
        var parts = (dateKey || '').split('-');
        if (parts.length !== 3) {
            return dateKey;
        }
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function emptyHtml() {
        return '<div class="routine-empty-mini">Belum ada checklist untuk tanggal ini.</div>';
    }

    function renderDate(dateKey) {
        if (!modal || !body || !title || !pdfLink) {
            return;
        }
        var groups = recapData[dateKey] || {};
        title.textContent = 'Rekap Harian ' + formatDate(dateKey);
        pdfLink.href = 'index.php?page=routine-monitoring&action=export_routine_pdf&recap_scope=day&routine_month=' + encodeURIComponent(month) + '&routine_year=' + encodeURIComponent(year) + '&routine_search=' + encodeURIComponent(search) + '&recap_date=' + encodeURIComponent(dateKey);
        var html = '';
        var hasAny = false;
        groupOrder.forEach(function (groupName) {
            var meta = categoryMeta[groupName] || {label: groupName, icon: 'fa-solid fa-list-check'};
            var rows = groups[groupName] || {'BAIK': [], 'KURANG BAIK': [], 'BURUK': []};
            var total = (rows['BAIK'] || []).length + (rows['KURANG BAIK'] || []).length + (rows['BURUK'] || []).length;
            if (total > 0) {
                hasAny = true;
            }
            html += '<section class="routine-recap-section">';
            html += '<div class="routine-recap-section__head"><span><i class="' + meta.icon + '"></i> ' + meta.label + '</span><strong>' + total + ' item</strong></div>';
            html += '<table class="routine-recap-table"><thead><tr><th>Nama Checking</th><th>Kondisi</th></tr></thead><tbody>';
            if (total === 0) {
                html += '<tr><td colspan="2">Belum ada data.</td></tr>';
            } else {
                ['BAIK', 'KURANG BAIK', 'BURUK'].forEach(function (status) {
                    (rows[status] || []).forEach(function (row) {
                        var noteHtml = row.keterangan ? '<br><small class="routine-recap-note">' + row.keterangan + '</small>' : '';
                        html += '<tr><td><strong>' + (row.item_name || '-') + '</strong>' + noteHtml + '</td><td><span class="routine-badge routine-badge--' + status.toLowerCase().replace(/\s+/g, '-') + '">' + status + '</span></td></tr>';
                    });
                });
            }
            html += '</tbody></table></section>';
        });
        body.innerHTML = hasAny ? html : emptyHtml();
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    document.querySelectorAll('.js-open-routine-recap').forEach(function (button) {
        button.addEventListener('click', function () {
            renderDate(button.getAttribute('data-recap-date') || '');
        });
    });

    function closeModal() {
        if (!modal) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.js-close-routine-recap').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
})();
</script>

<script>
(function () {
    var input = document.querySelector('.js-routine-live-search');
    if (!input) return;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.routine-monitoring-row'));
    var emptyStates = [];
    document.querySelectorAll('.routine-matrix-card').forEach(function (card) {
        var tbody = card.querySelector('.routine-matrix-table tbody');
        if (!tbody) return;
        var message = document.createElement('tr');
        message.className = 'routine-live-empty-row';
        message.hidden = true;
        var dayCount = card.querySelectorAll('.routine-matrix-table thead th').length || 1;
        message.innerHTML = '<td class="routine-empty-row" colspan="' + dayCount + '">Tidak ada list monitoring yang cocok.</td>';
        tbody.appendChild(message);
        emptyStates.push({card: card, message: message});
    });
    function applyLiveSearch() {
        var keyword = (input.value || '').toLowerCase().trim();
        rows.forEach(function (row) {
            var haystack = row.getAttribute('data-routine-search') || row.textContent.toLowerCase();
            row.hidden = keyword !== '' && haystack.indexOf(keyword) === -1;
        });
        emptyStates.forEach(function (state) {
            var visible = state.card.querySelectorAll('.routine-monitoring-row:not([hidden])').length;
            state.message.hidden = visible !== 0;
        });
    }
    input.addEventListener('input', applyLiveSearch);
    applyLiveSearch();
})();
</script>
