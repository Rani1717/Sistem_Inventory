<section class="standalone support-screen">
    <div class="support-screen__bg"></div>
    <div class="support-screen__overlay"></div>
    <img src="<?= asset('images/danantara-logo.png'); ?>" alt="Danantara" class="support-logo support-logo--left">
    <img src="<?= asset('images/pelindo-logo.png'); ?>" alt="Pelindo" class="support-logo support-logo--right">

    <a href="<?= e($data['whatsapp_url'] ?? 'https://wa.me/6281399545044?text=Halo'); ?>" class="wa-float wa-float--support" target="_blank" rel="noopener noreferrer" aria-label="Hubungi WhatsApp IT Support">
        <span class="wa-float__button"><i class="fa-brands fa-whatsapp"></i></span>
    </a>

    <div class="support-wrapper">
        <div class="support-shell support-shell--single">
            <div class="support-banner support-banner--sticky support-banner--compact">
                <div class="support-banner__top">
                    <h1>FORMULIR IT SUPPORT REQUEST</h1>
                    <div class="support-banner__datetime">
                        <div class="support-banner__meta-wrap support-banner__meta-wrap--center">
                            <div class="support-banner__meta">TANGGAL : <span id="realtimeDate"><?= e($data['date']); ?></span></div>
                            <div class="support-banner__meta">JAM : <span id="realtimeTime"><?= e($data['time']); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="support-banner__bottom">
                    <div class="support-banner__account-card support-banner__account-card--inline support-banner__account-card--plain">
                        <div class="support-banner__account-row support-banner__account-row--header"><br><br>
                            <span class="support-banner__checkbox <?= !empty($data['email_verified']) ? 'is-checked' : ''; ?>">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <span class="support-banner__mail"><?= e($data['user_email'] ?: 'Masukkan email aktif'); ?></span>
                            <div class="support-banner__account-links">
                                <a href="#email_pelapor" class="support-banner__swap">Ganti akun</a>
                                <a href="index.php?page=login" class="support-banner__swap">Login admin</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" action="it-support.php?page=submit-final" enctype="multipart/form-data" class="support-form-card support-form-card--single js-support-form">
                <input type="hidden" name="submitted_tanggal" id="submittedTanggal" value="">
                <input type="hidden" name="submitted_jam" id="submittedJam" value="">
                <div class="support-form-card__body support-form-card__body--single">
                    <?php if (!empty($data['errors']['global'])): ?>
                        <small style="color:#d11a1a; margin-bottom:10px; display:block;"><?= e($data['errors']['global']); ?></small>
                    <?php endif; ?>

                    <?php renderField('Email Pelapor', 'email_pelapor', 'email', 'nama@gmail.com', $data['form']['email_pelapor'], $data['errors']['email_pelapor'] ?? ''); ?>
                    <div class="support-email-box support-email-box--plain">
                        <label class="support-email-check">
                            <input type="checkbox" name="email_verified" value="1" class="js-email-verified-toggle" <?= !empty($data['form']['email_verified']) ? 'checked' : ''; ?>>
                            <span>Saya memastikan email aktif di atas benar dan dapat diganti sebelum dikirim.</span>
                        </label>
                        <?php if (!empty($data['errors']['email_verified'])): ?>
                            <small style="color:#d11a1a; display:block;"><?= e($data['errors']['email_verified']); ?></small>
                        <?php endif; ?>
                    </div>

                    <?php renderField('Nama Pelapor', 'nama_pelapor', 'text', 'Masukkan nama pelapor', $data['form']['nama_pelapor'], $data['errors']['nama_pelapor'] ?? ''); ?>
                    <div class="field">
                        <label class="field__label" for="divisi">Divisi</label>
                        <select id="divisi" name="divisi" class="field__control" required>
                            <option value="">Pilih divisi</option>
                            <?php foreach (($data['division_options'] ?? []) as $divisionOption): ?>
                                <?php
                                $divisionLabel = (string) ($divisionOption['label'] ?? '');
                                $divisionGroup = trim((string) ($divisionOption['group'] ?? ''));
                                $divisionText = $divisionGroup !== '' ? $divisionLabel . ' - ' . $divisionGroup : $divisionLabel;
                                ?>
                                <option value="<?= e($divisionLabel); ?>" <?= (string) ($data['form']['divisi'] ?? '') === $divisionLabel ? 'selected' : ''; ?>><?= e($divisionText); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($data['errors']['divisi'])): ?>
                            <small style="color:#d11a1a; margin-top:6px; display:block;"><?= e($data['errors']['divisi']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php renderField('Aset Yang Perlu Diperbaiki', 'aset_yang_perlu_diperbaiki', 'text', 'Contoh: PC, Printer, CCTV', $data['form']['aset_yang_perlu_diperbaiki'], $data['errors']['aset_yang_perlu_diperbaiki'] ?? ''); ?>
                    <?php renderField('Lokasi Perbaikan', 'lokasi_perbaikan', 'text', 'Masukkan lokasi perbaikan', $data['form']['lokasi_perbaikan'], $data['errors']['lokasi_perbaikan'] ?? ''); ?>
                    <?php renderField('Deskripsi Kerusakan', 'deskripsi_kerusakan', 'textarea', 'Jelaskan kerusakan / kendala yang dialami', $data['form']['deskripsi_kerusakan'], $data['errors']['deskripsi_kerusakan'] ?? ''); ?>

                    <div class="field">
                        <label class="field__label">Dokumentasi Kerusakan</label>
                        <?php renderDocInput('Pilih File', 'dokumentasi_kerusakan', true, $data['form']['existing_dokumentasi_kerusakan'] ?? ''); ?>
                        <?php if (!empty($data['errors']['dokumentasi_kerusakan'])): ?>
                            <small style="color:#d11a1a; margin-top:6px; display:block;"><?= e($data['errors']['dokumentasi_kerusakan']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="support-form-card__actions support-form-card__actions--single">
                    <button type="submit" class="btn btn--accent btn--xl support-submit-btn">SEND</button>
                </div>
            </form>
        </div>
        <div class="support-footer">© PT. Pelindo Multi Terminal 2026 All Rights Reserved.</div>
    </div>

    <?php if (!empty($data['flash']['message'])): ?>
        <div class="support-popup is-open support-popup--<?= e($data['flash']['type'] ?? 'success'); ?>" id="supportPopup">
            <div class="support-popup__backdrop"></div>
            <div class="support-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="supportPopupTitle">
                <button type="button" class="support-popup__close" data-popup-close aria-label="Tutup popup"><i class="fa-solid fa-xmark"></i></button>
                <div class="support-popup__icon"><i class="fa-solid <?= ($data['flash']['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i></div>
                <h2 id="supportPopupTitle"><?= ($data['flash']['type'] ?? '') === 'success' ? 'Berhasil' : 'Belum Berhasil'; ?></h2>
                <p><?= e($data['flash']['message']); ?></p>
                <?php if (($data['flash']['type'] ?? '') === 'success' && !empty($data['flash']['meta']['ticket_no'])): ?>
                    <div class="support-popup__meta">
                        <strong>No. Tiket:</strong> <?= e($data['flash']['meta']['ticket_no']); ?><br>
                        <strong>Nama:</strong> <?= e($data['flash']['meta']['nama_pelapor'] ?? '-'); ?><br>
                        <strong>Email:</strong> <?= e($data['flash']['meta']['email_pelapor'] ?? '-'); ?>
                    </div>
                <?php endif; ?>
                <button type="button" class="btn btn--accent" data-popup-close>OK</button>
            </div>
        </div>
    <?php endif; ?>
</section>
