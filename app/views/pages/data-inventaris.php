<?php
$isAdminSpmt = !empty($data['is_admin_spmt']);
$divisionRows = $data['division_management_rows'] ?? [];
?>
<div class="inventory-division-page">
    <div class="list-page-header list-page-header--simple">
        <h1>DATA INVENTARIS</h1>
        <div class="list-page-header__line"></div>
        <?php if ($isAdminSpmt): ?>
            <div class="division-admin-toolbar">
                <button class="division-admin-open" type="button" data-open-division-panel="1">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Kelola Divisi & Database</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="category-grid category-grid--inventaris">
        <?php foreach ($data['category_cards'] as $card): ?>
            <?php $isDisabled = !empty($card['is_disabled']); ?>
            <?php if ($isDisabled): ?>
                <div class="category-card category-card--inventaris category-card--disabled" aria-disabled="true">
                    <span class="category-card__icon">
                        <i class="<?= e($card['icon']); ?>"></i>
                    </span>
                    <span class="category-card__label"><?= nl2br(e($card['label'])); ?></span>
                    <?php if (!empty($card['sub_label'])): ?>
                        <span class="category-card__sub"><?= e($card['sub_label']); ?></span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <a href="<?= e($card['route_url'] ?? routeTo('inventaris-detail')); ?>" class="category-card category-card--inventaris">
                    <span class="category-card__icon">
                        <i class="<?= e($card['icon']); ?>"></i>
                    </span>
                    <span class="category-card__label"><?= nl2br(e($card['label'])); ?></span>
                    <?php if (!empty($card['sub_label'])): ?>
                        <span class="category-card__sub"><?= e($card['sub_label']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($isAdminSpmt): ?>
        <section class="division-admin-panel" id="divisionAdminPanel" aria-label="Kelola divisi inventaris" aria-hidden="true">
            <div class="division-admin-dialog" role="dialog" aria-modal="true" aria-labelledby="divisionAdminTitle">
                <div class="division-admin-panel__head">
                    <div>
                        <h2 id="divisionAdminTitle">Kelola Divisi / Database</h2>
                        <p>Tambah divisi baru, buat database inventaris otomatis, atau edit nama divisi yang sudah ada.</p>
                    </div>
                    <button class="division-admin-panel__close" type="button" data-close-division-panel="1" aria-label="Tutup kelola divisi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="division-admin-panel__body">
                    <form class="division-admin-form division-admin-card-form" method="post" action="index.php?page=data-inventaris">
                        <input type="hidden" name="division_action" value="add">
                        <div class="division-admin-card-form__title">
                            <span class="division-admin-card-form__icon"><i class="fa-solid fa-plus"></i></span>
                            <div>
                                <h3>Tambah Divisi / DB</h3>
                                <p>Database dan tabel dibuat otomatis mengikuti struktur divisi lain.</p>
                            </div>
                        </div>
                        <div class="division-admin-form-grid">
                            <label class="division-admin-field division-admin-field--wide">
                                <span>Nama Divisi</span>
                                <input type="text" name="division_label" placeholder="Contoh: DIVISI KOMERSIAL" required>
                            </label>
                            <label class="division-admin-field">
                                <span>Sumber</span>
                                <select name="sheet_sumber">
                                    <option value="SPMT">SPMT</option>
                                    <option value="SUBREG">SUBREG</option>
                                </select>
                            </label>
                            <label class="division-admin-field">
                                <span>Kode Divisi</span>
                                <input type="text" name="division_code" placeholder="Opsional">
                            </label>
                            <label class="division-admin-field division-admin-field--db">
                                <span>Nama Database</span>
                                <input type="text" name="inventory_db_name" placeholder="Otomatis">
                            </label>
                        </div>
                        <p class="division-admin-form__hint">Field opsional akan dibuat otomatis dari nama divisi.</p>
                        <div class="division-admin-form-actions">
                            <button class="division-admin-submit" type="submit"><i class="fa-solid fa-plus"></i> Tambah Divisi</button>
                        </div>
                    </form>

                    <div class="division-admin-list division-admin-list--cards">
                        <div class="division-admin-list__top">
                            <div>
                                <h3>Edit Divisi Terdaftar</h3>
                                <p>Ubah nama divisi dan sumber. Nama database ditampilkan kecil sebagai referensi.</p>
                            </div>
                            <input type="search" id="divisionAdminSearch" placeholder="Cari divisi / database...">
                        </div>
                        <div class="division-edit-card-scroll" id="divisionAdminTable">
                            <?php foreach ($divisionRows as $row): ?>
                                <?php $rowId = (string) ($row['id'] ?? ''); $updateFormId = 'divisionUpdateForm' . preg_replace('/[^0-9]/', '', $rowId); $src = strtoupper((string) ($row['sheet_sumber'] ?? 'SPMT')); ?>
                                <article class="division-edit-card" data-division-row>
                                    <form id="<?= e($updateFormId); ?>" method="post" action="index.php?page=data-inventaris" class="division-edit-form">
                                        <input type="hidden" name="division_action" value="update">
                                        <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                        <div class="division-edit-form__main">
                                            <label class="division-admin-field division-admin-field--wide division-edit-name">
                                                <span>Nama Divisi</span>
                                                <input type="text" name="division_label" class="division-label-input" value="<?= e($row['division_label'] ?? ''); ?>" required>
                                            </label>
                                            <label class="division-admin-field division-edit-source">
                                                <span>Sumber</span>
                                                <select name="sheet_sumber">
                                                    <option value="SPMT" <?= $src === 'SPMT' ? 'selected' : ''; ?>>SPMT</option>
                                                    <option value="SUBREG" <?= $src === 'SUBREG' ? 'selected' : ''; ?>>SUBREG</option>
                                                </select>
                                            </label>
                                            <div class="division-db-pill">
                                                <span>Database</span>
                                                <strong><?= e($row['inventory_db_name'] ?? '-'); ?></strong>
                                                <small><?= e($row['division_code'] ?? ''); ?></small>
                                            </div>
                                            <div class="division-edit-status">
                                                <span class="division-status <?= !empty($row['is_active']) ? 'division-status--active' : 'division-status--off'; ?>">
                                                    <?= !empty($row['is_active']) ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="division-edit-button-row">
                                        <button type="submit" form="<?= e($updateFormId); ?>" class="division-admin-mini division-admin-mini--save">Simpan</button>
                                        <?php if (!empty($row['is_active'])): ?>
                                            <form method="post" action="index.php?page=data-inventaris" class="division-edit-inline-form" onsubmit="return confirm('Nonaktifkan divisi ini dari tampilan?');">
                                                <input type="hidden" name="division_action" value="deactivate">
                                                <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                                <button type="submit" class="division-admin-mini division-admin-mini--off">Nonaktif</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="index.php?page=data-inventaris" class="division-edit-inline-form" onsubmit="return confirm('Aktifkan kembali divisi ini?');">
                                                <input type="hidden" name="division_action" value="activate">
                                                <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                                <button type="submit" class="division-admin-mini division-admin-mini--active">Aktifkan</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="index.php?page=data-inventaris" class="division-edit-inline-form" onsubmit="return confirm('Hapus divisi ini? Database <?= e($row['inventory_db_name'] ?? '-'); ?> dan seluruh isinya akan ikut terhapus permanen.');">
                                            <input type="hidden" name="division_action" value="delete">
                                            <input type="hidden" name="division_id" value="<?= e($rowId); ?>">
                                            <button type="submit" class="division-admin-mini division-admin-mini--delete">Hapus</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <?php endif; ?>
</div>

<?php if ($isAdminSpmt): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('divisionAdminPanel');
    var openers = document.querySelectorAll('[data-open-division-panel]');
    var closers = document.querySelectorAll('[data-close-division-panel]');
    openers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!panel) { return; }
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('division-overlay-open');
            var firstInput = panel.querySelector('input[name="division_label"]');
            if (firstInput) { setTimeout(function () { firstInput.focus(); }, 80); }
        });
    });
    closers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!panel) { return; }
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('division-overlay-open');
        });
    });

    if (panel) {
        panel.addEventListener('click', function (event) {
            if (event.target === panel) {
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('division-overlay-open');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) {
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('division-overlay-open');
            }
        });
    }
    var search = document.getElementById('divisionAdminSearch');
    var rows = document.querySelectorAll('[data-division-row]');
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
