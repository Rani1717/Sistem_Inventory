<?php
$authMode = trim((string) ($_GET['auth'] ?? 'login'));
if (!in_array($authMode, ['login', 'forgot', 'register'], true)) {
    $authMode = 'login';
}

$emailValue = $_SESSION['login_old']['email'] ?? '';
$errors = $_SESSION['login_errors'] ?? [];
$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';

$forgotOld = $_SESSION['forgot_old'] ?? [];
$forgotErrors = $_SESSION['forgot_errors'] ?? [];
$registerOld = $_SESSION['register_old'] ?? [];
$registerErrors = $_SESSION['register_errors'] ?? [];

unset(
    $_SESSION['login_old'],
    $_SESSION['login_errors'],
    $_SESSION['flash_error'],
    $_SESSION['flash_success'],
    $_SESSION['forgot_old'],
    $_SESSION['forgot_errors'],
    $_SESSION['register_old'],
    $_SESSION['register_errors']
);

$divisionOptions = [];
try {
    $divisionOptions = (new AuthModel())->fetchActiveDivisions();
} catch (Throwable $e) {
    $divisionOptions = [];
}
?>

<section class="standalone login-screen">
    <div class="login-screen__bg"></div>
    <div class="login-card">
        <div class="login-card__visual"></div>
        <div class="login-card__form">
            <h1 class="login-card__title">SPMT IT ASSET<br>MANAGEMENT</h1>

            <?php if ($flashError !== ''): ?>
                <div style="margin-bottom:12px; padding:10px 14px; border-radius:12px; background:#ffe5e5; color:#b10000;">
                    <?= e($flashError); ?>
                </div>
            <?php endif; ?>
            <?php if ($flashSuccess !== ''): ?>
                <div style="margin-bottom:12px; padding:10px 14px; border-radius:12px; background:#e6fff0; color:#087f3a;">
                    <?= e($flashSuccess); ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
                <a href="index.php?page=login" class="btn <?= $authMode === 'login' ? 'btn--primary' : 'btn--ghost'; ?>" style="padding:8px 12px;">Login</a>
                <a href="index.php?page=login&auth=register" class="btn <?= $authMode === 'register' ? 'btn--primary' : 'btn--ghost'; ?>" style="padding:8px 12px;">Sign In / Daftar</a>
            </div>

            <?php if ($authMode === 'forgot'): ?>
                <?php if (!empty($forgotErrors['global'])): ?>
                    <div style="margin-bottom:12px; padding:10px 14px; border-radius:12px; background:#ffe5e5; color:#b10000;"><?= e($forgotErrors['global']); ?></div>
                <?php endif; ?>
                <form class="stack-lg" action="<?= routeTo('forgot-password-process'); ?>" method="post" novalidate>
                    <?php renderField('Email Address', 'email', 'email', 'Masukkan email yang terdaftar', (string) ($forgotOld['email'] ?? ''), $forgotErrors['email'] ?? ''); ?>
                    <?php renderField('Password Baru', 'password', 'password', 'Minimal 6 karakter', '', $forgotErrors['password'] ?? ''); ?>
                    <?php renderField('Konfirmasi Password', 'password_confirm', 'password', 'Ulangi password baru', '', $forgotErrors['password_confirm'] ?? ''); ?>
                    <button type="submit" class="btn btn--primary btn--xl">GANTI PASSWORD</button>
                    <a href="index.php?page=login" class="link-accent">Kembali ke Login</a>
                </form>
            <?php elseif ($authMode === 'register'): ?>
                <?php if (!empty($registerErrors['global'])): ?>
                    <div style="margin-bottom:12px; padding:10px 14px; border-radius:12px; background:#ffe5e5; color:#b10000;"><?= e($registerErrors['global']); ?></div>
                <?php endif; ?>
                <form class="stack-lg" action="<?= routeTo('register-process'); ?>" method="post" novalidate>
                    <?php renderField('Nama Lengkap', 'nama_lengkap', 'text', 'Masukkan nama lengkap', (string) ($registerOld['nama_lengkap'] ?? ''), $registerErrors['nama_lengkap'] ?? ''); ?>
                    <?php renderField('Email Address', 'email', 'email', 'Masukkan email', (string) ($registerOld['email'] ?? ''), $registerErrors['email'] ?? ''); ?>
                    <div class="field">
                        <label class="field__label" for="default-divisi-id">Divisi</label>
                        <select id="default-divisi-id" name="default_divisi_id" class="field__control" required>
                            <option value="">Pilih divisi dari data master</option>
                            <?php foreach ($divisionOptions as $division): ?>
                                <?php $divisionId = (int) ($division['id'] ?? 0); ?>
                                <option value="<?= $divisionId; ?>" <?= (int) ($registerOld['default_divisi_id'] ?? 0) === $divisionId ? 'selected' : ''; ?>>
                                    <?= e((string) ($division['sheet_sumber'] ?? '')); ?> - <?= e((string) ($division['division_label'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($registerErrors['default_divisi_id'])): ?><small style="color:#d11a1a; margin-top:6px; display:block;"><?= e($registerErrors['default_divisi_id']); ?></small><?php endif; ?>
                    </div>
                    <?php renderField('Password', 'password', 'password', 'Minimal 6 karakter', '', $registerErrors['password'] ?? ''); ?>
                    <?php renderField('Konfirmasi Password', 'password_confirm', 'password', 'Ulangi password', '', $registerErrors['password_confirm'] ?? ''); ?>
                    <button type="submit" class="btn btn--primary btn--xl">DAFTARKAN AKUN</button>
                    <a href="index.php?page=login" class="link-accent">Sudah punya akun? Login</a>
                </form>
            <?php else: ?>
                <form class="stack-lg" action="<?= routeTo('login-process'); ?>" method="post" novalidate>
                    <?php renderField('Email Address', 'email', 'email', 'Masukkan email', $emailValue, $errors['email'] ?? ''); ?>
                    <?php renderField('Password', 'password', 'password', 'Masukkan password', '', $errors['password'] ?? ''); ?>
                    <a href="index.php?page=login&auth=forgot" class="link-accent">Forget Password?</a>
                    <button type="submit" class="btn btn--primary btn--xl">LOGIN</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<div style="text-align:center; margin-top:16px;"><a href="it-support.php">Buka Form IT Support Publik</a></div>
