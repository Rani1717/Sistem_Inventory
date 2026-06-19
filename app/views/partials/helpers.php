<?php
/** @var array $data */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', ltrim($path, '/'));

        if ($normalizedPath === '') {
            return 'public/assets/';
        }

        if (preg_match('#^https?://#i', $normalizedPath)) {
            return $normalizedPath;
        }

        if (strpos($normalizedPath, 'public/') === 0) {
            $relativePath = $normalizedPath;
        } elseif (strpos($normalizedPath, 'assets/') === 0) {
            $relativePath = 'public/' . $normalizedPath;
        } elseif (strpos($normalizedPath, 'uploads/') === 0 || strpos($normalizedPath, 'images/') === 0) {
            $relativePath = 'public/assets/' . $normalizedPath;
        } else {
            $relativePath = 'public/assets/' . $normalizedPath;
        }

        $absolutePath = dirname(__DIR__, 3) . '/' . $relativePath;
        if (is_file($absolutePath)) {
            return $relativePath . '?v=' . filemtime($absolutePath);
        }

        return $relativePath;
    }
}

if (!function_exists('routeTo')) {
    function routeTo(string $page): string
    {
        return 'index.php?page=' . urlencode($page);
    }
}

if (!function_exists('isMenuActive')) {
    function isMenuActive(string $page, array $menu): bool
    {
        return in_array($page, $menu['match'], true);
    }
}

if (!function_exists('renderSidebar')) {
    function renderSidebar(array $data, string $page): void
    {
        ?>
        <aside class="sidebar" id="appSidebar" aria-label="Menu utama">
            <button type="button" class="sidebar__close js-sidebar-close" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
            <div class="sidebar__logo-wrap">
                <img src="<?= asset('images/pelindo-logo.png'); ?>" alt="Pelindo" class="sidebar__logo">
            </div>

            <nav class="sidebar__nav">
                <?php foreach ($data['menus'] as $menu): ?>
                    <?php if (!empty($menu['admin_only']) && !AuthController::isAdminSpmt()) { continue; } ?>
                    <a href="<?= routeTo($menu['route']); ?>"
                       class="<?= $menu['variant'] === 'pill' ? 'sidebar__pill' : 'sidebar__link'; ?> <?= isMenuActive($page, $menu) ? 'is-active' : ''; ?>">
                        <i class="<?= e($menu['icon']); ?>"></i>
                        <span><?= e($menu['label']); ?></span>
                        <?php if (($menu['route'] ?? '') === 'user-management' && !empty($data['pending_user_count'])): ?>
                            <span class="sidebar__menu-badge"><?= (int) $data['pending_user_count']; ?></span>
                        <?php endif; ?>
                        <?php if (($menu['route'] ?? '') === 'notifikasi-alert'): ?>
                            <span class="sidebar__menu-badge js-alert-unread-badge" style="<?= empty($data['alert_unread_count']) ? 'display: none;' : ''; ?>"><?= (int) $data['alert_unread_count']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar__footer"><?= nl2br(e($data['brand'])); ?></div>
        </aside>
        <?php
    }
}

