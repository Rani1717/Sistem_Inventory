<?php /** @var array $data */ ?>
<div class="dashboard-grid">
    <div class="dashboard-summary">
        <?php foreach ($data['dashboard_cards'] as $card): ?>
            <?php
                $recapKey  = (string) ($card['title'] ?? '');
                $safeTotal = (int) ($card['safe_total'] ?? 0);
                $badTotal  = (int) ($card['bad_total'] ?? 0);
                $isOk      = ($card['value_class'] ?? '') === 'ok';
            ?>
            <article class="metric-card dashboard-hover-card js-open-dashboard-recap" data-recap-key="<?= e($recapKey); ?>" tabindex="0" role="button" aria-label="Lihat rekap <?= e($recapKey); ?>">
                <h3><?= e($card['title']); ?></h3>
                <div class="metric-card__counts">
                    <span class="metric-card__count metric-card__count--ok"><?= e((string) $safeTotal); ?> Aman</span>
                    <span class="metric-card__count-sep">/</span>
                    <span class="metric-card__count metric-card__count--bad"><?= e((string) $badTotal); ?> Perlu Update</span>
                </div>
                <div class="metric-card__hint"><i class="fa-solid fa-chart-simple"></i> Lihat rekap</div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php
        $cctvItems = array_values(is_array($data['cctv_breakdown'] ?? null) ? $data['cctv_breakdown'] : []);
        $cctvTotal = array_sum(array_map(static fn($item) => (int) ($item['value'] ?? 0), $cctvItems));
    ?>
    <article class="donut-card dashboard-hover-card dashboard-cctv-card js-open-modal" data-modal="cctvModal" tabindex="0" role="button" aria-label="Lihat detail CCTV">
        <div class="donut-card__title-row">
            <h2>JUMLAH CCTV<br>LAPANGAN</h2>
            <span class="donut-card__edit-hint"><i class="fa-solid fa-list"></i> Detail</span>
        </div>
        <div class="donut-card__content donut-card__content--cctv">
            <div class="donut-card__chart-wrap donut-card__chart-wrap--cctv">
                <canvas id="cctvChart"></canvas>
                <span class="donut-card__center"><?= e((string) $cctvTotal); ?></span>
            </div>
        </div>
    </article>

    <?php
        $pcChart       = $data['pc_chart'] ?? ['labels' => [], 'aktif' => [], 'rusak' => [], 'total' => 0, 'full_labels' => [], 'division_urls' => []];
        $pcTotal       = (int) ($pcChart['total'] ?? 0);
        $pcLabels      = $pcChart['labels'] ?? [];
        $pcFullLabels  = $pcChart['full_labels'] ?? $pcLabels;
        $pcAktif       = $pcChart['aktif'] ?? [];
        $pcRusak       = $pcChart['rusak'] ?? [];
        $pcUrls        = $pcChart['division_urls'] ?? [];
        $pcDonutValues = [];
        foreach ($pcLabels as $i => $label) {
            $pcDonutValues[] = (int) ($pcAktif[$i] ?? 0) + (int) ($pcRusak[$i] ?? 0);
        }
        $pcColors = ['#5B8DEF','#6FCF97','#F2A541','#34B3D8','#F58B82','#7D72F8','#3AA0FF','#6D5BD0','#41B8D5','#F3A43B','#4C7BE8','#E879A0'];
    ?>
    <article class="donut-card dashboard-hover-card dashboard-cctv-card dashboard-pc-card js-open-modal" data-modal="pcModal" tabindex="0" role="button" aria-label="Lihat rekap PC per divisi">
        <div class="donut-card__title-row">
            <h2>JUMLAH PC<br>PER DIVISI</h2>
            <span class="donut-card__edit-hint"><i class="fa-solid fa-chart-pie"></i> Rekap</span>
        </div>
        <div class="donut-card__content donut-card__content--cctv">
            <div class="donut-card__chart-wrap donut-card__chart-wrap--cctv">
                <canvas id="pcDivisiChart"></canvas>
                <span class="donut-card__center"><?= e((string) $pcTotal); ?></span>
            </div>
        </div>
    </article>

    <?php
        $complaintChart  = $data['complaint_chart'] ?? [];
        $complaintPeriod = (string) ($complaintChart['period_label'] ?? (($complaintChart['month'] ?? '') . ' ' . ($complaintChart['year'] ?? '')));
        $complaintItems  = is_array($complaintChart['items'] ?? null) ? $complaintChart['items'] : [];
        $complaintTotal  = (int) ($complaintChart['total'] ?? 0);
    ?>
    <article class="chart-card chart-card--wide chart-card--complaints dashboard-hover-card" tabindex="0">
        <div class="complaint-stat-head">
            <div><h2>STATISTIK KELUHAN<br>INVENTARIS</h2></div>
            <div class="complaint-stat-head__period">
                <span><?= e(trim($complaintPeriod) !== '' ? $complaintPeriod : date('F Y')); ?></span>
                <strong><?= e((string) $complaintTotal); ?></strong>
                <small>Total Keluhan</small>
            </div>
        </div>
        <div class="complaint-chart-wrap"><canvas id="keluhanChart"></canvas></div>
        <?php if ($complaintItems): ?>
            <div class="complaint-division-strip" aria-label="Jumlah keluhan per divisi periode berjalan">
                <?php foreach ($complaintItems as $item): ?>
                    <div class="complaint-division-pill" title="<?= e((string) ($item['label'] ?? '')); ?>">
                        <span><?= e((string) (($item['short_label'] ?? '') ?: ($item['label'] ?? '-'))); ?></span>
                        <strong><?= e((string) ((int) ($item['total'] ?? 0))); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="padding:10px 16px;font-size:0.8rem;color:#94a3b8;text-align:center;">Belum ada keluhan bulan ini.</div>
        <?php endif; ?>
    </article>

    <article class="chart-card chart-card--narrow chart-card--flow dashboard-hover-card" tabindex="0">
        <h2>ARUS INVENTARIS</h2>
        <div class="inventory-flow-chart-wrap"><canvas id="arusChart"></canvas></div>
        <div class="chart-month"><span></span><?= e(($data['inventory_flow']['month'] ?? '') . ' ' . ($data['inventory_flow']['year'] ?? '')); ?></div>
    </article>
