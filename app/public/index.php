<?php
declare(strict_types=1);

// Autoload dependencies
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
require __DIR__ . '/../models/Tag.php';
require __DIR__ . '/../models/Search.php';

// Controllers
require __DIR__ . '/../controllers/LoginController.php';
require __DIR__ . '/../controllers/RegisterController.php';
require __DIR__ . '/../controllers/ForgotPasswordController.php';
require __DIR__ . '/../controllers/ResetPasswordController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/LogoutController.php';
require __DIR__ . '/../controllers/ProfileController.php';
require __DIR__ . '/../controllers/BoardController.php';
require __DIR__ . '/../controllers/PostController.php';
require __DIR__ . '/../controllers/SearchController.php';
require __DIR__ . '/../controllers/TagController.php';

// --- Utilities ---
$uri    = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

// =====================================================
// AUTHENTICATION ROUTES
// =====================================================

// Home → Login page (or redirect to dashboard later)
if ($uri === '' || $uri === 'login') {
    $c = new LoginController();
    is_post() ? $c->login() : $c->showForm();
    exit;
}

if ($uri === 'register') {
    $c = new RegisterController();
    is_post() ? $c->register() : $c->showForm();
    exit;
}

if ($uri === 'forgot') {
    $c = new ForgotPasswordController();
    is_post() ? $c->sendReset() : $c->showForm();
    exit;
}

if ($uri === 'reset') {
    $c = new ResetPasswordController();
    is_post() ? $c->reset() : $c->showForm();
    exit;
}

if ($uri === 'logout') {
    (new LogoutController())->index();
    exit;
}

// =====================================================
// DASHBOARD / PROFILE
// =====================================================

if ($uri === 'dashboard') {
    (new DashboardController())->index();
    exit;
}

if ($uri === 'profile/avatar') {
    (new ProfileController())->avatar();
    exit;
}

// =====================================================
// BOARDS
// =====================================================

if ($uri === 'boards') {
    (new BoardController())->index();
    exit;
}

if ($uri === 'board') { // e.g. /board?b=123&page=2
    $c    = new BoardController();
    $id   = (int)($_GET['b'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $c->show($id, $page);
    exit;
}

if ($uri === 'board/create') {
    $c = new BoardController();
    is_post() ? $c->create() : $c->createForm();
    exit;
}

// =====================================================
// POSTS
// =====================================================

if ($uri === 'post') { // e.g. /post?id=123
    $c  = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    is_post() ? $c->comment($id) : $c->show($id);
    exit;
}

if ($uri === 'post/create') { // e.g. /post/create?b=123
    $c       = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    is_post() ? $c->create($boardId) : $c->createForm($boardId);
    exit;
}

// =====================================================
// SEARCH
// =====================================================

if ($uri === 'search') {
    (new SearchController())->index();
    exit;
}

// =====================================================
// TAGS
// =====================================================

if ($uri === 'tags') {
    (new TagController())->index();
    exit;
}

if ($uri === 'tag') { // fallback: /tag?slug=php
    $slug = (string)($_GET['slug'] ?? '');
    (new TagController())->show($slug);
    exit;
}

// Pretty route: /tag/{slug}
if (preg_match('#^tag/([^/]+)$#', $uri, $m)) {
    (new TagController())->show($m[1]);
    exit;
}

// =====================================================
// 404 HANDLER
// =====================================================
http_response_code(404);
echo "404 Not Found";
exit;
