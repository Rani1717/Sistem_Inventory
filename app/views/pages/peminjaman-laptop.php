<?php
/** @var array $data */
$flash          = $data['flash'] ?? null;
$stats          = $data['peminjaman_stats']  ?? ['total' => 0, 'dipinjam' => 0, 'kembali' => 0];
$rows           = $data['peminjaman_rows']   ?? [];
$belumKembali   = $data['peminjaman_belum_kembali'] ?? [];
$filterStatus   = $data['peminjaman_filter'] ?? '';
$filterSearch   = $data['peminjaman_search'] ?? '';
$uploadBase     = 'peminjaman_laptop/uploads/';
?>

<?php if (!empty($flash['message'])): ?>
<div class="log-toast log-toast--<?= e($flash['type'] ?? 'success'); ?> js-log-toast" role="status" aria-live="polite">
    <div class="log-toast__content">
        <strong><?= e(($flash['type'] ?? 'success') === 'error' ? 'Gagal' : 'Berhasil'); ?></strong>
        <span><?= e($flash['message']); ?></span>
    </div>
    <button type="button" class="log-toast__close js-close-log-toast" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark"></i></button>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     HEADER
════════════════════════════════════════════════════════ -->
<div class="detail-header detail-header--single-title">
    <h1>PEMINJAMAN INVENTARIS IT</h1>
</div>

<!-- ═══════════════════════════════════════════════════════
     STATISTIK CARDS
════════════════════════════════════════════════════════ -->
<div class="pinjam-stats">
    <div class="pinjam-stat-card pinjam-stat-card--blue">
        <div class="pinjam-stat-card__icon"><i class="fa-solid fa-laptop"></i></div>
        <div class="pinjam-stat-card__body">
            <span class="pinjam-stat-card__num"><?= e((string) $stats['total']); ?></span>
            <span class="pinjam-stat-card__label">Total Peminjaman</span>
        </div>
    </div>
    <div class="pinjam-stat-card pinjam-stat-card--red">
        <div class="pinjam-stat-card__icon"><i class="fa-solid fa-right-from-bracket"></i></div>
        <div class="pinjam-stat-card__body">
            <span class="pinjam-stat-card__num"><?= e((string) $stats['dipinjam']); ?></span>
            <span class="pinjam-stat-card__label">Sedang Dipinjam</span>
        </div>
    </div>
    <div class="pinjam-stat-card pinjam-stat-card--green">
        <div class="pinjam-stat-card__icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="pinjam-stat-card__body">
            <span class="pinjam-stat-card__num"><?= e((string) $stats['kembali']); ?></span>
            <span class="pinjam-stat-card__label">Sudah Dikembalikan</span>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FORM PEMINJAMAN & PENGEMBALIAN
