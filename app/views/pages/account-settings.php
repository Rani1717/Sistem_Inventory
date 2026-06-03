<?php
$accountUser = $data['account_user'] ?? ($_SESSION['auth'] ?? []);
$errors = $_SESSION['account_errors'] ?? [];
$old = $_SESSION['account_old'] ?? [];
unset($_SESSION['account_errors'], $_SESSION['account_old']);
$nameValue = (string) ($old['nama_lengkap'] ?? ($accountUser['nama_lengkap'] ?? ''));
$emailValue = (string) ($old['email'] ?? ($accountUser['email'] ?? ''));
?>
<div class="account-page">
    <div class="detail-header detail-header--report account-header">
        <h1>SETTING <br>AKUN</h1>
        <p class="account-header__subtitle">Edit nama user, email login, dan password akun yang sedang digunakan.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="flash flash--error"><?= e((string) $errors['general']); ?></div>
    <?php endif; ?>

    <form method="post" class="account-card" autocomplete="off">
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

        <div class="account-card__actions">
            <a href="index.php?page=dashboard" class="btn btn--ghost">Batal</a>
            <button type="submit" class="btn btn--primary btn--lg account-card__save-btn">Simpan</button>
        </div>
    </form>
</div>
