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
require __DIR__ . '/../models/BoardFollow.php';

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
    $id   = (int)($_GET['id'] ?? ($_GET['b'] ?? 0));
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
elseif ($uri === 'post') {
    $controller = new PostController();
    $id = (int)($_GET['id'] ?? 0);
    if (is_post()) $controller->comment($id); else $controller->show($id);
    exit;
}

elseif ($uri === 'post/create') {
    $controller = new PostController();
    $boardId = (int)($_GET['b'] ?? 0);
    if (is_post()) $controller->create($boardId); else $controller->createForm($boardId);
    exit;
}

elseif ($uri === 'post/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new PostController())->delete((int)($_GET['id'] ?? 0));
    exit;
}

elseif ($uri === 'comment/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    (new PostController())->deleteComment((int)($_GET['id'] ?? 0));
    exit;
}
elseif ($uri === 'post/edit') {
    // GET /post/edit?id=123  -> show edit form
    $id = (int)($_GET['id'] ?? 0);
    (new PostController())->editPost($id);
    exit;
}

elseif ($uri === 'post/update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST /post/update?id=123 -> save changes
    $id = (int)($_GET['id'] ?? 0);
    (new PostController())->update($id);
    exit;
}

// follow
elseif (preg_match('#^boards/(\d+)/(follow|unfollow)$#', $uri, $m)) {
    $boardId = (int)$m[1];
    $action  = $m[2]; // 'follow' or 'unfollow'

    if (!is_post()) { http_response_code(405); echo 'Method Not Allowed'; exit; }

    $controller = new BoardController();
    if ($action === 'follow')   { $controller->follow($boardId); }
    else                        { $controller->unfollow($boardId); }
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

elseif ($uri === 'tag') {
    $slug = (string)($_GET['slug'] ?? '');
    (new TagController())->show($slug);
    exit;
}

elseif ($uri === 'profile/follow') {
    if (!is_post()) { http_response_code(405); exit; }
    $profileId = (int)($_POST['profile_id'] ?? 0);
    $loggedInUserId = $_SESSION['uid'] ?? 0;
    (new ProfileController())->follow($loggedInUserId, $profileId);
    header("Location: /profile?id=$profileId");
    exit;
}

elseif ($uri === 'profile/unfollow') {
    if (!is_post()) { http_response_code(405); exit; }
    $profileId = (int)($_POST['profile_id'] ?? 0);
    $loggedInUserId = $_SESSION['uid'] ?? 0;
    (new ProfileController())->unfollow($loggedInUserId, $profileId);
    header("Location: /profile?id=$profileId");
    exit;
}
elseif ($uri === 'profile/followers') {
    (new ProfileController())->followers();
    exit;
} elseif ($uri === 'profile/following') {
    (new ProfileController())->following();
    exit;
} 

elseif ($uri === 'api/tags/suggest') {
    (new TagController())->suggest();
    exit;
}

elseif ($uri === 'profile/follow') {
    if (!is_post()) { http_response_code(405); exit; }
    $profileId = (int)($_POST['profile_id'] ?? 0);
    $loggedInUserId = $_SESSION['uid'] ?? 0;
    (new ProfileController())->follow($loggedInUserId, $profileId);
    header("Location: /profile?id=$profileId");
    exit;
}

elseif ($uri === 'profile/unfollow') {
    if (!is_post()) { http_response_code(405); exit; }
    $profileId = (int)($_POST['profile_id'] ?? 0);
    $loggedInUserId = $_SESSION['uid'] ?? 0;
    (new ProfileController())->unfollow($loggedInUserId, $profileId);
    header("Location: /profile?id=$profileId");
    exit;
}
elseif ($uri === 'profile/followers') {
    (new ProfileController())->followers();
    exit;
} elseif ($uri === 'profile/following') {
    (new ProfileController())->following();
    exit;
} 

// -------------------------------------------------------------
// 404 Fallback
// -------------------------------------------------------------
http_response_code(404);
echo "404 Not Found";
exit;