if (!function_exists('renderTopbar')) {
    function renderTopbar(array $data = []): void
    {
        $auth = $_SESSION['auth'] ?? [];
        $displayName = trim((string) ($auth['nama_lengkap'] ?? $auth['username'] ?? 'User'));
        $displayEmail = trim((string) ($auth['email'] ?? ($data['user_email'] ?? '')));
        $canAccessItSupport = AuthController::canAccessItSupport();
        $isAdmin = AuthController::isAdminSpmt();

        $notifications = $canAccessItSupport ? ($data['it_support_notifications'] ?? ['count' => 0, 'items' => []]) : ['count' => 0, 'items' => []];
        $notificationCount = (int) ($notifications['count'] ?? 0);

        $pendingUserNotif = ($isAdmin && !empty($data['pending_user_notifications']))
            ? $data['pending_user_notifications']
            : ['count' => 0, 'items' => []];
        $pendingUserCount = (int) ($pendingUserNotif['count'] ?? 0);

        $alertSummary = $data['alert_summary'] ?? ['count' => 0, 'items' => []];
        $alertCount = (int) ($alertSummary['count'] ?? 0);

        $totalBadgeCount = $notificationCount + $pendingUserCount + $alertCount;
        ?>
        <header class="topbar">
            <button type="button" class="topbar__mobile-menu js-sidebar-toggle" aria-label="Buka menu" aria-controls="appSidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar__search" data-global-search>
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" class="topbar__search-input js-global-search" placeholder="Search apa saja..." autocomplete="off" aria-label="Search apa saja">
                <div class="topbar__search-results js-global-search-results" hidden></div>
            </div>
            <div class="topbar__icons">
                <?php if ($canAccessItSupport || $isAdmin || $alertCount > 0 || true): ?>
                <div class="topbar__dropdown">
                    <button type="button" class="topbar__icon-btn js-toggle-notifications" aria-label="Notifikasi" aria-expanded="false" data-notification-count="<?= (int) $totalBadgeCount; ?>">
                        <i class="fa-solid fa-bell"></i>
                        <span class="topbar__badge js-notification-badge"<?= $totalBadgeCount > 0 ? "" : " hidden"; ?>><?= $totalBadgeCount > 99 ? "99+" : (int) $totalBadgeCount; ?></span>
                    </button>
                    <div class="topbar__menu topbar__menu--notifications js-notification-menu" hidden>

                        <?php if ($alertCount > 0 || !empty($alertSummary['items'])): ?>
                        <div class="topbar__menu-header">
                            <strong><i class="fa-solid fa-triangle-exclamation"></i> Alert Sistem</strong>
                            <span><?= (int) $alertCount; ?> notifikasi</span>
                        </div>
                        <?php if (!empty($alertSummary['items'])): ?>
                            <?php foreach ($alertSummary['items'] as $alertItem): ?>
                                <a class="notification-item notification-item--alert notification-item--level-<?= e(strtolower((string) ($alertItem['level'] ?? 'info'))); ?>"
                                   href="index.php?page=notifikasi-alert&mark_read_id=<?= (int) ($alertItem['id'] ?? 0); ?>">
                                    <span class="notification-item__ticket"><?= e((string) ($alertItem['kategori'] ?? '-')); ?></span>
                                    <strong><?= e((string) ($alertItem['judul'] ?? '-')); ?></strong>
                                    <small><?= e(mb_substr((string) ($alertItem['keterangan'] ?? ''), 0, 80)); ?></small>
                                    <em><?= e((string) ($alertItem['created_at'] ?? '')); ?></em>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notification-empty">Tidak ada alert baru.</div>
                        <?php endif; ?>
                        <a class="topbar__menu-footer" href="index.php?page=notifikasi-alert">
                            <i class="fa-solid fa-list-check"></i> Lihat Semua Notifikasi
                        </a>
                        <div class="notif-section-divider"></div>
                        <?php endif; ?>

                        <?php if ($canAccessItSupport): ?>
                        <div class="topbar__menu-header"><strong>IT Support baru</strong><span><span class="js-notification-count-text"><?= (int) $notificationCount; ?></span> notifikasi</span></div>
                        <?php if (!empty($notifications['items'])): ?>
                            <?php foreach ($notifications['items'] as $item): ?>
                                <a class="notification-item" href="index.php?page=data-keluhan&focus_ticket=<?= (int) ($item['id'] ?? 0); ?>&mark_notification_read=<?= (int) ($item['id'] ?? 0); ?>">
                                    <span class="notification-item__ticket"><?= e((string) ($item['ticket_no'] ?? '-')); ?></span>
                                    <strong><?= e((string) ($item['nama'] ?? 'Pelapor')); ?></strong>
                                    <small><?= e((string) ($item['divisi'] ?? '-')); ?> - <?= e((string) ($item['barang'] ?? '-')); ?></small>
                                    <em><?= e((string) ($item['tanggal_dan_jam'] ?? '')); ?></em>
                                </a>
                            <?php endforeach; ?>
                            <a class="topbar__menu-footer" href="index.php?page=data-keluhan&complaint_status=NOT+YET&mark_all_notifications=1">Lihat semua tiket baru</a>
                        <?php else: ?>
                            <div class="notification-empty">Belum ada form IT Support baru.</div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                        <div class="notif-section-divider"></div>
                        <div class="topbar__menu-header topbar__menu-header--user"><strong><i class="fa-solid fa-user-clock"></i> Akun Menunggu Validasi</strong><span class="notif-pending-count"><?= $pendingUserCount; ?></span></div>
                        <?php if (!empty($pendingUserNotif['items'])): ?>
                            <?php foreach ($pendingUserNotif['items'] as $uItem): ?>
                                <a class="notification-item notification-item--user" href="index.php?page=user-management">
                                    <strong><?= e((string) ($uItem['nama_lengkap'] ?? '-')); ?></strong>
                                    <small><?= e((string) ($uItem['email'] ?? '-')); ?></small>
                                    <small class="notif-user-division"><?= e((string) ($uItem['unit_kerja_default'] ?? '-')); ?></small>
                                    <em><?= e((string) ($uItem['created_at'] ?? '')); ?></em>
                                </a>
                            <?php endforeach; ?>
                            <a class="topbar__menu-footer topbar__menu-footer--user" href="index.php?page=user-management">Kelola semua akun pending</a>
                        <?php else: ?>
                            <div class="notification-empty">Tidak ada akun yang menunggu validasi.</div>
                        <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endif; ?>
                <div class="topbar__dropdown">
                    <button type="button" class="topbar__avatar topbar__avatar--user js-toggle-profile" aria-label="Menu profile" aria-expanded="false" title="<?= e(($displayName !== '' ? $displayName : 'User') . ($displayEmail !== '' ? ' - ' . $displayEmail : '')); ?>"><i class="fa-solid fa-user"></i><span class="profile-hover-card"><strong><?= e($displayName !== '' ? $displayName : 'User'); ?></strong><?php if ($displayEmail !== ''): ?><small><?= e($displayEmail); ?></small><?php endif; ?></span></button>
                    <div class="topbar__menu topbar__menu--profile js-profile-menu" hidden>
                        <div class="profile-menu__identity"><strong><?= e($displayName !== '' ? $displayName : 'User'); ?></strong><?php if ($displayEmail !== ''): ?><span><?= e($displayEmail); ?></span><?php endif; ?></div>
                        <button type="button" class="topbar__menu-action js-open-modal" data-modal="accountSettingsModal" data-profile-name="<?= e($displayName !== '' ? $displayName : 'User'); ?>" data-profile-email="<?= e($displayEmail); ?>"><i class="fa-solid fa-gear"></i> Setting Akun</button>
                        <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
}


if (!function_exists('renderMainHeader')) {
    function renderMainHeader(array $data, string $title): void
    {
        ?>
        <div class="main-banner">
            <h1 class="main-banner__title"><?= e($title); ?></h1>
            <div class="main-banner__meta">TANGGAL : <span id="realtimeDate"><?= e($data['date']); ?></span></div>
            <div class="main-banner__meta">JAM : <span id="realtimeTime"><?= e($data['time']); ?></span></div>
        </div>
        <?php
    }
}
if (!function_exists('renderField')) {
    function renderField(
        string $label,
        ?string $name = null,
        string $type = 'text',
        string $placeholder = '',
        string $value = '',
        string $error = ''
    ): void {
        // fallback otomatis kalau view lama hanya kirim 1 argumen
        $name = $name ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
        $inputId = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($name));

        // fallback placeholder
        if ($placeholder === '') {
            $placeholder = 'Masukkan ' . strtolower($label);
        }
        ?>
        <div class="field">
            <label class="field__label" for="<?= e($inputId); ?>"><?= e($label); ?></label>

            <?php if ($type === 'textarea'): ?>
                <textarea
                    id="<?= e($inputId); ?>"
                    name="<?= e($name); ?>"
                    class="field__control field__control--textarea"
                    placeholder="<?= e($placeholder); ?>"><?= e($value); ?></textarea>
            <?php else: ?>
                <input
                    id="<?= e($inputId); ?>"
                    name="<?= e($name); ?>"
                    type="<?= e($type); ?>"
                    class="field__control"
                    placeholder="<?= e($placeholder); ?>"
                    value="<?= $type === 'password' ? '' : e($value); ?>"
                    <?= $type === 'email' ? 'autocomplete="username"' : ''; ?>
                    <?= $type === 'password' ? 'autocomplete="current-password"' : ''; ?>
                >
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <small style="color:#d11a1a; margin-top:6px; display:block;"><?= e($error); ?></small>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('renderDocInput')) {
    function renderDocInput(string $placeholder = 'Pilih File', string $inputName = '', bool $imageOnly = true, string $existingPath = ''): void
    {
        $existingName = $existingPath !== '' ? basename($existingPath) : '';
        ?>
        <div class="doc-upload js-doc-upload" data-existing-src="<?= e($existingPath); ?>" data-existing-name="<?= e($existingName); ?>">
            <div class="doc-upload__field">
                <span class="doc-upload__placeholder"><?= e($placeholder); ?></span>
                <input type="file" <?= $inputName !== '' ? 'name="' . e($inputName) . '"' : ''; ?> class="js-file-input<?= $imageOnly ? ' js-image-input' : ''; ?> doc-upload__input-file" <?= $imageOnly ? 'accept="image/*"' : ''; ?> hidden>
                <input type="file" class="js-picker-input" <?= $imageOnly ? 'accept="image/*"' : ''; ?> hidden>
                <input type="file" class="js-camera-input" accept="image/*" capture="environment" hidden>
                <?php if ($inputName !== ''): ?>
                    <input type="hidden" name="existing_<?= e($inputName); ?>" class="js-existing-file-path" value="<?= e($existingPath); ?>">
                    <input type="hidden" name="remove_<?= e($inputName); ?>" class="js-remove-file-flag" value="0">
                <?php endif; ?>
                <span class="doc-upload__icons">
                    <button type="button" class="doc-upload__icon-btn js-camera-trigger" aria-label="Buka kamera" title="Buka kamera"><i class="fa-solid fa-camera"></i></button>
                    <button type="button" class="doc-upload__icon-btn js-file-trigger" aria-label="Pilih file" title="Pilih file"><i class="fa-solid fa-file"></i></button>
                </span>
            </div>
            <div class="doc-upload__preview js-doc-preview" hidden>
                <div class="doc-upload__preview-thumb-wrap" hidden>
                    <img src="" alt="Preview dokumentasi inventaris" class="doc-upload__preview-thumb js-preview-image">
                </div>
                <div class="doc-upload__preview-info">
                    <strong class="js-preview-name">Belum ada file</strong>
                    <span class="js-preview-size"></span>
                </div>
                <button type="button" class="btn btn--ghost js-preview-remove">Hapus</button>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('renderTabRow')) {
    function renderTabRow(string $active): void
   {
        ?>
        <div class="tab-row">
            <?php /* Tombol PC — langsung link, tidak ada dropdown */ ?>
            <a href="index.php?page=inventory-pc" class="tab-btn <?= $active === 'pc' ? 'is-active' : 'is-muted'; ?>">PC</a>

            <?php /* Tombol Perangkat Lain — tetap seperti semula */ ?>
            <button class="tab-btn <?= $active === 'other' ? 'is-active' : 'is-muted'; ?> js-route"
                    data-page="inventory-other">Perangkat Lain</button>
        </div>
        <?php
    }
}