════════════════════════════════════════════════════════ -->
<div class="pinjam-form-grid">

    <!-- FORM PEMINJAMAN -->
    <div class="pinjam-card">
        <div class="pinjam-card__header pinjam-card__header--blue">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Form Peminjaman IT</span>
        </div>
        <div class="pinjam-card__body">
            <form method="POST" action="index.php?page=peminjaman-laptop" id="formPeminjaman" onsubmit="return validateFormPinjam()">
                <input type="hidden" name="action" value="save_peminjaman">
                <div class="pinjam-card__cols">
                    <div class="pinjam-card__col-left">
                        <div class="pinjam-field">
                            <label for="pinjam_nama_barang">Nama Barang</label>
                            <input type="text" id="pinjam_nama_barang" name="nama_barang" class="pinjam-input" placeholder="Contoh: Laptop, Mouse, Proyektor" required>
                        </div>
                        <div class="pinjam-field">
                            <label for="pinjam_merk_barang">Merk Barang</label>
                            <input type="text" id="pinjam_merk_barang" name="merk_barang" class="pinjam-input" placeholder="Contoh: ASUS, Lenovo, DELL" required>
                        </div>
                        <div class="pinjam-field">
                            <label for="pinjam_nama_peminjam">Nama Peminjam</label>
                            <input type="text" id="pinjam_nama_peminjam" name="nama_peminjam" class="pinjam-input" placeholder="Nama lengkap peminjam" required>
                        </div>
                        <div class="pinjam-field">
                            <label for="pinjam_tanggal">Tanggal Peminjaman</label>
                            <input type="date" id="pinjam_tanggal" name="tanggal_peminjaman" class="pinjam-input" value="<?= e(date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="pinjam-card__col-right">
                        <div class="pinjam-field pinjam-field--photo">
                            <label>Foto Bukti Peminjaman <span class="pinjam-hint">(wajib)</span></label>
                            <div class="pinjam-webcam" id="webcamPinjam">
                                <video id="videoPinjam" autoplay playsinline class="pinjam-webcam__video"></video>
                                <canvas id="canvasPinjam" class="pinjam-webcam__canvas" style="display:none;"></canvas>
                                <div class="pinjam-webcam__overlay" id="overlayPinjam">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Kamera belum aktif</span>
                                </div>
                            </div>
                            <input type="hidden" name="bukti_peminjaman" id="buktPinjamData">
                            <div class="pinjam-webcam-actions">
                                <button type="button" class="pinjam-btn pinjam-btn--snap" id="btnSnapPinjam" onclick="takePhoto('pinjam')" style="display:none;">
                                    <i class="fa-solid fa-camera"></i> Ambil Foto
                                </button>
                                <button type="button" class="pinjam-btn pinjam-btn--reset" id="btnResetPinjam" onclick="resetPhoto('pinjam')" style="display:none;">
                                    <i class="fa-solid fa-rotate-left"></i> Ulangi
                                </button>
                            </div>
                            <div class="pinjam-photo-status" id="statusPinjam"></div>
                        </div>
                    </div>
                </div>
                <div class="pinjam-actions">
                    <button type="submit" class="pinjam-btn pinjam-btn--primary">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Peminjaman
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FORM PENGEMBALIAN -->
    <div class="pinjam-card">
        <div class="pinjam-card__header pinjam-card__header--green">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Form Pengembalian IT</span>
        </div>
        <div class="pinjam-card__body">
            <form method="POST" action="index.php?page=peminjaman-laptop" id="formPengembalian" onsubmit="return validateFormKembali()">
                <input type="hidden" name="action" value="save_pengembalian">
                <div class="pinjam-card__cols">
                    <div class="pinjam-card__col-left">
                        <div class="pinjam-field">
                            <label for="kembali_id">Pilih Barang yang Dikembalikan</label>
                            <select id="kembali_id" name="id_peminjaman" class="pinjam-input" required>
                                <option value="">-- Pilih barang --</option>
                                <?php foreach ($belumKembali as $bk): ?>
                                <option value="<?= e((string) $bk['id']); ?>">
                                    <?= e($bk['nama_barang'] . ' — ' . $bk['merk_barang'] . ' (' . $bk['nama_peminjam'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($belumKembali)): ?>
                            <p class="pinjam-empty-note"><i class="fa-solid fa-circle-info"></i> Tidak ada barang yang sedang dipinjam.</p>
                            <?php endif; ?>
                        </div>
                        <div class="pinjam-field">
                            <label for="kembali_tanggal">Tanggal Pengembalian</label>
                            <input type="date" id="kembali_tanggal" name="tanggal_pengembalian" class="pinjam-input" value="<?= e(date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="pinjam-card__col-right">
                        <div class="pinjam-field pinjam-field--photo">
                            <label>Foto Bukti Pengembalian <span class="pinjam-hint">(wajib)</span></label>
                            <div class="pinjam-webcam" id="webcamKembali">
                                <video id="videoKembali" autoplay playsinline class="pinjam-webcam__video"></video>
                                <canvas id="canvasKembali" class="pinjam-webcam__canvas" style="display:none;"></canvas>
                                <div class="pinjam-webcam__overlay" id="overlayKembali">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Kamera belum aktif</span>
                                </div>
                            </div>
                            <input type="hidden" name="bukti_pengembalian" id="buktiKembaliData">
                            <div class="pinjam-webcam-actions">
                                <button type="button" class="pinjam-btn pinjam-btn--snap" id="btnSnapKembali" onclick="takePhoto('kembali')" style="display:none;">
                                    <i class="fa-solid fa-camera"></i> Ambil Foto
                                </button>
                                <button type="button" class="pinjam-btn pinjam-btn--reset" id="btnResetKembali" onclick="resetPhoto('kembali')" style="display:none;">
                                    <i class="fa-solid fa-rotate-left"></i> Ulangi
                                </button>
                            </div>
                            <div class="pinjam-photo-status" id="statusKembali"></div>
                        </div>
                    </div>
                </div>
                <div class="pinjam-actions">
                    <button type="submit" class="pinjam-btn pinjam-btn--success">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.pinjam-form-grid -->

<!-- ═══════════════════════════════════════════════════════
     TABEL RIWAYAT
════════════════════════════════════════════════════════ -->
<div class="pinjam-table-section">
    <div class="pinjam-table-toolbar">
        <h2 class="pinjam-table-title"><i class="fa-solid fa-table-list"></i> Riwayat Peminjaman</h2>
        <div class="pinjam-table-controls">
            <form method="get" action="index.php" class="pinjam-filter-form">
                <input type="hidden" name="page" value="peminjaman-laptop">
                <div class="mini-search mini-search--input">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="pinjam_search" value="<?= e($filterSearch); ?>" placeholder="Cari nama, merk, peminjam..." id="pinjamSearch">
                </div>
                <select name="pinjam_filter" class="pinjam-select" onchange="this.form.submit()">
                    <option value=""     <?= $filterStatus === '' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="dipinjam"    <?= $filterStatus === 'dipinjam' ? 'selected' : ''; ?>>Sedang Dipinjam</option>
                    <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : ''; ?>>Sudah Dikembalikan</option>
                </select>
                <button type="submit" class="pinjam-btn pinjam-btn--ghost">Terapkan</button>
            </form>
            <a href="index.php?page=peminjaman-laptop&action=export_peminjaman"
               class="pinjam-btn pinjam-btn--export"
               title="Export ke Excel">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="table-wrap">
        <div class="pinjam-table-scroll">
            <table class="data-table pinjam-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Merk</th>
                        <th>Peminjam</th>
                        <th>Tgl Pinjam</th>
                        <th>Bukti Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Bukti Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): ?>
                    <?php $no = 1; foreach ($rows as $row):
                        $sudahKembali = !empty($row['tanggal_pengembalian']) && $row['tanggal_pengembalian'] !== '0000-00-00';
                    ?>
                    <tr>
                        <td><?= e((string) $no++); ?></td>
                        <td><?= e($row['nama_barang']); ?></td>
                        <td><?= e($row['merk_barang']); ?></td>
                        <td><?= e($row['nama_peminjam']); ?></td>
                        <td><?= e($row['tanggal_peminjaman']); ?></td>
                        <td class="pinjam-td-img">
                            <?php if (!empty($row['bukti_peminjaman'])): ?>
                            <img src="<?= e($uploadBase . $row['bukti_peminjaman']); ?>"
                                 class="pinjam-thumb"
                                 alt="Bukti pinjam"
                                 onclick="openImgModal(this.src)"
                                 loading="lazy">
                            <?php else: ?>
                            <span class="pinjam-noimg">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($sudahKembali ? $row['tanggal_pengembalian'] : '—'); ?></td>
                        <td class="pinjam-td-img">
                            <?php if (!empty($row['bukti_pengembalian'])): ?>
                            <img src="<?= e($uploadBase . $row['bukti_pengembalian']); ?>"
                                 class="pinjam-thumb"
                                 alt="Bukti kembali"
                                 onclick="openImgModal(this.src)"
                                 loading="lazy">
                            <?php else: ?>
                            <span class="pinjam-noimg">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge--<?= $sudahKembali ? 'in' : 'out'; ?>">
                                <?= $sudahKembali ? 'Dikembalikan' : 'Dipinjam'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button type="button"
                                        class="btn-action btn-action--edit js-pinjam-edit-btn"
                                        data-id="<?= e((string) $row['id']); ?>"
                                        data-nama-barang="<?= e($row['nama_barang']); ?>"
                                        data-merk-barang="<?= e($row['merk_barang']); ?>"
                                        data-nama-peminjam="<?= e($row['nama_peminjam']); ?>"
                                        data-tgl-pinjam="<?= e($row['tanggal_peminjaman']); ?>"
                                        data-tgl-kembali="<?= e($sudahKembali ? $row['tanggal_pengembalian'] : ''); ?>">Edit</button>
                                <form method="POST" action="index.php?page=peminjaman-laptop"
                                      class="js-confirm-delete"
                                      data-confirm-message="Hapus data peminjaman ini?">
                                    <input type="hidden" name="action" value="delete_peminjaman">
                                    <input type="hidden" name="id" value="<?= e((string) $row['id']); ?>">
                                    <button type="submit" class="btn-action btn-action--delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty-state">
                            <?= $filterStatus || $filterSearch ? 'Tidak ada data yang cocok dengan filter.' : 'Belum ada data peminjaman.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL EDIT