</div>


<?php $dashboardRecap = is_array($data['dashboard_recap'] ?? null) ? $data['dashboard_recap'] : []; ?>
<div id="dashboardRecapModal" class="modal dashboard-recap-modal" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog dashboard-recap-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="dashboardRecapTitle">
        <div class="modal__header dashboard-recap-modal__header">
            <div>
                <span class="dashboard-recap-modal__eyebrow">Rekap Dashboard</span>
                <h2 id="dashboardRecapTitle">Rekap Data</h2>
            </div>
            <button type="button" class="modal__close js-close-modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="dashboard-recap-modal__body">
            <div class="dashboard-recap-total-card"><span>Total Data</span><strong id="dashboardRecapTotal">0</strong></div>
            <div class="dashboard-recap-groups" id="dashboardRecapGroups"></div>
            <div class="dashboard-recap-section">
                <h3>Detail nilai terbanyak</h3>
                <div class="dashboard-recap-table-wrap">
                    <table class="dashboard-recap-table">
                        <thead><tr><th>Nilai</th><th>Jumlah</th><th>Status</th></tr></thead>
                        <tbody id="dashboardRecapTopValues"></tbody>
                    </table>
                </div>
            </div>
            <div class="dashboard-recap-section" id="dashboardRecapDivisionSection" hidden>
                <h3 id="dashboardRecapDivisionTitle">Rekap per divisi</h3>
                <div class="dashboard-recap-table-wrap">
                    <table class="dashboard-recap-table dashboard-recap-table--wide" id="dashboardRecapDivisionTable">
                        <thead id="dashboardRecapDivisionHead"></thead>
                        <tbody id="dashboardRecapDivisionRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /* ── Modal Detail CCTV (Accordion) ── */ ?>
