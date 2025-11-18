<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Board.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/BoardFollow.php';

class BoardController extends BaseController
{
    public function index(): void {
        $boards = Board::all();
        $this->render('boards_index', ['boards' => $boards]);
    }

    public function show(int $id, int $page = 1): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));

        $board = Board::findById($id);
        if (!$board) { http_response_code(404); echo "Board not found"; return; }

        $perPage = 20;
        $total   = Post::countByBoard($id);
        $posts   = Post::findByBoard($id, $perPage, ($page - 1) * $perPage);
        $pages   = max(1, (int)ceil($total / $perPage));

        $uid = (int)($_SESSION['uid'] ?? 0);
        $isFollowing   = $uid ? BoardFollow::isFollowing($uid, $id) : false;
        $followerCount = BoardFollow::followersCount($id);

        $this->render('board_show', compact('board','posts','page','pages','isFollowing','followerCount') + [
            'csrf' => $_SESSION['csrf']
        ]);
    }

    private function redirectBackToBoard(int $boardId): void {
        $fallback = '/board?id=' . $boardId;
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

    public function createForm(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
        $this->render('board_create', [
            'mode' => 'create',
            'old'  => ['name'=>'','description'=>''],
        ]);
    }

    public function create(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_login();
        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) { http_response_code(400); echo 'Invalid request'; return; }

        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($name === '') {
            $this->render('board_create', ['mode'=>'create','error'=>'Name required','old'=>['name'=>$name,'description'=>$desc]]);
            return;
        }

        $boardId = (int)Board::create($name, $desc, (int)$_SESSION['uid']);
        if ($boardId > 0) { header('Location: /board?id=' . $boardId); exit; }

        $this->render('board_create', ['mode'=>'create','error'=>'Failed to create board.','old'=>['name'=>$name,'description'=>$desc]]);
    }

    public function editForm(int $boardId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_login();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));

        $board = Board::findById($boardId);
        if (!$board) { http_response_code(404); echo 'Board not found'; return; }
        if (!Board::isOwnedBy($boardId, (int)$_SESSION['uid'])) { http_response_code(403); echo 'Not allowed'; return; }

        $this->render('board_create', [
            'mode'    => 'edit',
            'boardId' => $boardId,
            'old'     => ['name'=>$board['name'] ?? '', 'description'=>$board['description'] ?? ''],
        ]);
    }

    public function update(int $boardId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_login();
        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) { http_response_code(400); echo 'Invalid request'; return; }
        if (!Board::isOwnedBy($boardId, (int)$_SESSION['uid'])) { http_response_code(403); echo 'Not allowed'; return; }

        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($name === '') {
            $this->render('board_create', ['mode'=>'edit','boardId'=>$boardId,'error'=>'Board name is required.','old'=>['name'=>$name,'description'=>$desc]]);
            return;
        }

        $ok = Board::updateOwned($boardId, (int)$_SESSION['uid'], $name, $desc);
        if (!$ok) { http_response_code(403); echo 'Not allowed or board missing'; return; }
        header('Location: /board?id=' . $boardId); exit;
    }

    public function delete(int $boardId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        require_login();
        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) { http_response_code(400); echo 'Invalid request'; return; }
        if (!Board::isOwnedBy($boardId, (int)$_SESSION['uid'])) { http_response_code(403); echo 'Not allowed'; return; }
        if (!Board::deleteOwned($boardId, (int)$_SESSION['uid'])) { http_response_code(403); echo 'Not allowed'; return; }
        header('Location: /dashboard'); exit;
    }
}
