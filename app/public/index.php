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
require __DIR__ . '/../models/Profile.php';
require __DIR__ . '/../models/Board.php';
require __DIR__ . '/../models/Post.php';
require __DIR__ . '/../models/Tag.php';     // NEW (for TagController, tag pages)
require __DIR__ . '/../models/Search.php';  // Optional (if your SearchController uses it)

// Controllers
require __DIR__ . '/../controllers/LoginController.php';
require __DIR__ . '/../controllers/RegisterController.php';
require __DIR__ . '/../controllers/ForgotPasswordController.php';
require __DIR__ . '/../controllers/ResetPasswordController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/LogoutController.php';
require __DIR__ . '/../controllers/BoardController.php';
require __DIR__ . '/../controllers/PostController.php';
require __DIR__ . '/../controllers/SearchController.php'; // NEW
require __DIR__ . '/../controllers/TagController.php';    // NEW

// Normalize URI (strip query + leading/trailing slashes)
$uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

// Convenience helpers
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

// --- Routes ---

// Home → Login (or Dashboard if you later add auth checks here)
if ($uri === '' || $uri === 'login') {
    $controller = new LoginController();
    if (is_post()) $controller->login(); else $controller->showForm();
    exit;
} elseif ($uri === 'register') {
    $controller = new RegisterController();
    if (is_post()) $controller->register(); else $controller->showForm();
    exit;
} elseif ($uri === 'forgot') {
    $controller = new ForgotPasswordController();
    if (is_post()) $controller->sendReset(); else $controller->showForm();
    exit;
} elseif ($uri === 'reset') {
    $controller = new ResetPasswordController();
    if (is_post()) $controller->reset(); else $controller->showForm();
    exit;
} elseif ($uri === 'dashboard') {
    (new DashboardController())->index();
    exit;
} elseif ($uri === 'logout') {
    (new LogoutController())->index();
    exit;
} elseif ($uri === 'profile/avatar') {
    (new ProfileController())->avatar();
    exit;
}

/* -------- Boards -------- */
if ($uri === 'boards') {
    (new BoardController())->index();
    exit;
} elseif ($uri === 'board') { // supports /board?id=123 OR /board?b=123
    $controller = new BoardController();
    $id   = (int)($_GET['id'] ?? ($_GET['b'] ?? 0));
    $page = (int)($_GET['page'] ?? 1);
    $controller->show($id, $page);
    exit;
} elseif ($uri === 'board/create') {
    $controller = new BoardController();
    if (is_post()) $controller->create(); else $controller->createForm();
    exit;
}

/* -------- Posts -------- */
if ($uri === 'post') { // /post?id=123
    $controller = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    if (is_post()) $controller->comment($id); else $controller->show($id);
    exit;
} elseif ($uri === 'post/create') { // /post/create?b=123
    $controller = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    if (is_post()) $controller->create($boardId); else $controller->createForm($boardId);
    exit;
}

/* -------- Search -------- */
if ($uri === 'search') { // /search?q=...&type=posts|users|tags[&tag=slug]
    (new SearchController())->index();
    exit;
}

/* -------- Tags -------- */
if ($uri === 'tags') { // /tags
    (new TagController())->index();
    exit;
} elseif ($uri === 'tag') { // /tag?slug=php
    $slug = (string)($_GET['slug'] ?? '');
    (new TagController())->show($slug);
    exit;
} elseif (preg_match('#^tag/([^/]+)$#', $uri, $m)) { // pretty: /tag/{slug}
    (new TagController())->show($m[1]);
    exit;
}

/* -------- 404 (last) -------- */
http_response_code(404);
echo "404 Not Found";
exit;
?>