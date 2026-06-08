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
    <?php /* ============ FORM: PERANGKAT LAIN dengan Mode A / Mode B ============ */ ?>
    <form method="post" enctype="multipart/form-data" class="form-shell inventory-create-form inventory-create-form--other inventory-create-form--responsive" id="formOtherNew">
        <input type="hidden" name="action" value="save_other_new">
        <input type="hidden" name="input_mode" id="inputModeHidden" value="linked">
        <input type="hidden" name="pc_row_id" id="pcRowIdHidden" value="">

        <div class="inventory-create-scroll">
            <div class="form-shell__body">

                <!-- ====== TOGGLE MODE A / MODE B ====== -->
                <div class="other-mode-toggle" id="otherModeToggle">
                    <button type="button" class="other-mode-toggle__btn is-active" id="btnModeA" data-mode="linked">
                        <span class="other-mode-toggle__icon"><i class="fa-solid fa-link"></i></span>
                        <span class="other-mode-toggle__text">
                            <strong>Terhubung ke PC</strong>
                            <small>Mouse, keyboard, monitor, dll</small>
                        </span>
                    </button>
                    <button type="button" class="other-mode-toggle__btn" id="btnModeB" data-mode="standalone">
                        <span class="other-mode-toggle__icon"><i class="fa-solid fa-box"></i></span>
                        <span class="other-mode-toggle__text">
                            <strong>Barang Mandiri</strong>
                            <small>Printer, proyektor, UPS, dll</small>
                        </span>
                    </button>
                </div>
                <!-- ====== END TOGGLE ====== -->

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

                    <!-- MODE A: Pilih PC (hidden di Mode B) -->
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

                    <!-- Unit Kerja (auto-fill dari PC di Mode A) -->
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

    <style>
    /* ---- Mode Toggle ---- */
    .other-mode-toggle {
        display: flex;
        gap: 10px;
        margin: 0 0 20px;
        padding: 0;
    }
    .other-mode-toggle__btn {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: border-color .18s, background .18s, box-shadow .18s;
        text-align: left;
    }
    .other-mode-toggle__btn:hover {
        border-color: #93c5fd;
        background: #eff6ff;
    }
    .other-mode-toggle__btn.is-active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .other-mode-toggle__icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .other-mode-toggle__btn.is-active .other-mode-toggle__icon { background: #2563eb; color: #fff; }
    .other-mode-toggle__text strong { display: block; font-size: .9rem; color: #1e293b; font-weight: 700; }
    .other-mode-toggle__text small  { display: block; font-size: .75rem; color: #64748b; margin-top: 1px; }
    @media (max-width: 540px) {
        .other-mode-toggle { flex-direction: column; }
    }
    </style>

    <script>
    (function () {
        var form        = document.getElementById('formOtherNew');
        var btnA        = document.getElementById('btnModeA');
        var btnB        = document.getElementById('btnModeB');
        var modeInput   = document.getElementById('inputModeHidden');
        var pcRowInput  = document.getElementById('pcRowIdHidden');
        var fieldPc     = document.getElementById('fieldPilihPc');
        var pcDropdown  = document.getElementById('pilih_pc_dropdown');
        var divisionSel = document.getElementById('division_code_other');
        var divLabel    = document.getElementById('division_label_other');
        var unitKerja   = document.getElementById('unit_kerja_other');

        var currentMode = 'linked'; // default Mode A

        function setMode(mode) {
            currentMode = mode;
            modeInput.value = mode;
            if (mode === 'linked') {
                btnA.classList.add('is-active');
                btnB.classList.remove('is-active');
                fieldPc.style.display = '';
                pcDropdown.required = true;
            } else {
                btnB.classList.add('is-active');
                btnA.classList.remove('is-active');
                fieldPc.style.display = 'none';
                pcDropdown.required = false;
                pcDropdown.value = '';
                pcRowInput.value = '';
            }
        }

        btnA.addEventListener('click', function () { setMode('linked'); });
        btnB.addEventListener('click', function () { setMode('standalone'); });

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
            if (currentMode === 'linked' && (!pcRowInput.value || pcRowInput.value === '0')) {
                e.preventDefault();
                alert('Pilih PC yang akan dihubungkan terlebih dahulu.');
                pcDropdown.focus();
            }
        });

        // Inisialisasi awal
        setMode('linked');
    })();
    </script>
    <?php endif; ?>

</div>