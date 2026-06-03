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
            <p class="inventory-create-head-card__subtitle">Pilih jenis input inventaris baru.</p>
        </div>
        <?php renderTabRow('pc'); ?>
    </div>

    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--pc inventory-create-form--responsive">
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
</div>
