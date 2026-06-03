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
            <p class="inventory-create-head-card__subtitle">Input perangkat lain untuk user yang sudah punya inventaris PC.</p>
        </div>
        <?php renderTabRow('other'); ?>
    </div>
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--other inventory-create-form--responsive">
        <input type="hidden" name="action" value="save_other_new">
        <div class="inventory-create-scroll">
            <div class="form-shell__body">
                <div class="inventory-grid inventory-grid--responsive">
                    <div class="field">
                        <label class="field__label" for="division_code_other">Divisi</label>
                        <select id="division_code_other" name="division_code" class="field__control js-division-select" data-target-label="division_label_other" required>
                            <option value="">Pilih divisi</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= e($division['division_code'] ?? ''); ?>" data-division-id="<?= e((string) ($division['id'] ?? '')); ?>" data-division-label="<?= e($division['division_label'] ?? ''); ?>"><?= e(($division['sheet_sumber'] ?? '') . ' - ' . ($division['division_label'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="division_label" id="division_label_other" value="">
                    </div>
                    <div class="field">
                        <label class="field__label" for="user_other">User</label>
                        <input id="user_other" name="user" type="text" class="field__control" placeholder="Masukkan nama user" required>
                    </div>
                    <?php renderField('ID Inventaris', 'other_id_inventaris'); ?>
                    <?php renderField('Jenis Perangkat', 'other_jenis_perangkat'); ?>
                    <?php renderField('Merk Perangkat', 'other_merk_perangkat'); ?>
                    <?php renderField('Unit Kerja', 'unit_kerja'); ?>
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
</div>