════════════════════════════════════════════════════════ -->
<div class="log-modal" id="pinjamEditModal" hidden aria-hidden="true">
    <div class="log-modal__dialog">
        <div class="log-modal__header">
            <h3>Edit Data Peminjaman</h3>
            <button type="button" class="icon-round js-close-pinjam-modal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?page=peminjaman-laptop" class="log-modal__form">
            <input type="hidden" name="action" value="edit_peminjaman">
            <input type="hidden" name="edit_id" id="editPinjamId">
            <div class="log-modal__grid">
                <label><span>Nama Barang</span><input type="text" name="edit_nama_barang" id="editNamaBarang" required></label>
                <label><span>Merk Barang</span><input type="text" name="edit_merk_barang" id="editMerkBarang" required></label>
                <label><span>Nama Peminjam</span><input type="text" name="edit_nama_peminjam" id="editNamaPeminjam" required></label>
                <label><span>Tanggal Peminjaman</span><input type="date" name="edit_tanggal_peminjaman" id="editTglPinjam" required></label>
                <label><span>Tanggal Pengembalian</span><input type="date" name="edit_tanggal_pengembalian" id="editTglKembali"></label>
            </div>
            <div class="log-modal__actions">
                <button type="button" class="btn btn--ghost js-close-pinjam-modal">Batal</button>
                <button type="submit" class="btn btn--primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL LIHAT FOTO
