<?php
declare(strict_types=1);

// -------------------------------------------------------------
// Autoload + Core
// -------------------------------------------------------------
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../core/Session.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/BaseController.php';

// -------------------------------------------------------------
// Models
// -------------------------------------------------------------
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../models/Profile.php';
require __DIR__ . '/../models/Board.php';
require __DIR__ . '/../models/Post.php';
require __DIR__ . '/../models/Tag.php';
require __DIR__ . '/../models/Search.php';

// -------------------------------------------------------------
// Controllers
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// Helpers
// -------------------------------------------------------------
$uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

// -------------------------------------------------------------
// Routes
// -------------------------------------------------------------

// --- Auth + Account Pages ---
if ($uri === '' || $uri === 'login') {
    $controller = new LoginController();
    if (is_post()) $controller->login(); else $controller->showForm();
    exit;
}

elseif ($uri === 'register') {
    $controller = new RegisterController();
    if (is_post()) $controller->register(); else $controller->showForm();
    exit;
}

elseif ($uri === 'forgot') {
    $controller = new ForgotPasswordController();
    if (is_post()) $controller->sendReset(); else $controller->showForm();
    exit;
}

elseif ($uri === 'reset') {
    $controller = new ResetPasswordController();
    if (is_post()) $controller->reset(); else $controller->showForm();
    exit;
}

// --- Dashboard / Logout ---
elseif ($uri === 'dashboard') {
    (new DashboardController())->index();
    exit;
}

elseif ($uri === 'logout') {
    (new LogoutController())->index();
    exit;
}

// --- Profile Routes ---
elseif ($uri === 'profile') {
    (new ProfileController())->profile();
    exit;
}

elseif ($uri === 'profile/avatar') {
    (new ProfileController())->avatar();
    exit;
}

elseif ($uri === 'profile/edit') {
    (new ProfileController())->edit();
    exit;
}

elseif ($uri === 'profile/update') {
    if (is_post()) (new ProfileController())->update();
    exit;
}

// --- Boards ---
elseif ($uri === 'boards') {
    (new BoardController())->index();
    exit;
}

elseif ($uri === 'board') { // /board?b=123&page=2
    $controller = new BoardController();
    $id   = (int)($_GET['b'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $controller->show($id, $page);
    exit;
}

elseif ($uri === 'board/create') {
    $controller = new BoardController();
    if (is_post()) $controller->create(); else $controller->createForm();
    exit;
}

// --- Posts ---
elseif ($uri === 'post') { // /post?id=123
    $controller = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    if (is_post()) $controller->comment($id); else $controller->show($id);
    exit;
}

elseif ($uri === 'post/create') { // /post/create?b=123
    $controller = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    if (is_post()) $controller->create($boardId); else $controller->createForm($boardId);
    exit;
}

// --- Search ---
elseif ($uri === 'search') {
    (new SearchController())->index();
    exit;
}

// --- Tags ---
elseif ($uri === 'tags') {
    (new TagController())->index();
    exit;
}

elseif ($uri === 'tag') { // /tag?slug=php
    $slug = (string)($_GET['slug'] ?? '');
    (new TagController())->show($slug);
    exit;
}

// Pretty route: /tag/{slug}
elseif (preg_match('#^tag/([^/]+)$#', $uri, $m)) {
    $slug = $m[1];
    (new TagController())->show($slug);
    exit;
}

// -------------------------------------------------------------
// 404 Fallback
// -------------------------------------------------------------
http_response_code(404);
echo "404 Not Found";
exit;
