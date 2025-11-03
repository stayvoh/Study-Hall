<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Board.php';
require_once __DIR__ . '/../models/Comment.php';

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

        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => 'Invalid request. Please try again.',
                'old'     => [
                    'title'    => (string)($_POST['title'] ?? ''),
                    'body'     => (string)($_POST['body'] ?? ''),
                    'new_tags' => (string)($_POST['new_tags'] ?? ''),
                    'tags'     => array_map('intval', $_POST['tags'] ?? []),
                ],
            ]);
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $body  = trim((string)($_POST['body'] ?? ''));

        if ($title === '' || $body === '') {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => 'Title and body are required.',
                'old'     => [
                    'title'    => $title,
                    'body'     => $body,
                    'new_tags' => (string)($_POST['new_tags'] ?? ''),
                    'tags'     => array_map('intval', $_POST['tags'] ?? []),
                ],
            ]);
            return;
        }

        if ($this->checkProfanity([$title, $body])) {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => 'Your post contains inappropriate language.',
                'old'     => [
                    'title'    => $title,
                    'body'     => $body,
                    'new_tags' => (string)($_POST['new_tags'] ?? ''),
                    'tags'     => array_map('intval', $_POST['tags'] ?? []),
                ],
            ]);
            return;
        }

        $postId = Post::create($boardId, (int)$_SESSION['uid'], $title, $body);

        $inputTagIds  = array_map('intval', $_POST['tags'] ?? []);
        $inputTagText = trim((string)($_POST['new_tags'] ?? ''));

        $inputTagNames = [];
        if ($inputTagText !== '') {
            $parts = preg_split('/[,\n]+/', $inputTagText) ?: [];
            foreach ($parts as $p) {
                $n = trim(preg_replace('/\s+/', ' ', $p));
                if ($n !== '') $inputTagNames[$n] = true;
            }
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            require_once __DIR__ . '/../models/Tag.php';
            $tagModel = new Tag($pdo);

            $allTagIds = [];
            foreach ($inputTagIds as $tid) {
                if ($tid > 0) $allTagIds[$tid] = true;
            }

            foreach (array_keys($inputTagNames) as $name) {
                $t = $tagModel->ensure($name);
                if (!empty($t['id'])) $allTagIds[(int)$t['id']] = true;
            }

            if ($allTagIds) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:pid, :tid)");
                foreach (array_keys($allTagIds) as $tid) {
                    $stmt->execute([':pid' => $postId, ':tid' => (int)$tid]);
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }

        header('Location: /post?id=' . $postId);
        exit;
    }

    public function show(int $id): void {
        $rec = Post::findOneWithMeta($id);
        if (!$rec) { http_response_code(404); echo 'Post not found'; return; }

        $post = [
            'id'         => $rec['id'],
            'title'      => $rec['title'],
            'body'       => $rec['body'],
            'created_at' => $rec['created_at'],
            'author'     => $rec['author'] ?? 'User',
            'board_id'   => $rec['board_id'] ?? null,
            'created_by' => $rec['created_by'] ?? 0,
            'is_question'=> 0,
        ];

        $tags     = $rec['tags'] ?? [];
        $comments = $rec['comments'] ?? [];

        $this->render('post_show', compact('post','tags','comments'));
    }

    public function comment(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            $rec = Post::findOneWithMeta($id);
            if ($rec) {
                $post = [
                    'id' => $rec['id'],
                    'title' => $rec['title'],
                    'body' => $rec['body'],
                    'created_at' => $rec['created_at'],
                    'author' => $rec['author'] ?? 'User',
                    'board_id' => $rec['board_id'] ?? null,
                    'created_by' => $rec['created_by'] ?? 0,
                ];
                $tags = $rec['tags'] ?? [];
                $comments = Comment::allByPost($id);
                $error = 'Invalid request. Please try again.';
                $this->render('post_show', compact('post','tags','comments','error'));
                return;
            }
            http_response_code(400); echo 'Invalid request'; return;
        }

        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '') { header('Location: /post?id=' . $id); exit; }

        if ($this->checkProfanity([$body])) {
            $rec = Post::findOneWithMeta($id);
            $post = [
                'id' => $rec['id'],
                'title' => $rec['title'],
                'body' => $rec['body'],
                'created_at' => $rec['created_at'],
                'author' => $rec['author'] ?? 'User',
                'board_id' => $rec['board_id'] ?? null,
                'created_by' => $rec['created_by'] ?? 0,
            ];
            $tags = $rec['tags'] ?? [];
            $comments = Comment::allByPost($id);
            $error = 'Your comment contains inappropriate language.';
            $this->render('post_show', compact('post','tags','comments','error'));
            return;
        }

        Comment::create($id, (int)$_SESSION['uid'], $body);
        header('Location: /post?id=' . $id . '#c' . Database::getConnection()->lastInsertId());
        exit;
    }

    public function delete(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            http_response_code(400); echo 'Invalid request'; return;
        }

        $rec = Post::findOneWithMeta($id);
        if (!$rec) { http_response_code(404); echo 'Post not found'; return; }
        $boardId = (int)($rec['board_id'] ?? 0);

        if (!Post::deleteOwned($id, (int)$_SESSION['uid'])) {
            http_response_code(403); echo 'Not allowed'; return;
        }

        header('Location: /board?id=' . $boardId);
        exit;
    }

    public function deleteComment(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            http_response_code(400); echo 'Invalid request'; return;
        }

        $postId = Comment::deleteOwned($id, (int)$_SESSION['uid']);
        if (!$postId) { http_response_code(403); echo 'Not allowed'; return; }

        header('Location: /post?id=' . $postId);
        exit;
    }

    // Keep method name as-is to match your current routing to /post/edit?id=...
    public function editPost(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $rec = Post::findOneWithMeta($id);
        if (!$rec) { http_response_code(404); echo 'Post not found'; return; }
        if ((int)$rec['created_by'] !== (int)$_SESSION['uid']) { http_response_code(403); echo 'Not allowed'; return; }

        $pdo = Database::getConnection();
        $allTags = $pdo->query("SELECT id, name, slug FROM tag ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $sel = array_map(
            static fn($t) => (int)$t['id'],
            (array)($rec['tags'] ?? [])
        );

        $boardId = (int)($rec['board_id'] ?? 0);

        $this->render('post_create', [
            'mode'    => 'edit',
            'boardId' => $boardId,
            'allTags' => $allTags,
            'sel'     => $sel,
            'post'    => ['id' => (int)$rec['id']],
            'old'     => [
                'title'    => (string)$rec['title'],
                'body'     => (string)$rec['body'],
                'tags'     => $sel,
                'new_tags' => '',
            ],
        ]);
    }

    public function edit(int $id): void {
        $this->editPost($id);
    }

    public function update(int $id): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['uid'])) { http_response_code(403); echo 'Login required'; return; }

        $postedCsrf = (string)($_POST['csrf'] ?? '');
        if (function_exists('csrf_token') && !hash_equals(csrf_token(), $postedCsrf)) {
            http_response_code(400); echo 'Invalid request'; return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $body  = trim((string)($_POST['body'] ?? ''));
        if ($title === '' || $body === '') { http_response_code(422); echo 'Title and body required'; return; }

        if (!Post::updateOwned($id, (int)$_SESSION['uid'], $title, $body)) {
            http_response_code(403); echo 'Not allowed or post missing'; return;
        }

        $inputTagIds  = array_map('intval', $_POST['tags'] ?? []);
        $inputTagText = trim((string)($_POST['new_tags'] ?? ''));

        $inputTagNames = [];
        if ($inputTagText !== '') {
            $parts = preg_split('/[,\n]+/', $inputTagText) ?: [];
            foreach ($parts as $p) {
                $n = trim(preg_replace('/\s+/', ' ', $p));
                if ($n !== '') $inputTagNames[$n] = true;
            }
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            require_once __DIR__ . '/../models/Tag.php';
            $tagModel = new Tag($pdo);

            $allTagIds = [];
            foreach ($inputTagIds as $tid) if ($tid > 0) $allTagIds[$tid] = true;
            foreach (array_keys($inputTagNames) as $name) {
                $t = $tagModel->ensure($name);
                if (!empty($t['id'])) $allTagIds[(int)$t['id']] = true;
            }

            $pdo->prepare("DELETE FROM post_tag WHERE post_id = :pid")->execute([':pid' => $id]);
            if ($allTagIds) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:pid, :tid)");
                foreach (array_keys($allTagIds) as $tid) {
                    $stmt->execute([':pid' => $id, ':tid' => (int)$tid]);
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }

        header('Location: /post?id=' . $id);
        exit;
    }

    public static function updateOwned(int $id, int $userId, string $title, string $body): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE post SET title = :t, body = :b WHERE id = :id AND created_by = :u');
        return $stmt->execute([':t'=>$title, ':b'=>$body, ':id'=>$id, ':u'=>$userId]) && $stmt->rowCount() > 0;
    }
}
