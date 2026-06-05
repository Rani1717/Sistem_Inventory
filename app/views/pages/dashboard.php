<?php /** @var array $data */ ?>
<div class="dashboard-grid">
    <div class="dashboard-summary">
        <?php foreach ($data['dashboard_cards'] as $card): ?>
            <?php $recapKey = (string) ($card['title'] ?? ''); ?>
            <article class="metric-card dashboard-hover-card js-open-dashboard-recap" data-recap-key="<?= e($recapKey); ?>" tabindex="0" role="button" aria-label="Lihat rekap <?= e($recapKey); ?>">
                <h3><?= e($card['title']); ?></h3>
                <div class="metric-card__value metric-card__value--<?= e($card['value_class']); ?>"><?= e($card['value']); ?></div>
                <div class="metric-card__status"><?= e($card['status']); ?></div>
                <div class="metric-card__hint"><i class="fa-solid fa-chart-simple"></i> Lihat rekap</div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php
        $cctvItems = array_values(is_array($data['cctv_breakdown'] ?? null) ? $data['cctv_breakdown'] : []);
        $cctvTotal = array_sum(array_map(static fn($item) => (int) ($item['value'] ?? 0), $cctvItems));
    ?>
    <article class="donut-card dashboard-hover-card dashboard-cctv-card js-open-modal" data-modal="cctvModal" tabindex="0" role="button" aria-label="Kelola data CCTV">
        <div class="donut-card__title-row">
            <h2>JUMLAH CCTV<br>LAPANGAN</h2>
            <span class="donut-card__edit-hint"><i class="fa-solid fa-pen-to-square"></i> Kelola</span>
        </div>
        <div class="donut-card__content donut-card__content--cctv">
            <div class="donut-card__chart-wrap donut-card__chart-wrap--cctv">
                <canvas id="cctvChart"></canvas>
                <span class="donut-card__center"><?= e((string) $cctvTotal); ?></span>
            </div>
        </div>
    </article>

    <?php
        $pcChart       = $data['pc_chart'] ?? ['labels' => [], 'aktif' => [], 'rusak' => [], 'total' => 0];
        $pcTotal       = (int) ($pcChart['total'] ?? 0);
        $pcLabels      = $pcChart['labels'] ?? [];
        $pcAktif       = $pcChart['aktif'] ?? [];
        $pcRusak       = $pcChart['rusak'] ?? [];
        $pcDonutValues = [];
        foreach ($pcLabels as $i => $label) {
            $pcDonutValues[] = (int) ($pcAktif[$i] ?? 0) + (int) ($pcRusak[$i] ?? 0);
        }
        $pcColors = ['#5B8DEF','#6FCF97','#F2A541','#34B3D8','#F58B82','#7D72F8','#3AA0FF','#6D5BD0','#41B8D5','#F3A43B','#4C7BE8','#E879A0'];
    ?>
    <article class="donut-card dashboard-hover-card dashboard-pc-card js-open-modal" data-modal="pcModal" tabindex="0" role="button" aria-label="Lihat rekap PC per divisi">
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
                        <thead><tr><th>Nilai</th><th>Jumlah</th></tr></thead>
                        <tbody id="dashboardRecapTopValues"></tbody>
                    </table>
                </div>
            </div>
            <div class="dashboard-recap-section" id="dashboardRecapDivisionSection" hidden>
                <h3>Rekap MS Office per divisi</h3>
                <div class="dashboard-recap-table-wrap">
                    <table class="dashboard-recap-table dashboard-recap-table--wide">
                        <thead><tr><th>Divisi</th><th>Total</th><th>Licensed</th><th>Unlicensed</th><th>Lainnya</th></tr></thead>
                        <tbody id="dashboardRecapDivisionRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="cctvModal" class="modal cctv-modal" aria-hidden="true">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cctvModalTitle">
        <form method="post" action="index.php?page=dashboard" class="cctv-modal__form js-cctv-form">
            <input type="hidden" name="dashboard_action" value="save_cctv">
            <input type="hidden" name="cctv_id" id="cctvId" value="">
            <div class="modal__header">
                <h2 id="cctvModalTitle">Kelola Data CCTV</h2>
                <button type="button" class="modal__close js-close-modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="cctv-modal__body">
                <div class="cctv-modal__list">
                    <?php foreach ($cctvItems as $item): ?>
                        <button type="button" class="cctv-row js-edit-cctv"
                                data-id="<?= e((string) ($item['id'] ?? '')); ?>"
                                data-lokasi="<?= e($item['label']); ?>"
                                data-jumlah="<?= e((string) $item['value']); ?>"
                                data-color="<?= e($item['color']); ?>">
                            <span><i style="background: <?= e($item['color']); ?>"></i><?= e($item['label']); ?></span>
                            <strong><?= e((string) $item['value']); ?></strong>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="cctv-modal__fields">
                    <label>Lokasi CCTV<input type="text" name="cctv_lokasi" id="cctvLokasi" placeholder="Contoh: SAMUDERA" required></label>
                    <label>Jumlah<input type="number" name="cctv_jumlah" id="cctvJumlah" min="0" step="1" value="0" required></label>
                    <label>Warna Chart<input type="color" name="cctv_color" id="cctvColor" value="#5B8DEF" required></label>
                </div>
            </div>
            <div class="modal__footer modal__footer--between">
                <div class="modal__footer-group">
                    <button type="button" class="btn btn--ghost btn--lg js-reset-cctv">Tambah Baru</button>
                    <button type="submit" class="btn btn--danger btn--lg js-delete-cctv" name="dashboard_action" value="delete_cctv" disabled>Hapus</button>
                </div>
                <button type="submit" class="btn btn--primary btn--lg">Simpan</button>
            </div>
        </form>
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
            <div class="cctv-modal__list">
                <?php foreach ($pcLabels as $i => $label): ?>
                    <?php
                        $colorPc  = $pcColors[$i % count($pcColors)];
                        $jmlAktif = (int) ($pcAktif[$i] ?? 0);
                        $jmlRusak = (int) ($pcRusak[$i] ?? 0);
                        $jmlTotal = $jmlAktif + $jmlRusak;
                    ?>
                    <div class="cctv-row" style="cursor:default;">
                        <span>
                            <i style="background:<?= e($colorPc); ?>"></i>
                            <?= e($label); ?>
                        </span>
                        <span style="display:flex;gap:10px;align-items:center;font-size:0.82rem;">
                            <span style="color:#3dae4f;font-weight:600;">✓ <?= $jmlAktif; ?></span>
                            <span style="color:#f05252;font-weight:600;">✗ <?= $jmlRusak; ?></span>
                            <strong><?= $jmlTotal; ?></strong>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($pcLabels)): ?>
                    <div style="padding:16px;color:#64748b;text-align:center;">Belum ada data PC.</div>
                <?php endif; ?>
            </div>
            <div style="padding:14px 16px;border-top:1px solid #e2e8f0;display:flex;gap:18px;font-size:0.82rem;color:#475569;">
                <span><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#3dae4f;margin-right:4px;"></i>Aktif: <strong><?= array_sum($pcAktif); ?></strong></span>
                <span><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#f05252;margin-right:4px;"></i>Rusak: <strong><?= array_sum($pcRusak); ?></strong></span>
                <span>Total: <strong><?= $pcTotal; ?></strong> unit</span>
            </div>
        </div>
        <div class="modal__footer" style="justify-content:flex-end;">
            <button type="button" class="btn btn--primary btn--lg js-close-modal">Tutup</button>
        </div>
    </div>
</div>


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
    var divisionRows    = document.getElementById('dashboardRecapDivisionRows');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function renderRows(rows) {
        if (!rows || !rows.length) return '<tr><td colspan="2">Belum ada data.</td></tr>';
        return rows.map(function(row) {
            return '<tr><td>' + escapeHtml(row.label||'-') + '</td><td><strong>' + escapeHtml(row.total||0) + '</strong></td></tr>';
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
        if (topValues) topValues.innerHTML = renderRows(item.top_values || []);
        if (divisionSection && divisionRows) {
            var rows = item.division_rows || [];
            if (key === 'MS OFFICE' && rows.length) {
                divisionSection.hidden = false;
                divisionRows.innerHTML = rows.map(function(row) {
                    return '<tr><td>' + escapeHtml(row.division||'-') + '</td><td><strong>' + escapeHtml(row.total||0) + '</strong></td><td>' + escapeHtml(row.licensed||0) + '</td><td>' + escapeHtml(row.unlicensed||0) + '</td><td>' + escapeHtml(row.other||0) + '</td></tr>';
                }).join('');
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