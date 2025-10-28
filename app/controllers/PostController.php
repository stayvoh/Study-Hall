<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Board.php';

class PostController extends BaseController
{
    public function createForm(int $boardId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }
        $pdo = Database::getConnection();
        $tagsStmt = $pdo->query("SELECT id, name, slug FROM tag ORDER BY name");
        $allTags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
        $this->render('post_create', ['boardId' => $boardId, 'allTags' => $allTags]);
    }

    public function create(int $boardId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $title    = trim($_POST['title'] ?? '');
        $body     = trim($_POST['body']  ?? '');
        $csvTags  = (string)($_POST['new_tags'] ?? ''); // comma-separated, from the form
        $old = ['title' => $title, 'body' => $body, 'tags' => []];

        if ($title === '' || $body === '') {
            $this->render('post_create.php', [
                'boardId' => $boardId,
                'error'   => 'Title and body are required',
                'old'     => $old,
            ]);
            return;
        }

        // Create post
        $postId = Post::create($boardId, (int)$_SESSION['uid'], $title, $body);

        // Attach tags (create any missing)
        $pdo = Database::getConnection();
        $tagModel = new Tag($pdo);
        $rows = $tagModel->ensureManyFromCsv($csvTags);
        $tagModel->attachToPost($postId, array_column($rows, 'id'));

        header('Location: /post?id=' . $postId);
        exit;
    }


    public function show(int $id): void {
        $rec = Post::findOneWithMeta($id);
        if (!$rec) { http_response_code(404); echo 'Post not found'; return; }

        // Default is_question for legacy templates that check it
        $post = [
            'id'           => $rec['id'],
            'title'        => $rec['title'],
            'body'         => $rec['body'],
            'created_at'   => $rec['created_at'],
            'author'       => $rec['author'] ?? 'User',
            'board_id'     => $rec['board_id'] ?? null,
            'is_question'  => 0,
        ];
        $tags     = $rec['tags'] ?? [];
        $comments = $rec['comments'] ?? [];

        $this->render('post_show', compact('post', 'tags', 'comments'));
    }

    public function comment(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        // Optional CSRF check if your view posts a token
        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            $rec = Post::findOneWithMeta($id);
            if ($rec) {
                $post = [
                    'id' => $rec['id'], 'title' => $rec['title'], 'body' => $rec['body'],
                    'created_at' => $rec['created_at'], 'author' => $rec['author'] ?? 'User',
                    'board_id' => $rec['board_id'] ?? null,
                    'is_question' => 0,
                ];
                $tags     = $rec['tags'] ?? [];
                $comments = $rec['comments'] ?? [];
                $error    = 'Invalid request. Please try again.';
                $this->render('post_show', compact('post','tags','comments','error'));
                return;
            }
            http_response_code(400); echo 'Invalid request'; return;
        }

        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '') { header('Location: /post?id=' . $id); exit; }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO comment (post_id, created_by, body) VALUES (:pid, :uid, :body)");
        $stmt->execute([
            ':pid'  => $id,
            ':uid'  => (int)$_SESSION['uid'],
            ':body' => $body,
        ]);

        header('Location: /post?id=' . $id . '#c' . $pdo->lastInsertId());
        exit;
    }
}
