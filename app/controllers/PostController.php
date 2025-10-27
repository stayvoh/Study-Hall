<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Tag.php';

/**
 * Controller for Post-related actions:
 * - Show a post (with comments and tags)
 * - Add a comment
 * - Show "create post" form
 * - Handle "create post" submission
 */
class PostController extends BaseController
{
    /**
     * GET /post?id={id}
     * Render a single post page.
     */
    public function show(int $id): void
    {
        $this->renderPost($id, null);
    }

    /**
     * POST /post?id={id}
     * Add a comment to a post.
     */
    public function comment(int $id): void
    {
        $db   = Database::getConnection();
        $body = trim((string)($_POST['body'] ?? ''));

        // CSRF protection (if helper exists)
        if (function_exists('csrf_check') && !csrf_check((string)($_POST['csrf'] ?? ''))) {
            $this->renderPost($id, 'Invalid request. Please try again.');
            return;
        }

        // Require login
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->renderPost($id, 'Please log in to comment.');
            return;
        }

        if ($body === '') {
            $this->renderPost($id, 'Comment cannot be empty.');
            return;
        }

        // Insert comment
        $stmt = $db->prepare("INSERT INTO comment (post_id, user_id, body) VALUES (:pid, :uid, :body)");
        $stmt->execute([':pid' => $id, ':uid' => $userId, ':body' => $body]);

        // Redirect (Post/Redirect/Get pattern)
        header('Location: /post?id=' . (int)$id);
        exit;
    }

    /**
     * GET /post/create?b={boardId}
     * Show the "new post" form.
     */
    public function createForm(int $boardId): void
    {
        // Require login
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: /login');
            exit;
        }

        // Render the post creation view
        $this->render('post_create', [
            'boardId' => $boardId,
            'error'   => null,
        ]);
    }

    /**
     * POST /post/create?b={boardId}
     * Create a new post in the given board.
     */
    public function create(int $boardId): void
    {
        $db     = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            header('Location: /login');
            exit;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $body  = trim((string)($_POST['body'] ?? ''));

        // CSRF protection
        if (function_exists('csrf_check') && !csrf_check((string)($_POST['csrf'] ?? ''))) {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => 'Invalid request. Please try again.'
            ]);
            return;
        }

        if ($title === '' || $body === '') {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => 'Title and body are required.'
            ]);
            return;
        }

        // Insert post into DB
        $stmt = $db->prepare("
            INSERT INTO post (board_id, user_id, title, body)
            VALUES (:board, :uid, :title, :body)
        ");
        $stmt->execute([
            ':board' => $boardId,
            ':uid'   => $userId,
            ':title' => $title,
            ':body'  => $body
        ]);
        $postId = (int)$db->lastInsertId();

        // Optional: attach tags from form
        if (!empty($_POST['tags'] ?? [])) {
            $tagModel = new Tag($db);
            foreach ((array)$_POST['tags'] as $slug) {
                $tagModel->attachToPost($postId, $slug);
            }
        }

        // Redirect to the new post
        header('Location: /post?id=' . $postId);
        exit;
    }

    /**
     * Helper to load post details, tags, and comments,
     * then render the post view.
     */
    private function renderPost(int $id, ?string $error): void
    {
        $db = Database::getConnection();

        // Load post + author
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

        // Load tags
        $tagModel = new Tag($db);
        $tags = $tagModel->tagsForPost($id);

        // Load comments + author
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

        // Render the post view
        $this->render('post_show', [
            'post'     => $post,
            'tags'     => $tags,
            'comments' => $comments,
            'error'    => $error,
        ]);
    }
}
