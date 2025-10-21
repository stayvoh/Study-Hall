<?php
declare(strict_types=1);

// Composer autoloader (for PHPMailer and any future libraries)
require __DIR__ . '/../vendor/autoload.php';

// Core
require __DIR__ . '/../core/Session.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/BaseController.php';

// Models
require __DIR__ . '/../models/User.php';

// Controllers
require __DIR__ . '/../controllers/LoginController.php';
require __DIR__ . '/../controllers/RegisterController.php';
require __DIR__ . '/../controllers/ForgotPasswordController.php';
require __DIR__ . '/../controllers/ResetPasswordController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/LogoutController.php';

// Simple router
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if ($uri === '' || $uri === 'login') {
    $controller = new LoginController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->login();
    } else {
        $controller->showForm();
    }

} elseif ($uri === 'register') {
    $controller = new RegisterController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->register();
    } else {
        $controller->showForm();
    }

} elseif ($uri === 'forgot') {
    $controller = new ForgotPasswordController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->sendReset();
    } else {
        $controller->showForm();
    }

} elseif ($uri === 'reset') {
    $controller = new ResetPasswordController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->reset();
    } else {
        $controller->showForm();
    }

} elseif ($uri === 'dashboard') {
    $controller = new DashboardController();
    $controller->index();

} elseif ($uri === 'logout') {
    $controller = new LogoutController();
    $controller->index();

} else {
    http_response_code(404);
    echo "404 Not Found";
}
