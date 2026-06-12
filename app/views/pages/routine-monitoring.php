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

<style>
body.modal-open .routine-manager-modal,
body.has-modal-open .routine-manager-modal {
    overflow: hidden !important;
}
.routine-manage-list--modal {
    max-height: none !important;
    overflow: visible !important;
    padding-right: 0 !important;
}
</style>

<div class="routine-page routine-page--matrix">
    <div class="detail-header detail-header--single-title routine-header" style="position: relative; justify-content: center;">
        <div>
            <h1>ROUTINE MONITORING</h1>
        </div>
        <?php if ($canManageRoutineItems): ?>
            <button type="button" class="btn btn--secondary routine-manage-link js-open-category-modal" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); margin: 0;"><i class="fa-solid fa-list-check"></i> Kelola Kategori</button>
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
        <?php
        $manageItemsByCategory = [];
        foreach ($categoryMeta as $group => $meta) { $manageItemsByCategory[$group] = []; }
        foreach ($manageItems as $manageItem) {
            $manageGroup = strtoupper((string) ($manageItem['item_group'] ?? 'GATE'));
            if ($manageGroup === 'UMUM' || !isset($manageItemsByCategory[$manageGroup])) { continue; }
            $manageItemsByCategory[$manageGroup][] = $manageItem;
        }
        ?>

        <!-- Modal Kelola Kategori (Kelola List Checking) -->
        <div class="routine-manager-modal" id="routineCategoryManagerModal" hidden aria-hidden="true">
            <div class="routine-manager-dialog" role="dialog" aria-modal="true" aria-labelledby="categoryManagerTitle" style="max-width: 780px;">
                <div class="routine-manager-card__head routine-manager-modal__head">
                    <div>
                        <h2 id="categoryManagerTitle">Kelola List Checking</h2>
                    </div>
                    <button type="button" class="icon-round js-close-category-modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="routine-manager-scroll">
                    <!-- Kategori -->
                    <section class="routine-manager-section" style="padding-bottom: 8px;">
                        <div class="routine-manager-section__title"><h3>Kategori</h3></div>
                        
                        <!-- Form Tambah Kategori Baru -->
                        <form class="routine-add-form routine-manage-row--category" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                            <input type="hidden" name="action" value="add_routine_category">
                            <label class="routine-manage-row__name" style="grid-column: 1 / span 2;">
                                <span>Nama Kategori</span>
                                <input type="text" name="category_name" placeholder="Contoh: GATE" required>
                            </label>
                            <div class="routine-manage-row__actions">
                                <button type="submit" class="btn btn--primary routine-btn-lg" style="background: #0f2440; color: #fff;"><i class="fa-solid fa-plus"></i> Tambah Kategori</button>
                            </div>
                        </form>

                        <!-- List Kategori yang Ada -->
                        <div class="routine-manage-list" style="margin-top: 16px; max-height: min(450px, 50vh);">
                            <?php foreach ($manageCategories as $catRow): ?>
                                <?php
                                $categoryId = (int) $catRow['id'];
                                $groupName = strtoupper((string) ($catRow['category_name'] ?? ''));
                                $categoryActive = ((int) ($catRow['is_active'] ?? 1) === 1);
                                ?>
                                <form class="routine-manage-row routine-manage-row--category <?= $categoryActive ? '' : 'is-inactive'; ?>" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                    <input type="hidden" name="action" value="update_routine_category">
                                    <input type="hidden" name="category_id" value="<?= $categoryId; ?>">
                                    <label class="routine-manage-row__name">
                                        <span>Nama Kategori</span>
                                        <input type="text" name="category_name" value="<?= e($groupName); ?>" required>
                                    </label>
                                    <label class="routine-active-toggle">
                                        <input type="checkbox" name="is_active" value="1" <?= $categoryActive ? 'checked' : ''; ?>>
                                        <span>Aktif</span>
                                    </label>
                                    <div class="routine-manage-row__actions" style="flex-wrap: nowrap;">
                                        <button type="submit" class="btn btn--secondary routine-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                                        <button type="button" class="btn btn--danger routine-btn-lg js-confirm-delete" data-message="Hapus kategori ini? Semua item checking didalamnya akan dihapus permanen dari database." data-action="delete_routine_category"><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Modals Kelola per Kategori -->
        <?php foreach ($categoryMeta as $groupName => $meta): ?>
            <?php
            $catRow = null;
            foreach ($manageCategories as $c) {
                if (strtoupper((string) ($c['category_name'] ?? '')) === $groupName) {
                    $catRow = $c;
                    break;
                }
            }
            $categoryId = $catRow ? (int) $catRow['id'] : 0;
            $categoryActive = $catRow ? ((int) ($catRow['is_active'] ?? 1) === 1) : true;
            $categoryItems = $manageItemsByCategory[$groupName] ?? [];
            ?>
            <div class="routine-manager-modal js-category-manager-modal" id="routineItemManagerModal-<?= e($groupName); ?>" hidden aria-hidden="true" data-category="<?= e($groupName); ?>">
                <div class="routine-manager-dialog" role="dialog" aria-modal="true" aria-labelledby="routineManagerTitle-<?= e($groupName); ?>">
                    <div class="routine-manager-card__head routine-manager-modal__head">
                        <div>
                            <h2 id="routineManagerTitle-<?= e($groupName); ?>">Kelola Item Checking: <?= e($meta['label']); ?></h2>
                        </div>
                        <button type="button" class="icon-round js-close-routine-manager" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="routine-manager-scroll">
                        <!-- Item Checking -->
                        <section class="routine-manager-section">
                            <?php if ($groupName === 'CCTV'): ?>
                                <style>
                                    .routine-add-form--cctv {
                                        grid-template-columns: 48px minmax(150px, 1fr) 220px minmax(70px, 90px) 240px !important;
                                        gap: 12px !important;
                                    }
                                    .routine-manage-row--cctv-child {
                                        grid-template-columns: 48px minmax(150px, 1fr) 220px minmax(70px, 90px) 240px !important;
                                        gap: 12px !important;
                                    }
                                    .cctv-group-header:hover {
                                        background-color: #e2e8f0 !important;
                                    }
                                </style>
                                <form class="routine-add-form routine-add-form--modal routine-add-form--no-sort routine-add-form--cctv" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                    <input type="hidden" name="action" value="add_routine_item">
                                    <input type="hidden" name="item_group" value="CCTV">
                                    <input type="hidden" name="color" id="cctvAddLocationColor" value="#5B8DEF">
                                    
                                    <div style="width: 48px; height: 46px; align-self: end;"></div>
                                    <label class="routine-add-form__name"><span>Nama CCTV</span><input type="text" name="item_name" placeholder="Contoh: C049_CDC-CCC_01" required></label>
                                    <label class="routine-add-form__lokasi" style="display: flex; flex-direction: column; gap: 4px;">
                                        <span>Pilih Lokasi</span>
                                        
                                        <!-- Container 1: Default Dropdown View -->
                                        <div id="cctvLocSelectContainer" style="display: flex; gap: 6px; align-items: center; width: 100%;">
                                            <select name="lokasi" id="cctvAddLocationSelect" required style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 14px; font-size: 14px; flex-grow: 1; height: 46px; background-color: #fff;">
                                                <option value="">Pilih Lokasi</option>
                                                <?php 
                                                $uniqueLocs = [];
                                                foreach ($categoryItems as $item) {
                                                    $loc = trim((string) ($item['lokasi'] ?? ''));
                                                    if ($loc !== '' && !isset($uniqueLocs[$loc])) {
                                                        $uniqueLocs[$loc] = trim((string) ($item['color'] ?? '#5B8DEF'));
                                                    }
                                                }
                                                ksort($uniqueLocs);
                                                foreach ($uniqueLocs as $locName => $locCol):
                                                ?>
                                                    <option value="<?= e($locName); ?>" data-color="<?= e($locCol); ?>"><?= e($locName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" id="cctvAddLocationBtn" class="btn btn--secondary" style="height: 46px; width: 46px; min-width: auto; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0;" title="Tambah Lokasi Baru"><i class="fa-solid fa-plus"></i></button>
                                        </div>

                                        <!-- Container 2: Inline Quick Add View -->
                                        <div id="cctvLocQuickAddContainer" style="display: none; gap: 6px; align-items: center; width: 100%;">
                                            <input type="text" id="cctvNewLocName" placeholder="Ketik lokasi baru..." style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 14px; font-size: 14px; flex-grow: 1; height: 46px; background-color: #fff;">
                                            <div style="position: relative; width: 46px; height: 46px; border: 1px solid #cbd5e1; border-radius: 14px; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #fff; cursor: pointer;" title="Pilih warna lokasi">
                                                <input type="color" id="cctvNewLocColor" value="#5B8DEF" style="border: none; width: 100%; height: 100%; padding: 0; margin: 0; cursor: pointer; outline: none; background: transparent; position: absolute; transform: scale(1.4);">
                                            </div>
                                            <button type="button" id="cctvConfirmLocBtn" class="btn btn--primary" style="height: 46px; width: 46px; min-width: auto; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0; background-color: #10b981; border-color: #10b981;" title="Simpan Lokasi"><i class="fa-solid fa-check"></i></button>
                                            <button type="button" id="cctvCancelLocBtn" class="btn btn--danger" style="height: 46px; width: 46px; min-width: auto; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0;" title="Batal"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    </label>
                                    <div style="width: 100%; height: 46px; align-self: end;"></div>
                                    <button type="submit" class="btn btn--primary routine-btn-lg" style="align-self: end; height: 46px; min-width: auto !important; width: 100%; margin: 0; white-space: nowrap;"><i class="fa-solid fa-plus"></i> Tambah CCTV</button>
                                </form>
                                <div class="routine-manage-list routine-manage-list--modal routine-manage-list--form-cards" style="margin-top: 16px;">
                                    <?php if (empty($categoryItems)): ?>
                                        <div class="routine-empty-mini">Belum ada item checking.</div>
                                    <?php else: ?>
                                        <div class="routine-manage-row-header routine-manage-row-header--cctv" style="display: grid; grid-template-columns: 48px minmax(150px, 1fr) 220px minmax(70px, 90px) 240px !important; gap: 12px; padding: 8px 12px 4px; font-weight: 800; color: #6d84a4; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid transparent; border-bottom-color: rgba(42, 102, 165, 0.08); margin-bottom: 8px;">
                                            <div></div>
                                            <div>Nama CCTV</div>
                                            <div>Pilih Lokasi</div>
                                            <div style="text-align: center;">Aktif</div>
                                            <div style="text-align: center;">Aksi</div>
                                        </div>
                                        
                                        <?php
                                        // Group items by location
                                        $groupedLocItems = [];
                                        $locColors = [];
                                        foreach ($categoryItems as $item) {
                                            $loc = trim((string) ($item['lokasi'] ?? 'UMUM'));
                                            if ($loc === '') { $loc = 'UMUM'; }
                                            if (!isset($groupedLocItems[$loc])) {
                                                $groupedLocItems[$loc] = [];
                                            }
                                            $groupedLocItems[$loc][] = $item;
                                            $locColors[$loc] = trim((string) ($item['color'] ?? '#5B8DEF'));
                                        }
                                        ksort($groupedLocItems);
                                        ?>
                                        
                                        <?php foreach ($groupedLocItems as $locName => $locCams): ?>
                                            <?php $locCol = $locColors[$locName] ?? '#5B8DEF'; ?>
                                            <!-- Group Accordion Header -->
                                            <div class="cctv-group-header" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background-color: #f8fafc; border-radius: 14px; margin-top: 12px; margin-bottom: 8px; border: 1px solid #cbd5e1; cursor: pointer; transition: background-color 0.15s;" onclick="toggleCctvGroup(this)">
                                                <span class="cctv-group-toggle-icon" style="font-size: 14px; color: #64748b; width: 16px; text-align: center; display: inline-block; transition: transform 0.2s;"><i class="fa-solid fa-chevron-down"></i></span>
                                                <strong style="font-size: 14px; color: #1e293b; letter-spacing: 0.3px; text-transform: uppercase;"><?= e($locName); ?></strong>
                                                <div style="margin-left: auto; display: flex; align-items: center; gap: 8px;">
                                                    <span style="display: inline-block; width: 18px; height: 18px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1); background-color: <?= e($locCol); ?>; vertical-align: middle;" title="Warna: <?= e($locCol); ?>"></span>
                                                    <span class="badge" style="background-color: #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;"><?= count($locCams); ?> unit</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Children Container -->
                                            <div class="cctv-group-children" style="display: block; margin-bottom: 16px; padding-left: 14px; border-left: 2px solid #e2e8f0;">
                                                <?php foreach ($locCams as $manageItem): ?>
                                                    <?php $manageId = (int) ($manageItem['id'] ?? 0); $manageActive = (int) ($manageItem['is_active'] ?? 1) === 1; ?>
                                                    <form class="routine-manage-row routine-manage-row--no-sort routine-manage-row--cctv-child <?= $manageActive ? '' : 'is-inactive'; ?>" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>" style="display: grid; align-items: center; margin-bottom: 8px;">
                                                        <input type="hidden" name="action" value="update_routine_item">
                                                        <input type="hidden" name="item_id" value="<?= $manageId; ?>">
                                                        <input type="hidden" name="item_group" value="CCTV">
                                                        <input type="hidden" name="color" value="<?= e($locCol); ?>">
                                                        
                                                        <!-- Indentation tree pointer icon -->
                                                        <div style="display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; color: #94a3b8; font-size: 14px; height: 46px; align-self: end;">
                                                            <i class="fa-solid fa-arrow-turn-up fa-rotate-90"></i>
                                                        </div>
                                                        
                                                        <label class="routine-manage-row__name" style="margin: 0;"><input type="text" name="item_name" value="<?= e((string) ($manageItem['item_name'] ?? '')); ?>" required></label>
                                                        <label class="routine-manage-row__lokasi" style="margin: 0;">
                                                            <select name="lokasi" required style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 14px; font-size: 14px; width: 100%; height: 46px; background-color: #fff;">
                                                                <?php foreach ($uniqueLocs as $lName => $lCol): ?>
                                                                    <option value="<?= e($lName); ?>" data-color="<?= e($lCol); ?>" <?= $lName === $locName ? 'selected' : ''; ?>><?= e($lName); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label class="routine-active-toggle" style="display: flex; align-items: center; justify-content: center; height: 46px; align-self: end; margin: 0; padding: 0;"><input type="checkbox" name="is_active" value="1" <?= $manageActive ? 'checked' : ''; ?>></label>
                                                        <div class="routine-manage-row__actions" style="flex-wrap: nowrap; margin: 0; display: flex; gap: 8px;">
                                                            <button type="submit" class="btn btn--secondary routine-btn-lg" style="margin: 0; flex: 1;"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                                                            <button type="button" class="btn btn--danger routine-btn-lg js-confirm-delete" data-message="Hapus kamera CCTV ini?" data-action="delete_routine_item" style="margin: 0; flex: 1;"><i class="fa-solid fa-trash"></i> Hapus</button>
                                                        </div>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <script>
                                (function () {
                                    var cctvModal = document.getElementById('routineItemManagerModal-CCTV');
                                    if (!cctvModal) return;

                                    // Toggle collapse/expand of accordion children groups
                                    window.toggleCctvGroup = function (header) {
                                        var icon = header.querySelector('.cctv-group-toggle-icon i');
                                        var children = header.nextElementSibling;
                                        if (children && children.classList.contains('cctv-group-children')) {
                                            var isHidden = children.style.display === 'none';
                                            children.style.display = isHidden ? 'block' : 'none';
                                            if (icon) {
                                                icon.className = isHidden ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-right';
                                            }
                                        }
                                    };

                                    // Dropdown change updates hidden color field
                                    var locSelect = document.getElementById('cctvAddLocationSelect');
                                    var colorHidden = document.getElementById('cctvAddLocationColor');
                                    var addLocBtn = document.getElementById('cctvAddLocationBtn');

                                    // Elements for Inline Quick Add
                                    var locSelectContainer = document.getElementById('cctvLocSelectContainer');
                                    var locQuickAddContainer = document.getElementById('cctvLocQuickAddContainer');
                                    var newLocNameInput = document.getElementById('cctvNewLocName');
                                    var newLocColorInput = document.getElementById('cctvNewLocColor');
                                    var confirmLocBtn = document.getElementById('cctvConfirmLocBtn');
                                    var cancelLocBtn = document.getElementById('cctvCancelLocBtn');

                                    if (locSelect && colorHidden) {
                                        locSelect.addEventListener('change', function () {
                                            var selectedOpt = locSelect.options[locSelect.selectedIndex];
                                            var color = selectedOpt ? selectedOpt.getAttribute('data-color') : '#5B8DEF';
                                            colorHidden.value = color || '#5B8DEF';
                                        });
                                    }

                                    // Inline Quick Add Toggle
                                    if (addLocBtn && locSelectContainer && locQuickAddContainer && newLocNameInput) {
                                        addLocBtn.addEventListener('click', function () {
                                            locSelectContainer.style.display = 'none';
                                            locQuickAddContainer.style.display = 'flex';
                                            newLocNameInput.focus();
                                        });
                                    }

                                    // Inline Quick Add Cancel
                                    if (cancelLocBtn && locSelectContainer && locQuickAddContainer && newLocNameInput && newLocColorInput) {
                                        cancelLocBtn.addEventListener('click', function () {
                                            locQuickAddContainer.style.display = 'none';
                                            locSelectContainer.style.display = 'flex';
                                            newLocNameInput.value = '';
                                            newLocColorInput.value = '#5B8DEF';
                                        });
                                    }

                                    // Inline Quick Add Confirm
                                    if (confirmLocBtn && locSelectContainer && locQuickAddContainer && newLocNameInput && newLocColorInput && locSelect && colorHidden) {
                                        confirmLocBtn.addEventListener('click', function () {
                                            var newLoc = newLocNameInput.value.trim().toUpperCase();
                                            if (newLoc === '') {
                                                alert('Nama lokasi baru tidak boleh kosong!');
                                                newLocNameInput.focus();
                                                return;
                                            }

                                            var newCol = newLocColorInput.value || '#5B8DEF';
                                            var exists = false;
                                            for (var i = 0; i < locSelect.options.length; i++) {
                                                if (locSelect.options[i].value.toUpperCase() === newLoc) {
                                                    locSelect.selectedIndex = i;
                                                    exists = true;
                                                    break;
                                                }
                                            }

                                            if (!exists) {
                                                var opt = document.createElement('option');
                                                opt.value = newLoc;
                                                opt.text = newLoc;
                                                opt.setAttribute('data-color', newCol);
                                                opt.selected = true;
                                                locSelect.add(opt);
                                            }

                                            // Trigger change event to update hidden input & other logic
                                            var evt = document.createEvent('HTMLEvents');
                                            evt.initEvent('change', false, true);
                                            locSelect.dispatchEvent(evt);

                                            // Reset and switch UI back
                                            newLocNameInput.value = '';
                                            newLocColorInput.value = '#5B8DEF';
                                            locQuickAddContainer.style.display = 'none';
                                            locSelectContainer.style.display = 'flex';
                                        });
                                    }

                                    // Prevent Enter key in text input from submitting main form
                                    if (newLocNameInput) {
                                        newLocNameInput.addEventListener('keydown', function (e) {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                if (confirmLocBtn) {
                                                    confirmLocBtn.click();
                                                }
                                            }
                                        });
                                    }

                                    // Sync color hidden inputs when child location select changes
                                    cctvModal.addEventListener('change', function (e) {
                                        var target = e.target;
                                        if (target && target.name === 'lokasi' && target.tagName === 'SELECT') {
                                            var form = target.closest('form');
                                            if (form && form.classList.contains('routine-manage-row--cctv-child')) {
                                                var selectedOpt = target.options[target.selectedIndex];
                                                var colorVal = selectedOpt ? selectedOpt.getAttribute('data-color') : '#5B8DEF';
                                                var colorHiddenInput = form.querySelector('input[name="color"]');
                                                if (colorHiddenInput) {
                                                    colorHiddenInput.value = colorVal || '#5B8DEF';
                                                }
                                            }
                                        }
                                    });
                                })();
                                </script>
                            <?php else: ?>
                                <form class="routine-add-form routine-add-form--modal routine-add-form--no-sort" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                    <input type="hidden" name="action" value="add_routine_item">
                                    <input type="hidden" name="item_group" value="<?= e($groupName); ?>">
                                    <label class="routine-add-form__name"><span>Nama Checking</span><input type="text" name="item_name" placeholder="Contoh: <?= e($groupName); ?> 1" required></label>
                                    <button type="submit" class="btn btn--primary routine-btn-lg"><i class="fa-solid fa-plus"></i> Tambah Item</button>
                                </form>
                                <div class="routine-manage-list routine-manage-list--modal routine-manage-list--form-cards" style="margin-top: 16px;">
                                    <?php if (empty($categoryItems)): ?>
                                        <div class="routine-empty-mini">Belum ada item checking.</div>
                                    <?php else: ?>
                                        <?php foreach ($categoryItems as $manageItem): ?>
                                            <?php $manageId = (int) ($manageItem['id'] ?? 0); $manageActive = (int) ($manageItem['is_active'] ?? 1) === 1; ?>
                                            <form class="routine-manage-row routine-manage-row--no-sort routine-manage-row--category-field <?= $manageActive ? '' : 'is-inactive'; ?>" method="post" action="index.php?page=routine-monitoring&amp;<?= e($returnQuery); ?>">
                                                <input type="hidden" name="action" value="update_routine_item">
                                                <input type="hidden" name="item_id" value="<?= $manageId; ?>">
                                                <input type="hidden" name="item_group" value="<?= e($groupName); ?>">
                                                <label class="routine-manage-row__name"><span>Nama Checking</span><input type="text" name="item_name" value="<?= e((string) ($manageItem['item_name'] ?? '')); ?>" required></label>
                                                <label class="routine-active-toggle"><input type="checkbox" name="is_active" value="1" <?= $manageActive ? 'checked' : ''; ?>><span>Aktif</span></label>
                                                <div class="routine-manage-row__actions" style="flex-wrap: nowrap;">
                                                    <button type="submit" class="btn btn--secondary routine-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                                                    <button type="button" class="btn btn--danger routine-btn-lg js-confirm-delete" data-message="Hapus item checking ini secara permanen dari database?" data-action="delete_routine_item"><i class="fa-solid fa-trash"></i> Hapus</button>
                                                </div>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
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
                    <div class="routine-card-actions" style="display: flex; gap: 8px; align-items: center;">
                        <?php if ($canManageRoutineItems): ?>
                            <button type="button" class="btn btn--secondary routine-manage-link js-open-category-manager" data-category="<?= e($groupName); ?>"><i class="fa-solid fa-pen"></i> Kelola <?= e($meta['label']); ?></button>
                        <?php endif; ?>
                        <button type="submit" name="save_category" value="<?= e($groupName); ?>" class="btn btn--primary routine-save-btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Checklist <?= e($meta['label']); ?></button>
                        <?php if ($groupName === 'CCTV'): ?>
                            <a href="index.php?page=laporan&amp;action=report_export&amp;type=routine&amp;format=xlsx&amp;report_month=<?= e($monthValue); ?>&amp;report_year=<?= e($yearValue); ?>&amp;report_category=<?= urlencode($groupName); ?>" class="btn routine-export-excel-btn"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="routine-matrix-table-wrap">
                    <table class="routine-matrix-table <?= $groupName === 'CCTV' ? 'routine-matrix-table--cctv' : ''; ?>">
                        <thead>
                            <tr>
                                <?php if ($groupName === 'CCTV'): ?>
                                    <th class="sticky-col sticky-col--lokasi">Lokasi</th>
                                    <th class="sticky-col sticky-col--nama">Nama CCTV</th>
                                <?php else: ?>
                                    <th class="sticky-col">List Monitoring</th>
                                <?php endif; ?>
                                <?php foreach ($days as $day): ?>
                                    <th><?= e((string) ($day['day'] ?? '')); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td class="routine-empty-row" colspan="<?= count($days) + ($groupName === 'CCTV' ? 2 : 1); ?>">Belum ada item checking.</td></tr>
                            <?php else: ?>
                                <?php
                                $cctvRowspans = [];
                                $renderedLocations = [];
                                if ($groupName === 'CCTV') {
                                    foreach ($rows as $rowItem) {
                                        $loc = (string) ($rowItem['lokasi'] ?? '-');
                                        if (!isset($cctvRowspans[$loc])) {
                                            $cctvRowspans[$loc] = 0;
                                        }
                                        $cctvRowspans[$loc]++;
                                    }
                                }
                                ?>
                                <?php foreach ($rows as $item): ?>
                                    <?php
                                    $itemId = (int) ($item['id'] ?? 0);
                                    $loc = (string) ($item['lokasi'] ?? '-');
                                    ?>
                                    <tr class="routine-monitoring-row" data-lokasi="<?= e($loc); ?>" data-routine-search="<?= e(strtolower((string) ($groupName . ' ' . $loc . ' ' . ($item['item_name'] ?? '')))); ?>">
                                        <?php if ($groupName === 'CCTV'): ?>
                                            <?php
                                            $isFirst = !in_array($loc, $renderedLocations, true);
                                            if ($isFirst) {
                                                $renderedLocations[] = $loc;
                                            }
                                            $rSpan = $cctvRowspans[$loc] ?? 1;
                                            ?>
                                            <td class="sticky-col sticky-col--lokasi routine-lokasi-cell" rowspan="<?= $rSpan; ?>" style="<?= $isFirst ? '' : 'display: none;'; ?> vertical-align: middle; text-align: center;"><span><?= e($loc); ?></span></td>
                                            <td class="sticky-col sticky-col--nama routine-item-name-cell"><strong><?= e((string) ($item['item_name'] ?? '-')); ?></strong></td>
                                        <?php else: ?>
                                            <td class="sticky-col routine-item-name-cell"><strong><?= e((string) ($item['item_name'] ?? '-')); ?></strong></td>
                                        <?php endif; ?>
                                        <?php foreach ($days as $day): ?>
                                            <?php
                                            $dateKey = (string) ($day['date'] ?? '');
                                            $cell = $item['calendar'][$dateKey] ?? ['condition_status' => ''];
                                            $selectedStatus = (string) ($cell['condition_status'] ?? '');
                                            $keteranganVal = (string) ($cell['keterangan'] ?? '');
                                            $needsNote = in_array($selectedStatus, ['KURANG BAIK', 'BURUK', 'OFF'], true);
                                            ?>
                                            <td>
                                                <?php if ($groupName === 'CCTV'): ?>
                                                    <?php
                                                    $btnClass = 'cctv-switch-btn--empty';
                                                    $btnLabel = '-';
                                                    if ($selectedStatus === 'ON') {
                                                        $btnClass = 'cctv-switch-btn--on';
                                                        $btnLabel = 'ON';
                                                    } elseif ($selectedStatus === 'OFF') {
                                                        $btnClass = 'cctv-switch-btn--off';
                                                        $btnLabel = 'OFF';
                                                    }
                                                    ?>
                                                    <div class="cctv-switch-container">
                                                        <input type="hidden" 
                                                               class="js-cctv-status-input" 
                                                               name="items[<?= $itemId; ?>][<?= e($dateKey); ?>][condition_status]" 
                                                               value="<?= e($selectedStatus); ?>">
                                                        <button type="button" 
                                                                class="cctv-switch-btn <?= $btnClass; ?>" 
                                                                onclick="toggleCctvStatus(this)">
                                                            <span class="cctv-switch-label"><?= $btnLabel; ?></span>
                                                            <span class="cctv-switch-knob"></span>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
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
                                                <?php endif; ?>
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
            var rows = groups[groupName] || {'BAIK': [], 'KURANG BAIK': [], 'BURUK': [], 'ON': [], 'OFF': []};
            var total = (rows['BAIK'] || []).length + (rows['KURANG BAIK'] || []).length + (rows['BURUK'] || []).length + (rows['ON'] || []).length + (rows['OFF'] || []).length;
            if (total > 0) {
                hasAny = true;
            }
            html += '<section class="routine-recap-section">';
            html += '<div class="routine-recap-section__head"><span><i class="' + meta.icon + '"></i> ' + meta.label + '</span><strong>' + total + ' item</strong></div>';
            html += '<table class="routine-recap-table"><thead><tr><th>Nama Checking</th><th>Kondisi</th></tr></thead><tbody>';
            if (total === 0) {
                html += '<tr><td colspan="2">Belum ada data.</td></tr>';
            } else {
                ['BAIK', 'KURANG BAIK', 'BURUK', 'ON', 'OFF'].forEach(function (status) {
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
    function updateCctvRowspans() {
        var cctvTable = document.querySelector('.routine-matrix-table--cctv');
        if (!cctvTable) return;
        
        var visibleRows = Array.prototype.slice.call(cctvTable.querySelectorAll('.routine-monitoring-row:not([hidden])'));
        
        // Group visible rows by location
        var groups = {};
        visibleRows.forEach(function (row) {
            var loc = row.getAttribute('data-lokasi') || '';
            if (!groups[loc]) {
                groups[loc] = [];
            }
            groups[loc].push(row);
        });
        
        // For each group, set the rowspan on the first visible row's lokasi cell
        // and hide the lokasi cell for the other visible rows
        for (var loc in groups) {
            if (!groups.hasOwnProperty(loc)) continue;
            var groupRows = groups[loc];
            groupRows.forEach(function (row, idx) {
                var cell = row.querySelector('.routine-lokasi-cell');
                if (!cell) return;
                
                if (idx === 0) {
                    cell.setAttribute('rowspan', groupRows.length);
                    cell.style.display = ''; // Show it
                } else {
                    cell.style.display = 'none'; // Hide it
                }
            });
        }
    }

    function applyLiveSearch() {
        var keyword = (input.value || '').toLowerCase().trim();
        rows.forEach(function (row) {
            var haystack = row.getAttribute('data-routine-search') || row.textContent.toLowerCase();
            row.hidden = keyword !== '' && haystack.indexOf(keyword) === -1;
        });
        
        updateCctvRowspans();
        
        emptyStates.forEach(function (state) {
            var visible = state.card.querySelectorAll('.routine-monitoring-row:not([hidden])').length;
            state.message.hidden = visible !== 0;
        });
    }
    input.addEventListener('input', applyLiveSearch);
    applyLiveSearch();
})();
</script>

<script>
function toggleCctvStatus(btn) {
    var container = btn.closest('.cctv-switch-container');
    var input = container.querySelector('.js-cctv-status-input');
    var label = btn.querySelector('.cctv-switch-label');
    var currentStatus = input.value;
    var newStatus = 'ON';
    
    if (currentStatus === 'ON') {
        newStatus = 'OFF';
    } else if (currentStatus === 'OFF') {
        newStatus = '';
    } else {
        newStatus = 'ON';
    }
    
    input.value = newStatus;
    
    btn.className = 'cctv-switch-btn';
    if (newStatus === 'ON') {
        btn.classList.add('cctv-switch-btn--on');
        label.textContent = 'ON';
    } else if (newStatus === 'OFF') {
        btn.classList.add('cctv-switch-btn--off');
        label.textContent = 'OFF';
    } else {
        btn.classList.add('cctv-switch-btn--empty');
        label.textContent = '-';
    }
}

function routineCellChange(select) {
    var status = select.value;
    var wrapper = select.closest('td');
    
    select.className = 'routine-cell-select routine-cell-select--' + status.toLowerCase().replace(/\s+/g, '-');
    if (status === '') {
        select.classList.add('routine-cell-select--empty');
    }
    
    var noteInput = wrapper.querySelector('.routine-cell-note');
    if (noteInput) {
        if (status === 'KURANG BAIK' || status === 'BURUK') {
            noteInput.classList.remove('routine-cell-note--hidden');
        } else if (noteInput.value === '') {
            noteInput.classList.add('routine-cell-note--hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Helper function to serialize the main checklist matrix and append it to a form
    function appendMatrixJsonToForm(targetForm) {
        var matrixForm = document.querySelector('.routine-matrix-form');
        if (!matrixForm) return;
        
        var items = {};
        var inputs = matrixForm.querySelectorAll('input[name^="items["], select[name^="items["]');
        
        inputs.forEach(function (el) {
            var name = el.getAttribute('name');
            if (!name) return;
            
            var matches = name.match(/^items\[(\d+)\]\[([^\]]+)\]\[([^\]]+)\]$/);
            if (matches) {
                var itemId = matches[1];
                var dateKey = matches[2];
                var field = matches[3];
                
                if (!items[itemId]) {
                    items[itemId] = {};
                }
                if (!items[itemId][dateKey]) {
                    items[itemId][dateKey] = {};
                }
                items[itemId][dateKey][field] = el.value;
            }
        });
        
        var hidden = targetForm.querySelector('input[name="matrix_json"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'matrix_json';
            targetForm.appendChild(hidden);
        }
        hidden.value = JSON.stringify(items);
    }

    // Intercept standard submit events for modal/management forms (excluding main matrix form)
    document.addEventListener('submit', function (event) {
        var submittedForm = event.target;
        var mainMatrixForm = document.querySelector('.routine-matrix-form');
        if (submittedForm && submittedForm !== mainMatrixForm && submittedForm.action && submittedForm.action.indexOf('page=routine-monitoring') !== -1) {
            appendMatrixJsonToForm(submittedForm);
        }
    });

    // Custom Confirm Dialog for Delete Buttons
    document.addEventListener('click', function (e) {
        var deleteBtn = e.target.closest('.js-confirm-delete');
        if (deleteBtn) {
            e.preventDefault();
            var form = deleteBtn.closest('form');
            if (!form) return;
            var message = deleteBtn.getAttribute('data-message') || 'Apakah Anda yakin ingin menghapus data ini?';
            var actionValue = deleteBtn.getAttribute('data-action');
            
            if (window.spmtConfirm) {
                window.spmtConfirm(message).then(function (confirmed) {
                    if (confirmed) {
                        if (actionValue) {
                            var existingAction = form.querySelector('input[name="action"]');
                            if (existingAction) {
                                existingAction.value = actionValue;
                            } else {
                                var hiddenAction = document.createElement('input');
                                hiddenAction.type = 'hidden';
                                hiddenAction.name = 'action';
                                hiddenAction.value = actionValue;
                                form.appendChild(hiddenAction);
                            }
                        }
                        // Save unsaved matrix checklist states on programmatic delete form submit
                        appendMatrixJsonToForm(form);
                        form.submit();
                    }
                });
            } else {
                if (confirm(message)) {
                    if (actionValue) {
                        var existingAction = form.querySelector('input[name="action"]');
                        if (existingAction) {
                            existingAction.value = actionValue;
                        } else {
                            var hiddenAction = document.createElement('input');
                            hiddenAction.type = 'hidden';
                            hiddenAction.name = 'action';
                            hiddenAction.value = actionValue;
                            form.appendChild(hiddenAction);
                        }
                    }
                    // Save unsaved matrix checklist states on programmatic delete form submit
                    appendMatrixJsonToForm(form);
                    form.submit();
                }
            }
        }
    });

    var form = document.querySelector('.routine-matrix-form');
    if (!form) return;
    
    form.addEventListener('submit', function (event) {
        if (form.querySelector('input[name="matrix_json"]')) {
            return;
        }
        
        event.preventDefault();
        
        var items = {};
        var inputs = form.querySelectorAll('input[name^="items["], select[name^="items["]');
        
        inputs.forEach(function (el) {
            var name = el.getAttribute('name');
            if (!name) return;
            
            var matches = name.match(/^items\[(\d+)\]\[([^\]]+)\]\[([^\]]+)\]$/);
            if (matches) {
                var itemId = matches[1];
                var dateKey = matches[2];
                var field = matches[3];
                
                if (!items[itemId]) {
                    items[itemId] = {};
                }
                if (!items[itemId][dateKey]) {
                    items[itemId][dateKey] = {};
                }
                items[itemId][dateKey][field] = el.value;
            }
        });
        
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'matrix_json';
        hidden.value = JSON.stringify(items);
        form.appendChild(hidden);
        
        var submitter = event.submitter;
        if (submitter && submitter.name && submitter.value) {
            var subHidden = document.createElement('input');
            subHidden.type = 'hidden';
            subHidden.name = submitter.name;
            subHidden.value = submitter.value;
            form.appendChild(subHidden);
        }
        
        inputs.forEach(function (el) {
            el.removeAttribute('name');
        });
        
        form.submit();
    });
});
</script>