════════════════════════════════════════════════════════ -->
<div class="pinjam-img-modal" id="pinjamImgModal" hidden onclick="closeImgModal()">
    <div class="pinjam-img-modal__inner">
        <img src="" alt="Bukti peminjaman" id="pinjamImgBig">
        <button class="pinjam-img-modal__close" onclick="closeImgModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════ -->
<script>
/* ─── WEBCAM ─── */
const streams = {};

async function startCam(side) {
    const overlay = document.getElementById('overlay' + cap(side));
    if (overlay) {
        overlay.style.display = '';
        overlay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Mengaktifkan kamera...</span>';
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        streams[side] = stream;
        const video   = document.getElementById('video' + cap(side));
        video.srcObject = stream;
        video.style.display = 'block';
        if (overlay) {
            overlay.style.display = 'none';
        }
        document.getElementById('btnSnap' + cap(side)).style.display = '';
    } catch(e) {
        console.warn('Kamera tidak dapat diakses: ' + e.message);
        if (overlay) {
            overlay.innerHTML = '<i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i><span style="margin-top: 0.5rem; color: #ef4444; text-align: center; padding: 0 10px;">Akses kamera ditolak / tidak ditemukan</span>';
        }
    }
}

function takePhoto(side) {
    const video  = document.getElementById('video' + cap(side));
    const canvas = document.getElementById('canvas' + cap(side));
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Always draw image flipped horizontally to un-mirror it
    ctx.save();
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    ctx.restore();
    
    const data = canvas.toDataURL('image/png');
    const inputId = side === 'pinjam' ? 'buktPinjamData' : 'buktiKembaliData';
    document.getElementById(inputId).value = data;

    // Tampilkan canvas, sembunyikan video
    video.style.display   = 'none';
    canvas.style.display  = 'block';
    document.getElementById('btnSnap' + cap(side)).style.display  = 'none';
    document.getElementById('btnReset' + cap(side)).style.display = '';
    document.getElementById('status' + cap(side)).innerHTML =
        '<span class="pinjam-photo-ok"><i class="fa-solid fa-circle-check"></i> Foto berhasil diambil</span>';

    // Hentikan stream kamera
    if (streams[side]) {
        streams[side].getTracks().forEach(t => t.stop());
    }
}

