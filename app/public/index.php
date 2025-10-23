<?php
declare(strict_types=1);

// Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Core
require __DIR__ . '/../core/Session.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/BaseController.php';

// Models
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../models/Board.php';
require __DIR__ . '/../models/Post.php';

// Controllers
require __DIR__ . '/../controllers/LoginController.php';
require __DIR__ . '/../controllers/RegisterController.php';
require __DIR__ . '/../controllers/ForgotPasswordController.php';
require __DIR__ . '/../controllers/ResetPasswordController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/LogoutController.php';
require __DIR__ . '/../controllers/BoardController.php';
require __DIR__ . '/../controllers/PostController.php';

// Router
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

} elseif ($uri === 'boards') {
    $controller = new BoardController();
    $controller->index();

} elseif ($uri === 'board') {
    $controller = new BoardController();
    $id = (int)($_GET['b'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $controller->show($id, $page);

} elseif ($uri === 'board/create') {
    $controller = new BoardController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->create();
    } else {
        $controller->createForm();
    }
} elseif ($uri === 'post') {
    $controller = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->comment($id);
    } else {
        $controller->show($id);
    }

} elseif ($uri === 'post/create') {
    $controller = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->create($boardId);
    } else {
        $controller->createForm($boardId);
    }

} else {
    http_response_code(404);
    echo "404 Not Found";
}
