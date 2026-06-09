<?php
$isAdminSpmt = !empty($data['is_admin_spmt']);
$divisionRows = $data['division_management_rows'] ?? [];
$otherCards = $data['other_category_cards'] ?? [];
?>
<div class="inventory-division-page">
    <div class="list-page-header list-page-header--simple" style="margin-bottom:20px;">
        <h1>DATA INVENTARIS</h1>
        <div class="list-page-header__line"></div>
        <?php if ($isAdminSpmt): ?>
            <div class="division-admin-toolbar">
                <button class="division-admin-open" type="button" data-open-division-panel="1">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Kelola Divisi &amp; Database</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php
    /* Helper closure — render kartu divisi agar tidak duplikasi */
    $renderCards = function(array $cards): void {
        foreach ($cards as $card):
            $isDisabled = !empty($card['is_disabled']);
            if ($isDisabled): ?>
                <div class="category-card category-card--inventaris category-card--disabled" aria-disabled="true">
                    <span class="category-card__icon"><i class="<?= e($card['icon']); ?>"></i></span>
                    <span class="category-card__label"><?= nl2br(e($card['label'])); ?></span>
                    <?php if (!empty($card['sub_label'])): ?>
                        <span class="category-card__sub"><?= e($card['sub_label']); ?></span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <a href="<?= e($card['route_url'] ?? routeTo('inventaris-detail')); ?>" class="category-card category-card--inventaris">
                    <span class="category-card__icon"><i class="<?= e($card['icon']); ?>"></i></span>
                    <span class="category-card__label"><?= nl2br(e($card['label'])); ?></span>
                    <?php if (!empty($card['sub_label'])): ?>
                        <span class="category-card__sub"><?= e($card['sub_label']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif;
        endforeach;
    };
    $cards = $data['category_cards'] ?? [];
    ?>

    <!-- ====== SEKSI 1: INVENTARIS PC ====== -->
    <div class="inv-section">
        <div class="inv-section__header">
            <span class="inv-section__icon inv-section__icon--pc">
                <i class="fa-solid fa-desktop"></i>
            </span>
            <div>
                <h2 class="inv-section__title">Inventaris PC</h2>
                <p class="inv-section__subtitle">PC & perangkat yang terhubung (Mouse, Keyboard, Monitor, dll.) per divisi.</p>
            </div>
        </div>
        <div class="category-grid category-grid--inventaris">
            <?php $renderCards($cards); ?>
        </div>
    </div>

    <!-- ====== SEKSI 2: INVENTARIS LAIN ====== -->
    <div class="inv-section">
        <div class="inv-section__header">
            <span class="inv-section__icon inv-section__icon--other">
                <i class="fa-solid fa-box-open"></i>
            </span>
            <div>
                <h2 class="inv-section__title">Inventaris Lain</h2>
                <p class="inv-section__subtitle">Barang mandiri per divisi &mdash; Proyektor, Printer, Speaker, UPS, dan sejenisnya.</p>
            </div>
        </div>
        <?php if (!empty($otherCards)): ?>
            <div class="category-grid category-grid--inventaris">
                <?php $renderCards($otherCards); ?>
            </div>
        <?php else: ?>
            <div class="inv-section__empty">
                <i class="fa-solid fa-box-open inv-section__empty-icon"></i>
                <p>Belum ada barang mandiri yang ditambahkan.</p>
                <a href="index.php?page=inventory-other" class="inv-section__empty-link">
                    <i class="fa-solid fa-plus"></i> Tambah Inventaris Lain
                </a>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .inv-section__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 36px 20px;
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 14px;
        color: #64748b;
        text-align: center;
        margin-top: 4px;
    }
    .inv-section__empty-icon {
        font-size: 2rem;
        color: #cbd5e1;
    }
    .inv-section__empty p {
        margin: 0;
        font-size: .88rem;
    }
    .inv-section__empty-link {
        font-size: .83rem;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        padding: 6px 16px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        transition: background .15s;
    }
    .inv-section__empty-link:hover { background: #dbeafe; }
    </style>


    <?php if ($isAdminSpmt): ?>
    <!-- ====== MODAL: KELOLA DIVISI / DATABASE ====== -->
    <section class="division-admin-panel" id="divisionAdminPanel" aria-label="Kelola divisi inventaris" aria-hidden="true">
        <div class="division-admin-dialog" role="dialog" aria-modal="true" aria-labelledby="divisionAdminTitle">

            <!-- Header -->
            <div class="da-header">
                <div class="da-header__info">
                    <h2 id="divisionAdminTitle"><i class="fa-solid fa-database" style="font-size:.85em;color:#2A66A5;margin-right:8px;"></i>Kelola Divisi / Database</h2>
                    <p>Tambah divisi baru atau kelola divisi yang sudah ada.</p>
                </div>
                <button class="da-header__close" type="button" data-close-division-panel="1" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Tab Nav -->
            <div class="da-tab-nav">
                <button class="da-tab-btn is-active" data-da-tab="add" id="daTabBtnAdd">
                    <i class="fa-solid fa-plus"></i> Tambah Divisi
                </button>
                <button class="da-tab-btn" data-da-tab="list" id="daTabBtnList">
                    <i class="fa-solid fa-list-ul"></i> Daftar Divisi
                    <span class="da-tab-badge"><?= count($divisionRows); ?></span>
                </button>
            </div>

            <!-- ====== TAB PANEL: TAMBAH DIVISI ====== -->
            <div class="da-tab-panel" id="daPanelAdd">
                <form class="da-add-form" method="post" action="index.php?page=data-inventaris">
                    <input type="hidden" name="division_action" value="add">

                    <div class="da-add-form__grid">
                        <div class="da-field da-field--full">
                            <label class="da-field__label" for="da_division_label">
                                Nama Divisi <span style="color:#e53e3e">*</span>
                            </label>
                            <input id="da_division_label" type="text" name="division_label"
                                   class="da-field__input da-field__input--lg"
                                   placeholder="Contoh: DIVISI KOMERSIAL" required autocomplete="off">
                        </div>
                        <div class="da-field">
                            <label class="da-field__label" for="da_sheet_sumber">Sumber</label>
                            <select id="da_sheet_sumber" name="sheet_sumber" class="da-field__input">
                                <option value="SPMT">SPMT</option>
                                <option value="SUBREG">SUBREG</option>
                            </select>
                        </div>
                        <div class="da-field">
                            <label class="da-field__label" for="da_division_code">
                                Kode Divisi <span class="da-field__hint">(opsional)</span>
                            </label>
                            <input id="da_division_code" type="text" name="division_code"
                                   class="da-field__input" placeholder="Otomatis">
                        </div>
                        <div class="da-field da-field--full">
                            <label class="da-field__label" for="da_db_name">
                                Nama Database
                                <span class="da-field__hint"> — opsional, dibuat otomatis dari nama divisi</span>
                            </label>
                            <input id="da_db_name" type="text" name="inventory_db_name"
                                   class="da-field__input" placeholder="Otomatis">
                        </div>
                    </div>

                    <div class="da-add-form__footer">
                        <button class="da-submit-btn" type="submit">
                            <i class="fa-solid fa-plus"></i> Tambah Divisi
                        </button>
                    </div>
                </form>
            </div>

            <!-- ====== TAB PANEL: DAFTAR DIVISI ====== -->
            <div class="da-tab-panel" id="daPanelList" hidden>
                <div class="da-list-header">
                    <div class="da-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="divisionAdminSearch" class="da-search-input"
                               placeholder="Cari nama divisi atau database..." autocomplete="off">
                    </div>
                </div>

                <div class="da-table-scroll">
                    <table class="da-table">
                        <thead>
                            <tr>
                                <th>Nama Divisi</th>
                                <th>Database</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="divisionAdminTable">
                            <?php foreach ($divisionRows as $row): ?>
                                <?php
                                $rowId        = (string) ($row['id'] ?? '');
                                $updateFormId = 'daForm' . preg_replace('/[^0-9]/', '', $rowId);
                                $src          = strtoupper((string) ($row['sheet_sumber'] ?? 'SPMT'));
                                $isActive     = !empty($row['is_active']);
                                $dbName       = $row['inventory_db_name'] ?? '-';
                                $divLabel     = $row['division_label'] ?? '';
                                ?>
                                <tr class="da-row" data-division-row>
                                    <!-- Nama Divisi (editable) -->
                                    <td class="da-row__name">
                                        <form id="<?= e($updateFormId); ?>" method="post"
                                              action="index.php?page=data-inventaris" style="margin:0;">
                                            <input type="hidden" name="division_action" value="update">
                                            <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                            <input type="text" name="division_label"
                                                   class="da-inline-input"
                                                   value="<?= e($divLabel); ?>" required>
                                        </form>
                                    </td>
                                    <!-- Database -->
                                    <td class="da-row__db">
                                        <span class="da-db-chip" title="<?= e($dbName); ?>"><?= e($dbName); ?></span>
                                    </td>
                                    <!-- Sumber (editable) -->
                                    <td class="da-row__src">
                                        <select class="da-inline-select" form="<?= e($updateFormId); ?>" name="sheet_sumber">
                                            <option value="SPMT"   <?= $src === 'SPMT'   ? 'selected' : ''; ?>>SPMT</option>
                                            <option value="SUBREG" <?= $src === 'SUBREG' ? 'selected' : ''; ?>>SUBREG</option>
                                        </select>
                                    </td>
                                    <!-- Status -->
                                    <td class="da-row__status">
                                        <span class="da-status-badge <?= $isActive ? 'da-status-badge--on' : 'da-status-badge--off'; ?>">
                                            <?= $isActive ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <!-- Aksi: icon buttons -->
                                    <td class="da-row__actions">
                                        <!-- Simpan -->
                                        <button type="submit" form="<?= e($updateFormId); ?>"
                                                class="da-action-btn da-action-btn--save" title="Simpan perubahan">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                        <!-- Toggle Aktif / Nonaktif -->
                                        <?php if ($isActive): ?>
                                            <form method="post" action="index.php?page=data-inventaris"
                                                  class="da-inline-form"
                                                  onsubmit="return confirm('Nonaktifkan divisi ini dari tampilan?');">
                                                <input type="hidden" name="division_action" value="deactivate">
                                                <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                                <button type="submit" class="da-action-btn da-action-btn--off" title="Nonaktifkan">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="index.php?page=data-inventaris"
                                                  class="da-inline-form"
                                                  onsubmit="return confirm('Aktifkan kembali divisi ini?');">
                                                <input type="hidden" name="division_action" value="activate">
                                                <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                                <button type="submit" class="da-action-btn da-action-btn--on" title="Aktifkan">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <!-- Hapus -->
                                        <form method="post" action="index.php?page=data-inventaris"
                                              class="da-inline-form"
                                              onsubmit="return confirm('Hapus divisi ini? Database <?= e(addslashes($dbName)); ?> dan seluruh isinya akan ikut terhapus permanen.');">
                                            <input type="hidden" name="division_action" value="delete">
                                            <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                            <button type="submit" class="da-action-btn da-action-btn--delete" title="Hapus permanen">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- end daPanelList -->

        </div><!-- end division-admin-dialog -->
    </section>
    <?php endif; ?>
</div>

<?php if ($isAdminSpmt): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel   = document.getElementById('divisionAdminPanel');
    var openers = document.querySelectorAll('[data-open-division-panel]');
    var closers = document.querySelectorAll('[data-close-division-panel]');

    // Open / close
    openers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!panel) return;
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('division-overlay-open');
            var first = panel.querySelector('#da_division_label');
            if (first) setTimeout(function () { first.focus(); }, 80);
        });
    });
    closers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            closePanel();
        });
    });
    if (panel) {
        panel.addEventListener('click', function (e) {
            if (e.target === panel) closePanel();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) closePanel();
        });
    }
    function closePanel() {
        if (!panel) return;
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('division-overlay-open');
    }

    // Tab switching
    var tabBtns   = document.querySelectorAll('.da-tab-btn');
    var tabPanels = document.querySelectorAll('.da-tab-panel');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.dataset.daTab;
            tabBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            tabPanels.forEach(function (p) {
                p.hidden = (p.id !== 'daPanel' + target.charAt(0).toUpperCase() + target.slice(1));
            });
        });
    });

    // Search / filter
    var search = document.getElementById('divisionAdminSearch');
    var rows   = document.querySelectorAll('[data-division-row]');
    if (search) {
        search.addEventListener('input', function () {
            var q = search.value.toLowerCase().trim();
            rows.forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
            });
        });
    }
});
</script>
<?php endif; ?>
