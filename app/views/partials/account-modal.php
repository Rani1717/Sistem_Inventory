<?php
$auth = $_SESSION['auth'] ?? [];
$errors = $_SESSION['account_errors'] ?? [];
$old = $_SESSION['account_old'] ?? [];
unset($_SESSION['account_errors'], $_SESSION['account_old']);
$nameValue = (string) ($old['nama_lengkap'] ?? ($auth['nama_lengkap'] ?? $auth['username'] ?? ''));
$emailValue = (string) ($old['email'] ?? ($auth['email'] ?? ($data['user_email'] ?? '')));
$returnTo = $_SERVER['REQUEST_URI'] ?? 'index.php?page=dashboard';
if ($returnTo === '' || preg_match('#^https?://#i', $returnTo)) {
    $returnTo = 'index.php?page=dashboard';
}
$autoOpen = !empty($errors) || (isset($_GET['account_modal']) && $_GET['account_modal'] === '1');
?>
<div class="modal account-modal<?= $autoOpen ? ' is-open' : ''; ?>" id="accountSettingsModal" aria-hidden="<?= $autoOpen ? 'false' : 'true'; ?>">
    <div class="modal__backdrop js-close-modal"></div>
    <div class="modal__dialog account-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="accountSettingsTitle">
        <div class="modal__header">
            <div>
                <p class="account-modal__eyebrow">Profile</p>
                <h2 id="accountSettingsTitle">Setting Akun</h2>
            </div>
            <button type="button" class="icon-square js-close-modal" aria-label="Tutup setting akun"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash--error account-modal__flash"><?= e((string) $errors['general']); ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=account-settings" class="account-card account-card--modal" autocomplete="off">
            <input type="hidden" name="return_to" value="<?= e($returnTo); ?>">
            <div class="account-card__identity">
                <span class="account-card__avatar"><i class="fa-solid fa-user"></i></span>
                <div>
                    <strong><?= e($nameValue !== '' ? $nameValue : 'User'); ?></strong>
                    <span><?= e($emailValue); ?></span>
                </div>
            </div>

            <label class="account-field">
                <span>Nama User</span>
                <input type="text" name="nama_lengkap" value="<?= e($nameValue); ?>" placeholder="Masukkan nama user" required>
                <?php if (!empty($errors['nama_lengkap'])): ?><em><?= e((string) $errors['nama_lengkap']); ?></em><?php endif; ?>
            </label>

            <label class="account-field">
                <span>Email</span>
                <input type="email" name="email" value="<?= e($emailValue); ?>" placeholder="Masukkan email" required>
                <?php if (!empty($errors['email'])): ?><em><?= e((string) $errors['email']); ?></em><?php endif; ?>
            </label>

            <div class="account-card__password-note">Kosongkan password jika tidak ingin mengganti password.</div>
            <label class="account-field">
                <span>Password Baru</span>
                <input type="password" name="password" placeholder="Minimal 6 karakter" autocomplete="new-password">
                <?php if (!empty($errors['password'])): ?><em><?= e((string) $errors['password']); ?></em><?php endif; ?>
            </label>

            <label class="account-field">
                <span>Konfirmasi Password Baru</span>
                <input type="password" name="password_confirm" placeholder="Ulangi password baru" autocomplete="new-password">
                <?php if (!empty($errors['password_confirm'])): ?><em><?= e((string) $errors['password_confirm']); ?></em><?php endif; ?>
            </label>

            <div class="modal__footer account-card__actions">
                <button type="button" class="btn btn--ghost js-close-modal">Batal</button>
                <button type="submit" class="btn btn--primary btn--lg account-card__save-btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
