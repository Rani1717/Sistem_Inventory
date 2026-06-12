<?php
/** @var array $data */
require_once __DIR__ . '/../partials/helpers.php';

$page = $data['page'];
$pageTitle = $data['page_titles'][$page] ?? 'SPMT IT ASSET MANAGEMENT';
$currentLayout = $data['current_layout'] ?? 'app';
$viewFile = __DIR__ . '/../' . ($data['current_view'] ?? 'pages/splash.php');

if (!is_file($viewFile)) {
    http_response_code(500);
    echo 'View file not found: ' . e($viewFile);
    return;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> - SPMT IT ASSET MANAGEMENT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="icon" type="image/png" href="public/assets/images/logo.jpg">
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>
<body class="page page--<?= e($page); ?>">
<?php if ($currentLayout === 'app'): ?>
    <div class="app-layout">
        <?php renderSidebar($data, $page); ?>

        <main class="app-main">
            <?php renderTopbar($data); ?>
            <section class="frame">
                <?php if (!empty($data['flash']) && !in_array($page, ['inventaris-detail', 'log-barang'], true)): ?>
                    <div class="flash flash--<?= e($data['flash']['type'] ?? 'success'); ?>"><?= e($data['flash']['message'] ?? ''); ?></div>
                <?php endif; ?>
                <?php include $viewFile; ?>
            </section>
            <?php include __DIR__ . "/../partials/account-modal.php"; ?>
        </main>
    </div>
<?php else: ?>
    <?php include $viewFile; ?>
<?php endif; ?>

<script>
    window.SPMT_DATA = <?= json_encode([
        'page' => $page,
        'cctv_breakdown' => $data['cctv_breakdown'],
        'complaint_chart' => $data['complaint_chart'],
        'inventory_flow' => $data['inventory_flow'],
        'accessible_pages' => $data['accessible_pages'] ?? [],
        'flash_popup' => !empty($data['flash']['message']) ? [
            'type' => (string) ($data['flash']['type'] ?? 'success'),
            'message' => (string) ($data['flash']['message'] ?? ''),
        ] : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>
