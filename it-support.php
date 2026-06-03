<?php
require_once __DIR__ . '/app/controllers/PublicItSupportController.php';
$controller = new PublicItSupportController();
$controller->handle((string) ($_GET['page'] ?? 'form'));