<div id="cctvModal" class="modal cctv-modal" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cctvModalTitle">
        <div class="modal__header">
            <h2 id="cctvModalTitle">Detail CCTV Lapangan</h2>
            <button type="button" class="modal__close js-close-modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cctv-modal__body cctv-modal__body--accordion">
            <?php if (empty($cctvItems)): ?>
                <div class="cctv-accordion__empty">Belum ada data CCTV.</div>
            <?php else: ?>
                <div class="cctv-accordion" id="cctvAccordion">
                    <?php foreach ($cctvItems as $itemIdx => $item): ?>
                        <?php $cameras = is_array($item['cameras'] ?? null) ? $item['cameras'] : []; ?>
                        <div class="cctv-accordion__item" id="cctv-loc-<?= $itemIdx; ?>">
                            <button type="button" class="cctv-accordion__header js-cctv-accordion-toggle" aria-expanded="false" data-target="cctv-body-<?= $itemIdx; ?>">
                                <span class="cctv-accordion__dot" style="background:<?= e($item['color']); ?>"></span>
                                <span class="cctv-accordion__label"><?= e($item['label']); ?></span>
                                <span class="cctv-accordion__count"><?= e((string) $item['value']); ?> unit</span>
                                <i class="fa-solid fa-chevron-down cctv-accordion__chevron"></i>
                            </button>
                            <div class="cctv-accordion__body" id="cctv-body-<?= $itemIdx; ?>" hidden>
                                <?php if (empty($cameras)): ?>
                                    <div class="cctv-camera-empty">
                                        Belum ada data kamera individual.
                                        <a href="index.php?page=inventory-other&inv_tab=cctv" class="cctv-camera-add-link">+ Tambah CCTV baru</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($cameras as $cam): ?>
                                        <div class="cctv-camera-row" id="cctv-cam-<?= (int)($cam['id']); ?>">
                                            <div class="cctv-camera-row__info">
                                                <span class="cctv-camera-row__name"><?= e($cam['nama']); ?></span>
                                                <span class="cctv-camera-row__status cctv-camera-row__status--<?= strtolower(e($cam['status'])); ?>"><?= e($cam['status']); ?></span>
                                            </div>
                                            <div class="cctv-camera-row__actions">
                                                <button type="button" class="cctv-camera-btn js-cctv-cam-edit" title="Edit"
                                                    data-cam-id="<?= (int)($cam['id']); ?>"
                                                    data-cam-nama="<?= e($cam['nama']); ?>"
                                                    data-cam-kode="<?= e($cam['kode']); ?>"
                                                    data-cam-lokasi="<?= e($cam['lokasi']); ?>"
                                                    data-cam-status="<?= e($cam['status']); ?>">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="cctv-camera-btn cctv-camera-btn--danger js-cctv-cam-delete" title="Hapus"
                                                    data-cam-id="<?= (int)($cam['id']); ?>"
                                                    data-cam-nama="<?= e($cam['nama']); ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                            <div class="cctv-camera-inline-edit" id="cctv-edit-<?= (int)($cam['id']); ?>" hidden>
                                                <form method="post" action="index.php?page=dashboard" class="cctv-inline-form">
                                                    <input type="hidden" name="dashboard_action" value="edit_cctv_camera">
                                                    <input type="hidden" name="cctv_cam_id" value="<?= (int)($cam['id']); ?>">
                                                    <div class="cctv-inline-form__row">
                                                        <label>Nama CCTV<input type="text" name="nama_cctv" value="<?= e($cam['nama']); ?>" required></label>
                                                        <label>Status
                                                            <select name="status">
                                                                <option value="AKTIF" <?= $cam['status'] === 'AKTIF' ? 'selected' : ''; ?>>Aktif</option>
                                                                <option value="RUSAK" <?= $cam['status'] === 'RUSAK' ? 'selected' : ''; ?>>Rusak</option>
                                                                <option value="NONAKTIF" <?= $cam['status'] === 'NONAKTIF' ? 'selected' : ''; ?>>Nonaktif</option>
                                                            </select>
                                                        </label>
                                                        <div class="cctv-inline-form__btns">
                                                            <button type="submit" class="btn btn--primary btn--sm">Simpan</button>
                                                            <button type="button" class="btn btn--ghost btn--sm js-cctv-cam-cancel" data-cam-id="<?= (int)($cam['id']); ?>">Batal</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="cctv-camera-add-row">
                                        <a href="index.php?page=inventory-other&inv_tab=cctv" class="cctv-camera-add-link"><i class="fa-solid fa-plus"></i> Tambah CCTV baru</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal__footer" style="justify-content:flex-end;">
            <button type="button" class="btn btn--primary btn--lg js-close-modal">Tutup</button>
        </div>
    </div>
</div>

