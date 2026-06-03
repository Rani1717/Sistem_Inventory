<?php
require_once __DIR__ . '/app/controllers/PageController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$page = $_GET['page'] ?? 'splash';

switch ($page) {
    case 'login-process':
        $authController = new AuthController();
        $authController->loginProcess();
        break;

    case 'logout':
        $authController = new AuthController();
        $authController->logout();
        break;

    case 'forgot-password-process':
        $authController = new AuthController();
        $authController->forgotPasswordProcess();
        break;

    case 'register-process':
        $authController = new AuthController();
        $authController->registerProcess();
        break;

    default:
        $pageController = new PageController();
        $pageController->render($page);
        break;
}
