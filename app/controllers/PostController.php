<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Tag.php';

class PostController extends BaseController
{
    public function show(int $id): void
    {
        $this->renderPost($id, null);
    }

    // Handles POST /post?id={id}
    public function comment(int $id): void
    {
        $db   = Database::getConnection();
        $body = trim((string)($_POST['body'] ?? ''));

        // Optional CSRF check if helper exists
        if (function_exists('csrf_check') && !csrf_check((string)($_POST['csrf'] ?? ''))) {
            $this->renderPost($id, 'Invalid request. Please try again.');
            return;
        }

        // Require logged-in user
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->renderPost($id, 'Please log in to comment.');
            return;
        }

        if ($body === '') {
            $this->renderPost($id, 'Comment cannot be empty.');
            return;
        }

        // Save comment
        $stmt = $db->prepare("INSERT INTO comment (post_id, user_id, body) VALUES (:pid, :uid, :body)");
        $stmt->execute([':pid' => $id, ':uid' => $userId, ':body' => $body]);

        // PRG pattern: redirect back to the post
        header('Location: /post?id=' . (int)$id);
        exit;
    }

    /**
     * Loads post + tags + comments and renders the view.
     */
    private function renderPost(int $id, ?string $error): void
    {
        $db = Database::getConnection();

        // Post + author
        $stmt = $db->prepare("
            SELECT p.id, p.title, p.body, p.created_at, p.board_id,
                   COALESCE(up.username, ua.email) AS author
            FROM post p
            JOIN user_account ua ON ua.id = p.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();
        if (!$post) { http_response_code(404); echo 'Post not found'; return; }

        // Tags
        $tagModel = new Tag($db);
        $tags = $tagModel->tagsForPost($id);  // [{id,name,slug}]

        // Comments + author
        $cstmt = $db->prepare("
            SELECT c.id, c.body, c.created_at,
                   COALESCE(up.username, ua.email) AS author
            FROM comment c
            JOIN user_account ua ON ua.id = c.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE c.post_id = :pid
            ORDER BY c.created_at ASC
        ");
        $cstmt->execute([':pid' => $id]);
        $comments = $cstmt->fetchAll() ?: [];

        $this->render('post_show', [
            'post'     => $post,
            'tags'     => $tags,
            'comments' => $comments,
            'error'    => $error,
        ]);
    }
}