<?php /* ── Modal Rekap PC per Divisi ── */ ?>
<div id="pcModal" class="modal cctv-modal" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pcModalTitle">
        <div class="modal__header">
            <h2 id="pcModalTitle">Rekap Jumlah PC Per Divisi</h2>
            <button type="button" class="modal__close js-close-modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cctv-modal__body">
            <div class="pc-modal-summary">
                <span><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#3dae4f;margin-right:4px;"></i>Aktif: <strong><?= array_sum($pcAktif); ?></strong></span>
                <span><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#f05252;margin-right:4px;"></i>Rusak: <strong><?= array_sum($pcRusak); ?></strong></span>
                <span>Total: <strong><?= $pcTotal; ?></strong> unit</span>
            </div>
            <div class="pc-modal-list">
                <?php foreach ($pcLabels as $i => $label): ?>
                    <?php
                        $colorPc  = $pcColors[$i % count($pcColors)];
                        $jmlAktif = (int) ($pcAktif[$i] ?? 0);
                        $jmlRusak = (int) ($pcRusak[$i] ?? 0);
                        $jmlTotal = $jmlAktif + $jmlRusak;
                        $hasRusak = $jmlRusak > 0;
                        $fullLbl  = (string) ($pcFullLabels[$i] ?? $label);
                        $divUrl   = (string) ($pcUrls[$i] ?? '#');
                    ?>
                    <a href="<?= e($divUrl); ?>" class="pc-modal-row<?= $hasRusak ? ' pc-modal-row--rusak' : ''; ?>">
                        <span class="pc-modal-row__label">
                            <i style="background:<?= e($colorPc); ?>"></i>
                            <?= e($fullLbl); ?>
                        </span>
                        <span class="pc-modal-row__stats">
                            <span style="color:#3dae4f;font-weight:600;">✓ <?= $jmlAktif; ?></span>
                            <?php if ($hasRusak): ?><span style="color:#f05252;font-weight:600;">✗ <?= $jmlRusak; ?></span><?php endif; ?>
                            <strong><?= $jmlTotal; ?></strong>
                        </span>
                        <i class="fa-solid fa-arrow-right pc-modal-row__arrow"></i>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($pcLabels)): ?>
                    <div style="padding:16px;color:#64748b;text-align:center;">Belum ada data PC.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal__footer" style="justify-content:flex-end;">
            <button type="button" class="btn btn--primary btn--lg js-close-modal">Tutup</button>
        </div>
    </div>
</div>

