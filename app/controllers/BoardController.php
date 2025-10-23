<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Board.php';
require_once __DIR__ . '/../models/Post.php';

class BoardController extends BaseController
{
    // GET /boards
    public function index(): void {
        $boards = Board::all();
        $this->render('boards_index', [
            'boards' => $boards
        ]);
    }

    // GET /board?b=ID&page=N
    public function show(int $id, int $page = 1): void {
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

        $this->render('board_show', [
            'board' => $board,
            'posts' => $posts,
            'page'  => $page,
            'pages' => $pages
        ]);
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
            $this->render('board_create', [
                'error' => 'Name required'
            ]);
            return;
        }

        Board::create($name, $desc);
        $this->render('board_create', [
            'success' => true
        ]);
    }
}