function resetPhoto(side) {
    const video   = document.getElementById('video' + cap(side));
    const canvas  = document.getElementById('canvas' + cap(side));
    const inputId = side === 'pinjam' ? 'buktPinjamData' : 'buktiKembaliData';
    document.getElementById(inputId).value = '';
    canvas.style.display  = 'none';
    video.style.display   = 'none';
    const overlay = document.getElementById('overlay' + cap(side));
    if (overlay) {
        overlay.style.display = '';
        overlay.innerHTML = '<i class="fa-solid fa-camera"></i><span>Kamera belum aktif</span>';
    }
    document.getElementById('btnSnap' + cap(side)).style.display  = 'none';
    document.getElementById('btnReset' + cap(side)).style.display = 'none';
    document.getElementById('status' + cap(side)).innerHTML = '';

    // Auto-start camera again
    startCam(side);
}

function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

// Auto-start cameras on page load
window.addEventListener('DOMContentLoaded', () => {
    startCam('pinjam');
    startCam('kembali');
});

/* ─── VALIDASI FORM ─── */
function validateFormPinjam() {
    if (!document.getElementById('buktPinjamData').value) {
        alert('Silakan ambil foto bukti peminjaman terlebih dahulu!');
        return false;
    }
    return true;
}

function validateFormKembali() {
    if (!document.getElementById('buktiKembaliData').value) {
        alert('Silakan ambil foto bukti pengembalian terlebih dahulu!');
        return false;
    }
    return true;
}

/* ─── SEARCH LIVE ─── */
document.getElementById('pinjamSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.pinjam-table tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

/* ─── MODAL EDIT ─── */
document.querySelectorAll('.js-pinjam-edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editPinjamId').value       = this.dataset.id;
        document.getElementById('editNamaBarang').value     = this.dataset.namaBarang;
        document.getElementById('editMerkBarang').value     = this.dataset.merkBarang;
        document.getElementById('editNamaPeminjam').value   = this.dataset.namaPeminjam;
        document.getElementById('editTglPinjam').value      = this.dataset.tglPinjam;
        document.getElementById('editTglKembali').value     = this.dataset.tglKembali;
        const modal = document.getElementById('pinjamEditModal');
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
    });
});

document.querySelectorAll('.js-close-pinjam-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = document.getElementById('pinjamEditModal');
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
    });
});

/* ─── MODAL FOTO ─── */
function openImgModal(src) {
    document.getElementById('pinjamImgBig').src = src;
    const m = document.getElementById('pinjamImgModal');
    m.removeAttribute('hidden');
}
function closeImgModal() {
    document.getElementById('pinjamImgModal').setAttribute('hidden', '');
}

/* ─── TOAST ─── */
const toastEl = document.querySelector('.js-log-toast');
if (toastEl) {
    setTimeout(() => toastEl.style.opacity = '0', 4000);
    setTimeout(() => toastEl.remove(), 4600);
}
document.querySelector('.js-close-log-toast')?.addEventListener('click', () => {
    const t = document.querySelector('.js-log-toast');
    if (t) { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
});

/* ─── CONFIRM DELETE ─── */
// Ditangani secara global oleh app.js menggunakan window.spmtConfirm kustom
</script>
