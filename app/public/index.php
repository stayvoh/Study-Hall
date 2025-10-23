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
}

if ($uri === 'register') {
    $controller = new RegisterController();
    if (is_post()) $controller->register(); else $controller->showForm();
    exit;
}

if ($uri === 'forgot') {
    $controller = new ForgotPasswordController();
    if (is_post()) $controller->sendReset(); else $controller->showForm();
    exit;
}

if ($uri === 'reset') {
    $controller = new ResetPasswordController();
    if (is_post()) $controller->reset(); else $controller->showForm();
    exit;
}

if ($uri === 'dashboard') {
    (new DashboardController())->index();
    exit;
}

if ($uri === 'logout') {
    (new LogoutController())->index();
    exit;
}

// ---- Boards (existing style) ----
if ($uri === 'boards') {
    (new BoardController())->index();
    exit;
}

if ($uri === 'board') { // /board?b=123&page=2
    $controller = new BoardController();
    $id   = (int)($_GET['b'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $controller->show($id, $page);
    exit;
}

if ($uri === 'board/create') {
    $controller = new BoardController();
    if (is_post()) $controller->create(); else $controller->createForm();
    exit;
}

// ---- Posts ----
if ($uri === 'post') { // /post?id=123
    $controller = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    if (is_post()) $controller->comment($id); else $controller->show($id);
    exit;
}

if ($uri === 'post/create') { // /post/create?b=123
    $controller = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    if (is_post()) $controller->create($boardId); else $controller->createForm($boardId);
    exit;
}

// ---- Search (new) ----
if ($uri === 'search') {            // /search?q=...&type=posts|users|tags[&tag=slug]
    (new SearchController())->index();
    exit;
}

// ---- Tags (new) ----
if ($uri === 'tags') {              // /tags
    (new TagController())->index();
    exit;
}

if ($uri === 'tag') {               // fallback: /tag?slug=php
    $slug = (string)($_GET['slug'] ?? '');
    (new TagController())->show($slug);
    exit;
}

// Pretty route: /tag/{slug}
if (preg_match('#^tag/([^/]+)$#', $uri, $m)) {
    $slug = $m[1];
    (new TagController())->show($slug);
    exit;
}

// --- 404 ---
http_response_code(404);
echo "404 Not Found";
