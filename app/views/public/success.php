<section class="standalone support-screen">
    <div class="support-screen__bg"></div>
    <div class="support-screen__overlay"></div>
    <img src="<?= asset('images/danantara-logo.png'); ?>" alt="Danantara" class="support-logo support-logo--left">
    <img src="<?= asset('images/pelindo-logo.png'); ?>" alt="Pelindo" class="support-logo support-logo--right">

    <div class="support-wrapper">
        <div class="support-shell">
            <div class="support-banner support-banner--sticky">
                <h1>FORMULIR IT SUPPORT REQUEST</h1>
                <div class="support-banner__meta">TANGGAL : <?= e($data['date']); ?></div>
                <div class="support-banner__meta">JAM : <?= e($data['time']); ?></div>
            </div>
            <div class="support-form-card support-form-card--step2" style="text-align:left;">
                <div class="support-form-card__body">
                    <h2 style="margin-top:0;">Permintaan berhasil dikirim</h2>
                    <p><strong>No. Tiket:</strong> <?= e($data['success']['ticket_no'] ?? '-'); ?></p>
                    <p><strong>Nama:</strong> <?= e($data['success']['nama_pelapor'] ?? '-'); ?></p>
                    <p><strong>Divisi:</strong> <?= e($data['success']['divisi'] ?? '-'); ?></p>
                    <p><strong>Aset:</strong> <?= e($data['success']['aset'] ?? '-'); ?></p>
                    <p>Status awal tiket otomatis <strong>NOT YET</strong> dan akan muncul di laporan IT Support divisi IT.</p>
                </div>
                <div class="support-form-card__actions" style="display:flex; justify-content:flex-end;">
                    <a href="it-support.php" class="btn btn--accent btn--xl">BUAT TIKET BARU</a>
                </div>
            </div>
        </div>
    </div>
</section>
