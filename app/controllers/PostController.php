<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Comment.php';

class PostController extends BaseController
{
    // GET /post/create?b=ID
    public function createForm(int $boardId): void {
        $this->render('post_create', [
            'boardId' => $boardId
        ]);
    }

    // POST /post/create?b=ID
    public function create(int $boardId): void {
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        $error = null;

        if ($title === '' || $body === '') {
            $error = 'Title and body are required.';
        }

        if ($error) {
            $this->render('post_create', [
                'boardId' => $boardId,
                'error'   => $error
            ]);
            return;
        }

        $postId = Post::create($boardId, (int)$_SESSION['uid'], $title, $body);
        header('Location: /post?id=' . $postId);
        exit;
    }

    // GET /post?id=ID
    public function show(int $id): void {
        $thread = Post::findById($id);
        if (!$thread) {
            http_response_code(404);
            echo "Post not found";
            return;
        }

        $comments = Comment::allByPost($id);
        $this->render('post_show', [
            'thread'   => $thread,
            'comments' => $comments,
            'error'    => null
        ]);
    }

    // POST /post?id=ID
    public function comment(int $id): void {
        $thread = Post::findById($id);
        if (!$thread) {
            http_response_code(404);
            echo "Post not found";
            return;
        }

        $error = null;
        $body  = trim($_POST['body'] ?? '');

        if ($body === '') {
            $error = 'Comment cannot be empty.';
        }

        if ($error) {
            $comments = Comment::allByPost($id);
            $this->render('post_show', [
                'thread'   => $thread,
                'comments' => $comments,
                'error'    => $error
            ]);
            return;
        }

        Comment::create($id, (int)$_SESSION['uid'], $body);
        header('Location: /post?id=' . $id);
        exit;
    }
}
