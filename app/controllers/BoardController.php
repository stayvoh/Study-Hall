<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Board.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/BoardFollow.php';

class BoardController extends BaseController
{
    // GET /boards
    public function index(): void {
        $boards = Board::all();
        $this->render('boards_index', [
            'boards' => $boards
        ]);
    }

    // GET /board?b=ID&page=N  (or /board/{id}?page=N depending on your router)
    public function show(int $id, int $page = 1): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    $board = Board::findById($id);
    if (!$board) {
        http_response_code(404);
        echo "Board not found";
        return;
    }

    $perPage = 20;
    $total   = Post::countByBoard($id);
    $posts   = Post::findByBoard($id, $perPage, ($page - 1) * $perPage);
    $pages   = max(1, (int)ceil($total / $perPage));

    $uid = (int)($_SESSION['uid'] ?? 0);
    $isFollowing   = $uid ? BoardFollow::isFollowing($uid, $id) : false;
    $followerCount = BoardFollow::followersCount($id);

    $this->render('board_show', [
        'board'          => $board,
        'posts'          => $posts,
        'page'           => $page,
        'pages'          => $pages,
        'isFollowing'    => $isFollowing,
        'followerCount'  => $followerCount,
        'csrf'           => $_SESSION['csrf'],
    ]);
}

    /** Small helper to send users back to where they came from (or to the board). */
    private function redirectBackToBoard(int $boardId): void {
        $fallback = '/board?id=' . $boardId; // adjust to your route (e.g., '/boards/'.$boardId)
        $to = $_SERVER['HTTP_REFERER'] ?? $fallback;
        header('Location: ' . $to);
        exit;
    }

    public function follow(int $boardId): void {
        require_login();
        BoardFollow::follow((int)$_SESSION['uid'], $boardId);
        $this->redirectBackToBoard($boardId);
    }

    public function unfollow(int $boardId): void {
        require_login();
        BoardFollow::unfollow((int)$_SESSION['uid'], $boardId);
        $this->redirectBackToBoard($boardId);
    }

    // GET /board/create
    public function createForm(): void {
        $this->render('board_create');
    }

    // POST /board/create
    public function create(): void {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            $this->render('board_create', ['error' => 'Name required']);
            return;
        }

        Board::create($name, $desc);
        $this->render('board_create', ['success' => true]);
    }
}
