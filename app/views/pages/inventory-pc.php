<?php
renderMainHeader($data, 'FORM SPMT IT ASSET MANAGEMENT');
$form = $data['inventory_form'] ?? ['divisions' => [], 'users' => []];
$divisions = $form['divisions'] ?? [];
$users = $form['users'] ?? [];

// Sub-tab aktif: 'pc' (default) atau 'linked'
$pcTab = trim((string) ($_GET['pc_tab'] ?? 'pc'));
if (!in_array($pcTab, ['pc', 'linked'], true)) {
    $pcTab = 'pc';
}
?>
<div class="inventory-create-page">
    <div class="inventory-create-head-card">
        <div>
            <h2 class="inventory-create-head-card__title">Inventaris Baru</h2>
            <p class="inventory-create-head-card__subtitle">Pilih jenis input inventaris baru.</p>
        </div>
        <?php renderTabRow('pc'); ?>
    </div>

    <!-- ====== SUB-TAB TOGGLE: PC | Terhubung ke PC ====== -->
    <div class="other-mode-toggle" id="pcSubTabToggle" style="margin-bottom:0;">
        <a href="index.php?page=inventory-pc&pc_tab=pc"
           class="other-mode-toggle__btn <?= $pcTab === 'pc' ? 'is-active' : ''; ?>">
            <span class="other-mode-toggle__icon"><i class="fa-solid fa-desktop"></i></span>
            <span class="other-mode-toggle__text">
                <strong>PC</strong>
                <small>Data utama komputer/laptop</small>
            </span>
        </a>
        <a href="index.php?page=inventory-pc&pc_tab=linked"
           class="other-mode-toggle__btn <?= $pcTab === 'linked' ? 'is-active' : ''; ?>">
            <span class="other-mode-toggle__icon"><i class="fa-solid fa-link"></i></span>
            <span class="other-mode-toggle__text">
                <strong>Terhubung ke PC</strong>
                <small>Mouse, keyboard, monitor, dll</small>
            </span>
        </a>
    </div>

    <?php if ($pcTab === 'pc'): ?>
    <!-- ====== FORM: PC ====== -->
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--pc inventory-create-form--responsive" style="margin-top:16px;">
        <input type="hidden" name="action" value="save_pc_new">
        <div class="inventory-create-scroll">
            <div class="form-shell__body">
                <div class="inventory-grid inventory-grid--responsive">
                    <div class="field">
                        <label class="field__label" for="division_code_pc">Divisi</label>
                        <select id="division_code_pc" name="division_code" class="field__control js-division-select" data-target-label="division_label_pc" required>
                            <option value="">Pilih divisi</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= e($division['division_code'] ?? ''); ?>" data-division-id="<?= e((string) ($division['id'] ?? '')); ?>" data-division-label="<?= e($division['division_label'] ?? ''); ?>"><?= e(($division['sheet_sumber'] ?? '') . ' - ' . ($division['division_label'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="division_label" id="division_label_pc" value="">
                    </div>
                    <?php renderField('ID Inventaris', 'id_inventaris'); ?>
                
                    <div class="field">
                        <label class="field__label" for="jenis_perangkat_pc">Jenis Perangkat</label>
                        <input id="jenis_perangkat_pc" name="jenis_perangkat_display" type="text" class="field__control" value="PC" readonly>
                        <input type="hidden" name="jenis_perangkat" value="PC">
                    </div>
                    <?php renderField('Merk Perangkat', 'merk_perangkat'); ?>
                
                    <div class="field">
                        <label class="field__label" for="status_pc">Status</label>
                        <select id="status_pc" name="status" class="field__control" required>
                            <option value="AKTIF" selected>Aktif</option>
                            <option value="RUSAK">Rusak</option>
                        </select>
                    </div>
                    <?php renderField('Computer Name', 'computer_name'); ?>
                
                    <?php renderField('Unit Kerja', 'unit_kerja', 'text', '', ''); ?>
                    <?php renderField('User', 'user'); ?>
                
                    <?php renderField('RAM', 'ram'); ?>
                    <?php renderField('Processor', 'processor'); ?>
                
                    <?php renderField('IP Address', 'ip_address'); ?>
                    <?php renderField('Harddisk', 'kapasitas_harddisk'); ?>
                
                    <?php renderField('Sistem Operasi', 'sistem_operasi'); ?>
                    <?php renderField('Licensed Windows', 'licensed_windows'); ?>
                
                    <?php renderField('MS Office', 'microsoft_office'); ?>
                    <?php renderField('Licensed Office', 'licensed_office'); ?>
                
                    <div class="field field--doc-upload inventory-grid__full">
                        <label class="field__label">Dokumentasi PC</label>
                        <?php renderDocInput('Pilih File', 'pc_gambar_file'); ?>
                        <input type="hidden" name="pc_gambar_existing" value="">
                    </div>
                </div>
            </div>
        </div>
        <div class="inventory-create-actions">
            <button type="submit" class="btn btn--accent btn--full">ADD NEW</button>
        </div>
    </form>

    <?php else: ?>
    <!-- ====== FORM: TERHUBUNG KE PC ====== -->
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--other inventory-create-form--responsive" id="formLinkedNew" style="margin-top:16px;">
        <input type="hidden" name="action" value="save_other_new">
        <input type="hidden" name="input_mode" value="linked">
        <input type="hidden" name="pc_row_id" id="pcRowIdHidden" value="">

        <div class="inventory-create-scroll">
            <div class="form-shell__body">
                <div class="inventory-grid inventory-grid--responsive">
                    <!-- Divisi -->
                    <div class="field">
                        <label class="field__label" for="division_code_linked">Divisi <span style="color:#e53e3e">*</span></label>
                        <select id="division_code_linked" name="division_code" class="field__control" required>
                            <option value="">Pilih divisi</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= e($division['division_code'] ?? ''); ?>"
                                        data-division-label="<?= e($division['division_label'] ?? ''); ?>">
                                    <?= e(($division['sheet_sumber'] ?? '') . ' - ' . ($division['division_label'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="division_label" id="division_label_linked" value="">
                    </div>

                    <!-- Pilih PC -->
                    <div class="field" id="fieldPilihPc">
                        <label class="field__label" for="pilih_pc_dropdown">Pilih PC <span style="color:#e53e3e">*</span></label>
                        <select id="pilih_pc_dropdown" class="field__control" required>
                            <option value="">— Pilih divisi dulu —</option>
                        </select>
                        <small style="display:block;margin-top:4px;font-size:0.75rem;color:#64748b;">Format: NAMA USER — COMPUTER NAME</small>
                    </div>

                    <?php renderField('ID Inventaris', 'other_id_inventaris'); ?>
                    <?php renderField('Jenis Perangkat', 'other_jenis_perangkat'); ?>
                    <?php renderField('Merk Perangkat', 'other_merk_perangkat'); ?>

                    <!-- Unit Kerja (auto-fill dari PC) -->
                    <div class="field">
                        <label class="field__label" for="unit_kerja_linked">Unit Kerja</label>
                        <input id="unit_kerja_linked" name="unit_kerja" type="text" class="field__control" placeholder="Masukkan unit kerja">
                    </div>

                    <div class="field">
                        <label class="field__label" for="linked_status">Status</label>
                        <select id="linked_status" name="other_status" class="field__control" required>
                            <option value="AKTIF" selected>Aktif</option>
                            <option value="RUSAK">Rusak</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field__label">Dokumentasi Inventaris</label>
                        <?php renderDocInput('Pilih File', 'other_gambar_file'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="inventory-create-actions">
            <button type="submit" class="btn btn--accent btn--full">ADD NEW</button>
        </div>
    </form>

    <script>
    (function () {
        var form        = document.getElementById('formLinkedNew');
        var pcRowInput  = document.getElementById('pcRowIdHidden');
        var divisionSel = document.getElementById('division_code_linked');
        var divLabel    = document.getElementById('division_label_linked');
        var pcDropdown  = document.getElementById('pilih_pc_dropdown');
        var unitKerja   = document.getElementById('unit_kerja_linked');

        // Load daftar PC via AJAX saat divisi berubah
        divisionSel.addEventListener('change', function () {
            var code = this.value;
            var optEl = this.options[this.selectedIndex];
            divLabel.value = optEl ? (optEl.dataset.divisionLabel || '') : '';

            pcDropdown.innerHTML = '<option value="">Memuat daftar PC...</option>';
            pcDropdown.disabled = true;
            pcRowInput.value = '';
            unitKerja.value = '';

            if (!code) {
                pcDropdown.innerHTML = '<option value="">— Pilih divisi dulu —</option>';
                pcDropdown.disabled = false;
                return;
            }

            fetch('index.php?ajax=get_pc_list&division_code=' + encodeURIComponent(code))
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    pcDropdown.innerHTML = '<option value="">Pilih PC...</option>';
                    if (!list || list.length === 0) {
                        pcDropdown.innerHTML = '<option value="">Tidak ada PC di divisi ini</option>';
                    } else {
                        list.forEach(function (pc) {
                            var opt = document.createElement('option');
                            opt.value = pc.id;
                            opt.textContent = pc.label;
                            opt.dataset.user = pc.user;
                            opt.dataset.unitKerja = pc.unit_kerja;
                            pcDropdown.appendChild(opt);
                        });
                    }
                    pcDropdown.disabled = false;
                })
                .catch(function () {
                    pcDropdown.innerHTML = '<option value="">Gagal memuat daftar PC</option>';
                    pcDropdown.disabled = false;
                });
        });

        // Saat PC dipilih → auto-fill unit kerja + simpan pc_row_id
        pcDropdown.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                pcRowInput.value = opt.value;
                unitKerja.value = opt.dataset.unitKerja || '';
            } else {
                pcRowInput.value = '';
            }
        });

        // Validasi sebelum submit
        form.addEventListener('submit', function (e) {
            if (!pcRowInput.value || pcRowInput.value === '0') {
                e.preventDefault();
                alert('Pilih PC yang akan dihubungkan terlebih dahulu.');
                pcDropdown.focus();
            }
        });
    })();
    </script>
    <?php endif; ?>

</div>

<style>
/* Sub-tab toggle — gunakan style yang sama dengan other-mode-toggle */
.other-mode-toggle__btn {
    text-decoration: none;
    color: inherit;
}
</style>
