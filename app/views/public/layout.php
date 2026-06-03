<?php
require_once dirname(__DIR__) . '/partials/helpers.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Request - SPMT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" type="image/png" href="public/assets/images/logo.jpg">
    <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>
<body class="page page--public-it-support">
<?php include $viewFile; ?>
<script src="<?= asset('js/app.js'); ?>"></script>
</body>
</html>
