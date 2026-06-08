<?php
$prevNav = $data['division_nav']['prev'] ?? null;
$nextNav = $data['division_nav']['next'] ?? null;
$pagination = $data['pagination'] ?? ['pages' => [], 'prev' => null, 'next' => null, 'current' => 1];
$currentPc = $data['current_pc_row'] ?? [];
$pageKey = (string) ($data['current_page_key'] ?? '');
$rawOtherItems = $data['raw_other_items'] ?? [];
$standaloneItems = $data['standalone_items'] ?? [];
$divisionCode = (string) ($data['current_division_code'] ?? '');
$displayDivision = (string) ($data['current_display_division'] ?? '');
$inventoryDivisionOptions = $data['inventory_division_options'] ?? [];
$flash = $data['flash'] ?? null;
$focusItem = (string) ($data['focus_item'] ?? ($_GET['focus_item'] ?? ''));
$licenseValue = strtoupper(trim((string) ($data['summary_specs']['license'] ?? '')));
$officeValue = strtoupper(trim((string) ($data['summary_specs']['office'] ?? '')));
$officeLicenseValue = strtoupper(trim((string) ($data['summary_specs']['office_license'] ?? '')));
$licenseClass = strpos($licenseValue, 'UNLICENSED') !== false ? 'summary-table__pill--red' : ((strpos($licenseValue, 'LICENSED') !== false || strpos($licenseValue, 'AKTIF') !== false) ? 'summary-table__pill--green' : '');
$officeLicenseClass = strpos($officeLicenseValue, 'UNLICENSED') !== false ? 'summary-table__pill--red' : ((strpos($officeLicenseValue, 'LICENSED') !== false || strpos($officeLicenseValue, 'AKTIF') !== false) ? 'summary-table__pill--green' : '');
$editUrlBase = 'index.php?' . http_build_query([
    'page' => 'inventaris-detail',
    'division_code' => $divisionCode,
    'display_division' => $displayDivision,
    'user_page' => $pagination['current'] ?? 1,
]);
?>
<div class="detail-page detail-page--inventory">
    <div class="detail-header">
        <div class="detail-title-wrap detail-title-wrap--fixed-nav">
            <?php if (!empty($prevNav['href'])): ?>
                <a class="detail-title-wrap__nav detail-title-wrap__nav--left" href="<?= e($prevNav['href']); ?>" title="<?= e('Divisi sebelumnya: ' . ($prevNav['label'] ?? '')); ?>">‹</a>
            <?php else: ?>
                <span class="detail-title-wrap__nav detail-title-wrap__nav--left is-disabled">‹</span>
            <?php endif; ?>
            <h1><?= e($data['current_display_division']); ?></h1>
            <?php if (!empty($nextNav['href'])): ?>
                <a class="detail-title-wrap__nav detail-title-wrap__nav--right" href="<?= e($nextNav['href']); ?>" title="<?= e('Divisi berikutnya: ' . ($nextNav['label'] ?? '')); ?>">›</a>
            <?php else: ?>
                <span class="detail-title-wrap__nav detail-title-wrap__nav--right is-disabled">›</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($flash['message'])): ?>
            <div class="flash flash--<?= e($flash['type'] ?? 'success'); ?> detail-flash"><?= e($flash['message']); ?></div>
        <?php endif; ?>
        <div class="detail-header__row">
            <span class="detail-header__updated">LAST UPDATED : <?= e($data['updated']); ?></span>
            <div class="detail-header__actions detail-header__actions--inline">
                <div class="export-menu js-export-menu">
                    <button class="btn btn--primary btn--lg detail-action js-export-toggle" type="button">EXPORT FILE</button>
                    <div class="export-menu__panel">
                        <a href="<?= e($editUrlBase . '&action=export&format=pdf'); ?>">Export PDF</a>
                        <a href="<?= e($editUrlBase . '&action=export&format=xlsx'); ?>">Export Excel (.xlsx)</a>
                    </div>
                </div>
                <button class="icon-square icon-square--lg js-open-modal" type="button" data-modal="modalAddOther" title="Tambah perangkat lain"><i class="fa-solid fa-plus"></i></button>
                <button class="icon-square icon-square--lg js-open-modal" type="button" data-modal="modalEditInventory" title="Edit data inventaris"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="icon-square icon-square--lg js-open-modal" type="button" data-modal="modalDeleteInventory" title="Hapus data user inventaris"><i class="fa-solid fa-trash"></i></button>
            </div>
    </div>
    <div class="summary-table summary-table--sticky">
        <div class="summary-table__row summary-table__row--head">
            <span>Computer Name</span><span>User</span><span>Processor</span><span>RAM</span><span>Harddisk</span>
        </div>
        <div class="summary-table__row summary-table__row--body">
            <span><?= e($data['summary_specs']['computer_name']); ?></span>
            <span><?= e($data['summary_specs']['user']); ?></span>
            <span><?= e($data['summary_specs']['processor']); ?></span>
            <span><?= e($data['summary_specs']['ram']); ?></span>
            <span><?= e($data['summary_specs']['harddisk']); ?></span>
        </div>
        <div class="summary-table__row summary-table__row--head">
            <span>IP Address</span><span>Sistem Operasi</span><span>LICENSED</span><span>MS OFFFICE</span><span>LICENSED</span>
        </div>
        <div class="summary-table__row summary-table__row--body">
            <span><?= e($data['summary_specs']['ip']); ?></span>
            <span><?= e($data['summary_specs']['os']); ?></span>
            <span class="summary-table__pill <?= e($licenseClass); ?>"><?= e($data['summary_specs']['license']); ?></span>
            <span><?= e($data['summary_specs']['office']); ?></span>
            <span class="summary-table__pill <?= e($officeLicenseClass); ?>"><?= e($data['summary_specs']['office_license']); ?></span>
        </div>
    </div>


    <div class="inventory-summary-mobile" aria-label="Ringkasan detail komputer">
        <div class="inventory-summary-mobile__item"><span>Computer Name</span><strong><?= e($data['summary_specs']['computer_name']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>User</span><strong><?= e($data['summary_specs']['user']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>Processor</span><strong><?= e($data['summary_specs']['processor']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>RAM</span><strong><?= e($data['summary_specs']['ram']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>Harddisk</span><strong><?= e($data['summary_specs']['harddisk']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>IP Address</span><strong><?= e($data['summary_specs']['ip']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>Sistem Operasi</span><strong><?= e($data['summary_specs']['os']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>Licensed OS</span><strong class="inventory-summary-mobile__pill <?= e($licenseClass); ?>"><?= e($data['summary_specs']['license']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>MS Office</span><strong><?= e($data['summary_specs']['office']); ?></strong></div>
        <div class="inventory-summary-mobile__item"><span>Licensed Office</span><strong class="inventory-summary-mobile__pill <?= e($officeLicenseClass); ?>"><?= e($data['summary_specs']['office_license']); ?></strong></div>
    </div>

    <div class="detail-table-panel">
        <div class="table-wrap table-wrap--inventory">
            <table class="data-table data-table--inventory">
                <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>ID Inventaris</th>
                    <th>Jenis Perangkat</th>
                    <th>Merk Perangkat</th>
                    <th>Unit Kerja</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data['inventory_rows'] as $row): ?>
                    <tr data-row-key="<?= e($row['row_key'] ?? ''); ?>" class="<?= ($focusItem !== '' && $focusItem === ($row['row_key'] ?? '')) ? 'is-focused-row' : ''; ?>">
                        <td><?= e((string) $row['no']); ?></td>
                        <td><div class="thumb thumb--image"><img src="<?= asset($row['image']); ?>" alt="<?= e($row['device']); ?>" data-fallback-src="<?= asset(($row['device'] ?? '') === 'PC' ? 'images/inv-pc.png' : 'images/inv-default.jpg'); ?>"></div></td>
                        <td><?= e($row['id']); ?></td>
                        <td><?= e($row['device']); ?></td>
                        <td><?= e($row['brand'] ?? '-'); ?></td>
                        <td><?= e($row['unit']); ?></td>
                        <td class="inventory-status-cell"><span class="badge badge--<?= e($row['status_class']); ?>"><?= e($row['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination pagination--sticky">
        <?php if (!empty($pagination['prev']['href'])): ?>
            <a class="pagination__btn" href="<?= e($pagination['prev']['href']); ?>"><span>Previous</span></a>
        <?php else: ?>
            <span class="pagination__btn pagination__btn--disabled"><span>Previous</span></span>
        <?php endif; ?>
        <?php foreach (($pagination['pages'] ?? []) as $pageItem): ?>
            <?php
                $titleAttr = 'User: ' . ($pageItem['user_label'] ?: 'Komputer #' . $pageItem['number']);
                if (!empty($pageItem['has_rusak'])) {
                    $titleAttr .= ' (Status: RUSAK)';
                }
                $rusakClass = !empty($pageItem['has_rusak']) ? ' is-rusak' : '';
            ?>
            <?php if (!empty($pageItem['is_active'])): ?>
                <span class="pagination__num is-active<?= $rusakClass; ?>" title="<?= e($titleAttr); ?>"><?= e((string) $pageItem['number']); ?></span>
            <?php else: ?>
                <a class="pagination__num<?= $rusakClass; ?>" href="<?= e($pageItem['href']); ?>" title="<?= e($titleAttr); ?>"><?= e((string) $pageItem['number']); ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($pagination['next']['href'])): ?>
            <a class="pagination__btn" href="<?= e($pagination['next']['href']); ?>"><span>Next</span></a>
        <?php else: ?>
            <span class="pagination__btn pagination__btn--disabled"><span>Next</span></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($standaloneItems)): ?>
    <div class="standalone-section">
        <div class="standalone-section__header">
            <span class="standalone-section__icon"><i class="fa-solid fa-box"></i></span>
            <div>
                <h2 class="standalone-section__title">Perangkat Mandiri</h2>
                <p class="standalone-section__sub">Barang divisi <?= e($displayDivision); ?> yang tidak terikat ke PC tertentu (printer, proyektor, UPS, dll)</p>
            </div>
        </div>
        <div class="table-wrap table-wrap--inventory">
            <table class="data-table data-table--inventory">
                <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>ID Inventaris</th>
                    <th>Jenis Perangkat</th>
                    <th>Merk Perangkat</th>
                    <th>Unit Kerja</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($standaloneItems as $idx => $item): ?>
                    <?php
                        $itemStatus = strtoupper(trim((string) ($item['status'] ?? 'AKTIF')));
                        $itemStatus = $itemStatus !== '' ? $itemStatus : 'AKTIF';
                        $itemStatusClass = $itemStatus === 'AKTIF' ? 'ok' : ($itemStatus === 'RUSAK' ? 'bad' : 'neutral');
                        $itemImg = trim((string) ($item['gambar'] ?? ''));
                        $itemImg = $itemImg !== '' ? $itemImg : 'images/inv-default.jpg';
                    ?>
                    <tr>
                        <td><?= e((string) ($idx + 1)); ?></td>
                        <td><div class="thumb thumb--image"><img src="<?= asset($itemImg); ?>" alt="<?= e((string) ($item['jenis_perangkat'] ?? '')); ?>" data-fallback-src="<?= asset('images/inv-default.jpg'); ?>"></div></td>
                        <td><?= e((string) ($item['id_inventaris'] ?? '-')); ?></td>
                        <td><?= e((string) ($item['jenis_perangkat'] ?? '-')); ?></td>
                        <td><?= e((string) ($item['merk_perangkat'] ?? '-')); ?></td>
                        <td><?= e((string) ($item['unit_kerja'] ?? '-')); ?></td>
                        <td class="inventory-status-cell"><span class="badge badge--<?= e($itemStatusClass); ?>"><?= e($itemStatus); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <style>
    .standalone-section {
        margin-top: 32px;
        border-top: 2px dashed #e2e8f0;
        padding-top: 20px;
    }
    .standalone-section__header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }
    .standalone-section__icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: #fff;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .standalone-section__title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 2px;
    }
    .standalone-section__sub {
        font-size: 0.78rem;
        color: #64748b;
        margin: 0;
    }

    /* Highlight page number when PC is broken */
    .pagination__num.is-rusak {
        background-color: #fee2e2 !important;
        border-color: #fca5a5 !important;
        color: #dc2626 !important;
        font-weight: bold;
    }
    .pagination__num.is-rusak.is-active {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
        color: #ffffff !important;
    }
    </style>

</div><!-- /.detail-page -->

<div class="modal" id="modalAddOther" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h2>Form Perangkat Lain Baru</h2>
            <button type="button" class="icon-square js-close-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" enctype="multipart/form-data" class="inventory-form inventory-form--grid">
            <input type="hidden" name="action" value="save_other">
            <input type="hidden" name="page_key" value="<?= e($pageKey); ?>">
            <div class="inventory-form__grid">
                <label><span>ID Inventaris</span><input type="text" name="other_id_inventaris"></label>
                <label><span>Jenis Perangkat</span><input type="text" name="other_jenis_perangkat" placeholder="Contoh : Monitor"></label>
                <label><span>Merk Perangkat</span><input type="text" name="other_merk_perangkat" placeholder="Contoh : LENOVO"></label>
                <label><span>Unit Kerja</span><input type="text" name="other_unit_kerja" value="<?= e($currentPc['unit_kerja'] ?? $displayDivision); ?>"></label>
                <label><span>User</span><input type="text" name="other_user" value="<?= e($currentPc['user'] ?? ''); ?>"></label>
                <label><span>Status</span><select name="other_status" class="inventory-form__select"><option value="AKTIF" selected>Aktif</option><option value="RUSAK">Rusak</option></select></label>
                <label class="inventory-form__full"><span>Gambar</span><input type="file" name="other_gambar_file" accept="image/*" class="js-image-input" data-preview-target="addOtherImagePreview"></label>
                <div class="inventory-form__hint inventory-form__hint--preview" id="addOtherImagePreview">Belum ada gambar dipilih.</div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--ghost btn--lg js-close-modal">Batal</button>
                <button type="submit" class="btn btn--primary btn--lg">Simpan Perangkat Lain</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modalEditInventory" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog modal__dialog--wide">
        <div class="modal__header">
            <h2>Edit Data Inventaris</h2>
            <button type="button" class="icon-square js-close-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="form-switcher">
            <button type="button" class="form-switcher__btn is-active" data-target="editPcPane">PC</button>
            <button type="button" class="form-switcher__btn" data-target="editOtherPane">Perangkat Lain</button>
        </div>
        <div class="form-pane is-active" id="editPcPane">
            <form method="post" enctype="multipart/form-data" class="inventory-form inventory-form--grid">
                <input type="hidden" name="action" value="save_inventory_edit">
                <input type="hidden" name="edit_scope" value="pc">
                <input type="hidden" name="page_key" value="<?= e($pageKey); ?>">
                <div class="inventory-form__grid">
                    <label><span>ID Inventaris</span><input type="text" name="id_inventaris" value="<?= e($currentPc['id_inventaris'] ?? ''); ?>"></label>
                    <label><span>Unit Kerja</span><input type="text" name="unit_kerja" value="<?= e($currentPc['unit_kerja'] ?? ''); ?>"></label>
                    <label><span>Pindah Divisi</span>
                        <select name="target_division_code" class="inventory-form__select">
                            <?php foreach ($inventoryDivisionOptions as $divisionOption): ?>
                                <?php $optionCode = (string) ($divisionOption['division_code'] ?? ''); ?>
                                <option value="<?= e($optionCode); ?>" <?= $optionCode === $divisionCode ? 'selected' : ''; ?>><?= e(strtoupper((string) ($divisionOption['division_label'] ?? $optionCode))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span>Jenis Perangkat</span><input type="text" name="jenis_perangkat_display" value="PC" readonly></label>
                    <input type="hidden" name="jenis_perangkat" value="PC">
                    <label><span>Merk Perangkat</span><input type="text" name="merk_perangkat" value="<?= e($currentPc['merk_perangkat'] ?? (($currentPc['jenis_perangkat'] ?? '') !== 'PC' ? ($currentPc['jenis_perangkat'] ?? '') : '')); ?>"></label>
                    <?php $pcStatusValue = strtoupper(trim((string) ($currentPc['status'] ?? 'AKTIF'))); ?>
                    <label><span>Status</span><select name="status" class="inventory-form__select"><option value="AKTIF" <?= $pcStatusValue === 'AKTIF' ? 'selected' : ''; ?>>Aktif</option><option value="RUSAK" <?= $pcStatusValue === 'RUSAK' ? 'selected' : ''; ?>>Rusak</option></select></label>
                    <label><span>Computer Name</span><input type="text" name="computer_name" value="<?= e($currentPc['computer_name'] ?? ''); ?>"></label>
                    <label><span>User</span><input type="text" name="user" value="<?= e($currentPc['user'] ?? ''); ?>"></label>
                    <label><span>Processor</span><input type="text" name="processor" value="<?= e($currentPc['processor'] ?? ''); ?>"></label>
                    <label><span>RAM</span><input type="text" name="ram" value="<?= e($currentPc['ram'] ?? ''); ?>"></label>
                    <label><span>Harddisk</span><input type="text" name="kapasitas_harddisk" value="<?= e($currentPc['kapasitas_harddisk'] ?? ''); ?>"></label>
                    <label><span>IP Address</span><input type="text" name="ip_address" value="<?= e($currentPc['ip_address'] ?? ''); ?>"></label>
                    <label><span>Sistem Operasi</span><input type="text" name="sistem_operasi" value="<?= e($currentPc['sistem_operasi'] ?? ''); ?>"></label>
                    <label><span>Licensed Windows</span><input type="text" name="licensed_windows" value="<?= e($currentPc['licensed_windows'] ?? ''); ?>"></label>
                    <label><span>Microsoft Office</span><input type="text" name="microsoft_office" value="<?= e($currentPc['microsoft_office'] ?? ''); ?>"></label>
                    <label><span>Licensed Office</span><input type="text" name="licensed_office" value="<?= e($currentPc['licensed_office'] ?? ''); ?>"></label>
                    <?php
                        $pcGambarRaw = trim((string) ($currentPc['gambar'] ?? ''));
                        $pcGambarDisplay = $pcGambarRaw !== '' ? asset($pcGambarRaw) : '';
                    ?>
                    <input type="hidden" name="pc_gambar_existing" value="<?= e($pcGambarRaw); ?>">
                    <div class="inventory-form__full"><span>Dokumentasi PC</span><?php renderDocInput('Pilih File', 'pc_gambar_file', true, $pcGambarDisplay); ?></div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn--ghost btn--lg js-close-modal">Batal</button>
                    <button type="submit" class="btn btn--primary btn--lg">Simpan Perubahan PC</button>
                </div>
            </form>
        </div>
        <div class="form-pane" id="editOtherPane">
            <form method="post" enctype="multipart/form-data" class="inventory-form inventory-form--grid">
                <input type="hidden" name="action" value="save_inventory_edit">
                <input type="hidden" name="edit_scope" value="other">
                <input type="hidden" name="page_key" value="<?= e($pageKey); ?>">
                <input type="hidden" name="item_key" id="otherItemKey" value="">
                <label class="inventory-form__full"><span>Pilih Perangkat Lain</span>
                    <select class="inventory-form__select js-other-selector">
                        <option value="">Pilih perangkat lain</option>
                        <?php foreach ($rawOtherItems as $idx => $item): ?>
                            <?php $itemKey = md5(strtolower(trim((string) ($item['id_inventaris'] ?? ''))) . '|' . strtolower(trim((string) ($item['jenis_perangkat'] ?? ''))) . '|' . strtolower(trim((string) ($item['unit_kerja'] ?? ''))) . '|' . strtolower(trim((string) ($item['user'] ?? ''))) . '|' . strtolower(trim((string) ($item['merk_perangkat'] ?? '')))); ?>
                            <option value="<?= e($itemKey); ?>" data-index="<?= e((string) $idx); ?>"><?= e(($item['jenis_perangkat'] ?? '-') . ' - ' . ($item['merk_perangkat'] ?? '-')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="inventory-form__grid">
                    <label><span>ID Inventaris</span><input type="text" name="other_id_inventaris" id="otherIdInventaris"></label>
                    <label><span>Jenis Perangkat</span><input type="text" name="other_jenis_perangkat" id="otherJenisPerangkat"></label>
                    <label><span>Merk Perangkat</span><input type="text" name="other_merk_perangkat" id="otherMerkPerangkat"></label>
                    <label><span>Unit Kerja</span><input type="text" name="other_unit_kerja" id="otherUnitKerja"></label>
                    <label><span>User</span><input type="text" name="other_user" id="otherUser"></label>
                    <label><span>Status</span><select name="other_status" id="otherStatus" class="inventory-form__select"><option value="AKTIF">Aktif</option><option value="RUSAK">Rusak</option></select></label>
                    <input type="hidden" name="other_gambar_existing" id="otherGambarExisting"><label class="inventory-form__full"><span>Gambar Baru</span><input type="file" name="other_gambar_file" id="otherGambarFile" accept="image/*" class="js-image-input" data-preview-target="otherGambarPreviewText"></label><div class="inventory-form__hint inventory-form__hint--preview" id="otherGambarPreviewText">Gambar saat ini: -</div>
                </div>
                <div class="modal__footer modal__footer--between">
                    <button type="submit" name="action" value="delete_other_item" class="btn btn--danger btn--lg js-confirm-delete-item" data-confirm-message="Yakin mau hapus 1 perangkat yang dipilih ini saja?">Hapus 1 Perangkat</button>
                    <div class="modal__footer-group">
                        <button type="button" class="btn btn--ghost btn--lg js-close-modal">Batal</button>
                        <button type="submit" name="action" value="save_inventory_edit" class="btn btn--primary btn--lg">Simpan Perubahan Perangkat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal" id="modalDeleteInventory" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog">
        <div class="modal__header">
            <h2>Hapus Data Inventaris User</h2>
            <button type="button" class="icon-square js-close-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" class="inventory-form js-confirm-delete" data-confirm-message="Yakin mau hapus? Data PC dan semua perangkat lain untuk user ini akan ikut dihapus.">
            <input type="hidden" name="action" value="delete_inventory_bundle">
            <input type="hidden" name="page_key" value="<?= e($pageKey); ?>">
            <div class="inventory-form__danger-note">Data PC dan semua perangkat lain pada halaman user ini akan dihapus sekaligus.</div>
            <div class="modal__footer">
                <button type="button" class="btn btn--ghost btn--lg js-close-modal">Batal</button>
                <button type="submit" class="btn btn--danger btn--lg">Hapus Data</button>
            </div>
        </form>
    </div>
</div>
<script>
// Menjaga parameter user_page di URL address bar agar mendukung reload (F5),
// bookmarking, dan direct navigation ke PC yang rusak dari dashboard.
window.SPMT_INVENTORY_DETAIL = {
    otherItems: <?= json_encode(array_values($rawOtherItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    focusItem: <?= json_encode($focusItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
</script>
