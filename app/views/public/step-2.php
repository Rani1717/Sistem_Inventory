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
                <div class="support-banner__account">
                    <span class="support-banner__checkbox <?= !empty($data['email_verified']) ? 'is-checked' : ''; ?>"><i class="fa-solid fa-check"></i></span>
                    <span class="support-banner__mail"><?= e($data['user_email'] ?: 'Masukkan email aktif'); ?></span>
                    <a href="it-support.php" class="support-banner__swap">Ganti akun email</a>
                </div>
            </div>
            <form method="post" action="it-support.php?page=submit-final" enctype="multipart/form-data" class="support-form-card support-form-card--step2">
                <div class="support-form-card__body">
                    <?php if (!empty($data['errors_step2']['global'])): ?>
                        <small style="color:#d11a1a; margin-bottom:10px; display:block;"><?= e($data['errors_step2']['global']); ?></small>
                    <?php endif; ?>
                    <?php renderField('Deskripsi Kerusakan', 'deskripsi_kerusakan', 'textarea', 'Jelaskan kerusakan / kendala yang dialami', $data['step2']['deskripsi_kerusakan'], $data['errors_step2']['deskripsi_kerusakan'] ?? ''); ?>
                    <div class="field">
                        <label class="field__label">Dokumentasi Kerusakan</label>
                        <?php renderDocInput('Pilih File', 'dokumentasi_kerusakan'); ?>
                        <?php if (!empty($data['errors_step2']['dokumentasi_kerusakan'])): ?>
                            <small style="color:#d11a1a; margin-top:6px; display:block;"><?= e($data['errors_step2']['dokumentasi_kerusakan']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="support-form-card__actions" style="justify-content:space-between; display:flex; gap:12px;">
                    <a href="it-support.php" class="btn btn--ghost">BACK</a>
                    <button type="submit" class="btn btn--accent btn--xl">SEND</button>
                </div>
            </form>
        </div>
        <button type="button" class="wa-float"><i class="fa-brands fa-whatsapp"></i></button>
        <div class="support-footer">© PT. Pelindo Multi Terminal 2026 All Rights Reserved.</div>
    </div>
</section>
