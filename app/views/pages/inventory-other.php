<?php
renderMainHeader($data, 'FORM SPMT IT ASSET MANAGEMENT');
$form = $data['inventory_form'] ?? ['divisions' => [], 'users' => []];
$divisions = $form['divisions'] ?? [];
$users = $form['users'] ?? [];

// Tab aktif: 'other' atau 'cctv'
$activeTab = trim((string) ($_GET['inv_tab'] ?? 'other'));
if (!in_array($activeTab, ['other', 'cctv'], true)) {
    $activeTab = 'other';
}
?>
<div class="inventory-create-page">
    <div class="inventory-create-head-card">
        <div>
            <h2 class="inventory-create-head-card__title">Inventaris Baru</h2>
            <p class="inventory-create-head-card__subtitle">Input perangkat lain untuk user yang sudah punya inventaris PC.</p>
        </div>
        <?php renderTabRow($activeTab === 'cctv' ? 'cctv' : 'other'); ?>
    </div>

    <?php if ($activeTab === 'cctv'): ?>
    <?php /* ============ FORM: CCTV ============ */ ?>
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--other inventory-create-form--responsive">
        <input type="hidden" name="action" value="save_cctv_inventaris_new">
        <div class="inventory-create-scroll">
            <div class="form-shell__body">
                <div class="inventory-grid inventory-grid--responsive">
                    <div class="field">
                        <label class="field__label" for="cctv_nama">Nama CCTV <span style="color:#e53e3e">*</span></label>
                        <input id="cctv_nama" name="nama_cctv" type="text" class="field__control" placeholder="Contoh: CCTV GATE 1" required>
                    </div>
                    <div class="field">
                        <label class="field__label" for="cctv_kode">Kode CCTV <span style="color:#e53e3e">*</span></label>
                        <input id="cctv_kode" name="kode_cctv" type="text" class="field__control" placeholder="Contoh: CCTV-001" required>
                        <small style="display:block;margin-top:4px;font-size:0.75rem;color:#64748b;">Kode unik untuk identifikasi unit CCTV ini.</small>
                    </div>
                    <div class="field">
                        <label class="field__label" for="cctv_lokasi">Lokasi</label>
                        <input id="cctv_lokasi" name="lokasi" type="text" class="field__control" placeholder="Contoh: Gedung A, Lantai 1">
                    </div>
                    <div class="field">
                        <label class="field__label" for="cctv_jumlah">Jumlah Unit <span style="color:#e53e3e">*</span></label>
                        <input id="cctv_jumlah" name="jumlah" type="number" class="field__control" min="1" value="1" required>
                    </div>
                    <div class="field">
                        <label class="field__label" for="cctv_status">Status</label>
                        <select id="cctv_status" name="status" class="field__control">
                            <option value="AKTIF" selected>Aktif</option>
                            <option value="RUSAK">Rusak</option>
                            <option value="NONAKTIF">Nonaktif</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label class="field__label" for="cctv_keterangan">Keterangan</label>
                        <textarea id="cctv_keterangan" name="keterangan" class="field__control" rows="3" style="resize:vertical;" placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="inventory-create-actions">
            <button type="submit" class="btn btn--accent btn--full">ADD NEW</button>
        </div>
    </form>

    <?php else: ?>
    <?php /* ============ FORM: PERANGKAT LAIN (tidak berubah dari semula) ============ */ ?>
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
                                <option value="<?= e($division['division_code'] ?? ''); ?>"
                                        data-division-id="<?= e((string) ($division['id'] ?? '')); ?>"
                                        data-division-label="<?= e($division['division_label'] ?? ''); ?>">
                                    <?= e(($division['sheet_sumber'] ?? '') . ' - ' . ($division['division_label'] ?? '')); ?>
                                </option>
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
    <?php endif; ?>

</div>