<style>
/* ── Metric Card: Counts ── */
.metric-card__counts {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 8px 0 6px;
    flex-wrap: wrap;
}
.metric-card__count { font-size: .95rem; font-weight: 700; }
.metric-card__count--ok  { color: #16a34a; }
.metric-card__count--bad { color: #dc2626; }
.metric-card__count-sep  { color: #94a3b8; font-size: .85rem; }

/* ── CCTV Accordion ── */
.cctv-modal__body--accordion { padding: 0; }
.cctv-accordion { display: flex; flex-direction: column; }
.cctv-accordion__item { border-bottom: 1px solid #e2e8f0; }
.cctv-accordion__header {
    width: 100%; display: flex; align-items: center; gap: 10px;
    padding: 13px 18px; background: none; border: none; cursor: pointer;
    text-align: left; transition: background .15s;
}
.cctv-accordion__header:hover { background: #f8fafc; }
.cctv-accordion__dot {
    width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
}
.cctv-accordion__label { flex: 1; font-weight: 600; font-size: .9rem; color: #1e293b; }
.cctv-accordion__count { font-size: .8rem; color: #64748b; white-space: nowrap; }
.cctv-accordion__chevron { font-size: .75rem; color: #94a3b8; transition: transform .2s; flex-shrink: 0; }
.cctv-accordion__header[aria-expanded="true"] .cctv-accordion__chevron { transform: rotate(180deg); }
.cctv-accordion__body { background: #f8fafc; }
.cctv-accordion__empty { padding: 14px 18px; font-size: .85rem; color: #64748b; }

/* Camera rows */
.cctv-camera-row {
    padding: 10px 18px; border-bottom: 1px solid #e2e8f0;
    display: flex; flex-direction: column; gap: 4px;
}
.cctv-camera-row:last-of-type { border-bottom: none; }
.cctv-camera-row__info { display: flex; align-items: center; gap: 10px; }
.cctv-camera-row__name { flex: 1; font-size: .85rem; color: #1e293b; font-weight: 500; }
.cctv-camera-row__status {
    font-size: .72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;
}
.cctv-camera-row__status--aktif   { background: #dcfce7; color: #15803d; }
.cctv-camera-row__status--rusak   { background: #fee2e2; color: #dc2626; }
.cctv-camera-row__status--nonaktif{ background: #f1f5f9; color: #64748b; }
.cctv-camera-row__actions { display: flex; gap: 6px; justify-content: flex-end; }
.cctv-camera-btn {
    padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 6px;
    background: #fff; cursor: pointer; font-size: .8rem; color: #475569;
    transition: all .15s;
}
.cctv-camera-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.cctv-camera-btn--danger:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.cctv-camera-add-row { padding: 10px 18px; }
.cctv-camera-empty { padding: 12px 18px; font-size: .82rem; color: #94a3b8; }
.cctv-camera-add-link { font-size: .8rem; color: #2563eb; text-decoration: none; }
.cctv-camera-add-link:hover { text-decoration: underline; }

/* Inline edit form */
.cctv-camera-inline-edit { padding: 10px 18px 14px; background: #f0f7ff; border-top: 1px solid #bfdbfe; }
.cctv-inline-form__row { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.cctv-inline-form__row label { display: flex; flex-direction: column; gap: 4px; font-size: .8rem; color: #475569; font-weight: 600; }
.cctv-inline-form__row input, .cctv-inline-form__row select {
    padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .85rem;
}
.cctv-inline-form__btns { display: flex; gap: 6px; align-items: flex-end; }

/* ── PC Modal ── */
.pc-modal-summary {
    display: flex; gap: 18px; padding: 12px 16px;
    background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    font-size: .83rem; color: #475569; flex-wrap: wrap;
}
.pc-modal-list { overflow-y: auto; max-height: 400px; }
.pc-modal-row {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-bottom: 1px solid #e2e8f0;
    text-decoration: none; color: inherit; transition: background .15s;
}
.pc-modal-row:hover { background: #f0f7ff; }
.pc-modal-row--rusak { background: #fff5f5; }
.pc-modal-row--rusak:hover { background: #fee2e2; }
.pc-modal-row__label {
    flex: 1; display: flex; align-items: center; gap: 8px;
    font-size: .88rem; font-weight: 600; color: #1e293b;
    white-space: normal; word-break: break-word;
}
.pc-modal-row__label i {
    display: inline-block; width: 12px; height: 12px;
    border-radius: 50%; flex-shrink: 0;
}
.pc-modal-row__stats { display: flex; gap: 10px; align-items: center; font-size: .82rem; }
.pc-modal-row__arrow { font-size: .75rem; color: #94a3b8; flex-shrink: 0; }

/* Recap table: tambah kolom status */
.dashboard-recap-table th:last-child,
.dashboard-recap-table td:last-child { text-align: center; }
</style>

<script>
(function () {
    var recapData = <?= json_encode($dashboardRecap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};
    var modal = document.getElementById('dashboardRecapModal');
    if (!modal) return;
    var title           = document.getElementById('dashboardRecapTitle');
    var total           = document.getElementById('dashboardRecapTotal');
    var groups          = document.getElementById('dashboardRecapGroups');
    var topValues       = document.getElementById('dashboardRecapTopValues');
    var divisionSection = document.getElementById('dashboardRecapDivisionSection');
    var divisionTitle   = document.getElementById('dashboardRecapDivisionTitle');
    var divisionHead    = document.getElementById('dashboardRecapDivisionHead');
    var divisionRows    = document.getElementById('dashboardRecapDivisionRows');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    // Untuk top_values, tandai yang "perlu update" dengan warna berbeda
    function renderTopValueRows(rows, recapKey) {
        if (!rows || !rows.length) return '<tr><td colspan="3">Belum ada data.</td></tr>';
        // Ambil nilai safe dari recap data (heuristik: safe = value yang ada di groups ok)
        return rows.map(function(row) {
            var label = escapeHtml(row.label || '-');
            var total = escapeHtml(row.total || 0);
            var statusHtml = '';
            // Heuristic: Windows 11 Pro → aman, else → perlu update (untuk SYSTEM OS)
            if (recapKey === 'SYSTEM OS') {
                var normalized = (row.label || '').toUpperCase().replace(/\s+/g,'');
                var isSafe = normalized.indexOf('WINDOWS11PRO') !== -1;
                statusHtml = isSafe
                    ? '<span style="color:#16a34a;font-size:.8rem;font-weight:700;">✓ Aman</span>'
                    : '<span style="color:#dc2626;font-size:.8rem;font-weight:700;">⚠ Perlu Update</span>';
            } else if (recapKey === 'PROCESSOR') {
                var n = (row.label || '').toUpperCase().replace(/\s+/g,'');
                var ok = n.indexOf('I5') !== -1 || n.indexOf('I7') !== -1 || n.indexOf('COREI5') !== -1 || n.indexOf('COREI7') !== -1;
                statusHtml = ok
                    ? '<span style="color:#16a34a;font-size:.8rem;font-weight:700;">✓ Aman</span>'
                    : '<span style="color:#f59e0b;font-size:.8rem;font-weight:700;">↑ Perlu Upgrade</span>';
            } else if (recapKey === 'MS OFFICE') {
                var n2 = (row.label || '').toUpperCase().replace(/\s+/g,'');
                var isLic = n2.indexOf('LICENSED') !== -1 && n2.indexOf('UNLICENSED') === -1;
                var isUnl = n2.indexOf('UNLICENSED') !== -1;
                statusHtml = isLic
                    ? '<span style="color:#16a34a;font-size:.8rem;">✓ Licensed</span>'
                    : (isUnl ? '<span style="color:#dc2626;font-size:.8rem;">✗ Unlicensed</span>' : '<span style="color:#94a3b8;font-size:.8rem;">—</span>');
            }
            return '<tr><td>' + label + '</td><td><strong>' + total + '</strong></td><td>' + statusHtml + '</td></tr>';
        }).join('');
    }

    function openRecap(key) {
        var item = recapData[key] || {};
        if (title) title.textContent = item.title ? ('Rekap ' + item.title) : 'Rekap Data';
        if (total) total.textContent = item.total || 0;
        if (groups) {
            var groupRows = item.groups || [];
            groups.innerHTML = groupRows.length ? groupRows.map(function(row) {
                return '<div class="dashboard-recap-pill dashboard-recap-pill--' + escapeHtml(row.type||'neutral') + '"><span>' + escapeHtml(row.label||'-') + '</span><strong>' + escapeHtml(row.total||0) + '</strong></div>';
            }).join('') : '<div class="dashboard-recap-empty">Belum ada rekap.</div>';
        }
        if (topValues) topValues.innerHTML = renderTopValueRows(item.top_values || [], key);

        if (divisionSection && divisionRows && divisionHead) {
            var divRows = item.division_rows || [];
            if (divRows.length) {
                divisionSection.hidden = false;
                if (key === 'MS OFFICE') {
                    if (divisionTitle) divisionTitle.textContent = 'Rekap MS Office per Divisi';
                    divisionHead.innerHTML = '<tr><th>Divisi</th><th>Total</th><th>Licensed</th><th>Unlicensed</th><th>Lainnya</th></tr>';
                    divisionRows.innerHTML = divRows.map(function(row) {
                        var hasUnlic = (row.unlicensed || 0) > 0;
                        return '<tr' + (hasUnlic ? ' style="background:#fff5f5"' : '') + '><td>' + escapeHtml(row.division||'-') + '</td><td><strong>' + escapeHtml(row.total||0) + '</strong></td><td style="color:#16a34a">' + escapeHtml(row.licensed||0) + '</td><td style="color:#dc2626">' + escapeHtml(row.unlicensed||0) + '</td><td>' + escapeHtml(row.other||0) + '</td></tr>';
                    }).join('');
                } else if (key === 'SYSTEM OS') {
                    if (divisionTitle) divisionTitle.textContent = 'Rekap Sistem Operasi per Divisi';
                    divisionHead.innerHTML = '<tr><th>Divisi</th><th>Total</th><th style="color:#16a34a">Aman</th><th style="color:#dc2626">Perlu Update</th></tr>';
                    divisionRows.innerHTML = divRows.map(function(row) {
                        var hasBad = (row.bad || 0) > 0;
                        return '<tr' + (hasBad ? ' style="background:#fff5f5"' : '') + '><td>' + escapeHtml(row.division||'-') + '</td><td><strong>' + escapeHtml(row.total||0) + '</strong></td><td style="color:#16a34a;font-weight:600">' + escapeHtml(row.safe||0) + '</td><td style="color:#dc2626;font-weight:600">' + escapeHtml(row.bad||0) + '</td></tr>';
                    }).join('');
                } else {
                    var label1 = key === 'PROCESSOR' ? 'Aman (i5+)' : 'Aman';
                    var label2 = key === 'PROCESSOR' ? 'Perlu Upgrade' : 'Perlu Upgrade';
                    if (divisionTitle) divisionTitle.textContent = 'Rekap ' + (item.title || key) + ' per Divisi';
                    divisionHead.innerHTML = '<tr><th>Divisi</th><th>Total</th><th style="color:#16a34a">' + label1 + '</th><th style="color:#dc2626">' + label2 + '</th></tr>';
                    divisionRows.innerHTML = divRows.map(function(row) {
                        var hasBad = (row.bad || 0) > 0;
                        return '<tr' + (hasBad ? ' style="background:#fff5f5"' : '') + '><td>' + escapeHtml(row.division||'-') + '</td><td><strong>' + escapeHtml(row.total||0) + '</strong></td><td style="color:#16a34a;font-weight:600">' + escapeHtml(row.safe||0) + '</td><td style="color:#dc2626;font-weight:600">' + escapeHtml(row.bad||0) + '</td></tr>';
                    }).join('');
                }
            } else {
                divisionSection.hidden = true;
                divisionRows.innerHTML = '';
            }
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden','false');
        document.body.classList.add('has-modal-open');
    }
    document.querySelectorAll('.js-open-dashboard-recap').forEach(function(card) {
        card.addEventListener('click', function() { openRecap(card.getAttribute('data-recap-key')||''); });
        card.addEventListener('keydown', function(e) {
            if (e.key==='Enter'||e.key===' ') { e.preventDefault(); openRecap(card.getAttribute('data-recap-key')||''); }
        });
    });
})();
</script>

<script>
/* ── CCTV Accordion JS ── */
(function () {
    document.querySelectorAll('.js-cctv-accordion-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-target');
            var body = document.getElementById(targetId);
            if (!body) return;
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            body.hidden = expanded;
        });
    });

    // Inline edit toggle
    document.querySelectorAll('.js-cctv-cam-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var camId = btn.getAttribute('data-cam-id');
            var editDiv = document.getElementById('cctv-edit-' + camId);
            if (editDiv) editDiv.hidden = !editDiv.hidden;
        });
    });

    // Batal inline edit
    document.querySelectorAll('.js-cctv-cam-cancel').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var camId = btn.getAttribute('data-cam-id');
            var editDiv = document.getElementById('cctv-edit-' + camId);
            if (editDiv) editDiv.hidden = true;
        });
    });

    // Delete kamera
    document.querySelectorAll('.js-cctv-cam-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var nama = btn.getAttribute('data-cam-nama') || 'kamera ini';
            var camId = btn.getAttribute('data-cam-id');
            if (!confirm('Hapus ' + nama + '?')) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?page=dashboard';
            var inputs = [
                ['dashboard_action', 'delete_cctv_camera'],
                ['cctv_cam_id', camId],
            ];
            inputs.forEach(function(pair) {
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = pair[0]; inp.value = pair[1];
                form.appendChild(inp);
            });
            document.body.appendChild(form);
            form.submit();
        });
    });
})();
</script>

<script>
(function () {
    var pcChartData = <?= json_encode([
        'labels' => $pcLabels,
        'values' => $pcDonutValues,
        'colors' => array_slice($pcColors, 0, count($pcLabels)),
    ], JSON_UNESCAPED_UNICODE); ?>;

    var canvas = document.getElementById('pcDivisiChart');
    if (!canvas || !pcChartData.labels || !pcChartData.labels.length) return;

    function initPcChart() {
        if (typeof Chart === 'undefined') { setTimeout(initPcChart, 100); return; }
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: pcChartData.labels,
                datasets: [{
                    data: pcChartData.values,
                    backgroundColor: pcChartData.colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' unit';
                            }
                        }
                    }
                }
            }
        });
    }
    initPcChart();
})();
</script>