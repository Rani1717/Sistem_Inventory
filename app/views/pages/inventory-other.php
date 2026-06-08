<?php
renderMainHeader($data, 'FORM SPMT IT ASSET MANAGEMENT');
$form = $data['inventory_form'] ?? ['divisions' => [], 'users' => []];
$divisions = $form['divisions'] ?? [];
$users = $form['users'] ?? [];
?>
<div class="inventory-create-page">
    <div class="inventory-create-head-card">
        <div>
            <h2 class="inventory-create-head-card__title">Inventaris Baru</h2>
            <p class="inventory-create-head-card__subtitle">Input barang mandiri (Printer, Proyektor, UPS, dll).</p>
        </div>
        <?php renderTabRow('other'); ?>
    </div>

    <!-- ====== FORM: BARANG MANDIRI ====== -->
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--other inventory-create-form--responsive" id="formStandaloneNew">
        <input type="hidden" name="action" value="save_other_new">
        <input type="hidden" name="input_mode" value="standalone">

        <div class="inventory-create-scroll">
            <div class="form-shell__body">
                <div class="inventory-grid inventory-grid--responsive">
                    <!-- Divisi -->
                    <div class="field">
                        <label class="field__label" for="division_code_other">Divisi <span style="color:#e53e3e">*</span></label>
                        <select id="division_code_other" name="division_code" class="field__control" required>
                            <option value="">Pilih divisi</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= e($division['division_code'] ?? ''); ?>"
                                        data-division-label="<?= e($division['division_label'] ?? ''); ?>">
                                    <?= e(($division['sheet_sumber'] ?? '') . ' - ' . ($division['division_label'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="division_label" id="division_label_other" value="">
                    </div>

                    <?php renderField('ID Inventaris', 'other_id_inventaris'); ?>
                    <?php renderField('Jenis Perangkat', 'other_jenis_perangkat'); ?>
                    <?php renderField('Merk Perangkat', 'other_merk_perangkat'); ?>

                    <!-- Unit Kerja -->
                    <div class="field">
                        <label class="field__label" for="unit_kerja_other">Unit Kerja</label>
                        <input id="unit_kerja_other" name="unit_kerja" type="text" class="field__control" placeholder="Masukkan unit kerja">
                    </div>

                    <div class="field">
                        <label class="field__label" for="other_status">Status</label>
                        <select id="other_status" name="other_status" class="field__control" required>
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
        var divisionSel = document.getElementById('division_code_other');
        var divLabel    = document.getElementById('division_label_other');

        divisionSel.addEventListener('change', function () {
            var optEl = this.options[this.selectedIndex];
            divLabel.value = optEl ? (optEl.dataset.divisionLabel || '') : '';
        });
    })();
    </script>
</div>