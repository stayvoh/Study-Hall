<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Search.php';
require_once __DIR__ . '/../Tag.php';

class SearchController extends BaseController {
    public function index(): void {
        $db = Database::getConnection();
        $search = new Search($db);

        $q        = (string)($_GET['q'] ?? '');
        $type     = (string)($_GET['type'] ?? 'posts'); // posts|users|tags
        $tag      = (string)($_GET['tag'] ?? '');
        $board_id = (int)($_GET['board_id'] ?? 0);
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = 20;
        $offset   = ($page - 1) * $limit;

        $opts = ['q'=>$q, 'tag'=>$tag, 'board_id'=>$board_id, 'limit'=>$limit, 'offset'=>$offset];

        if ($type === 'users')      { $results = $search->users($opts); }
        else if ($type === 'tags')  { $results = $search->tags($opts); }
        else                        { $type = 'posts'; $results = $search->posts($opts); }

        $this->render('search.php', [
            'q'        => $q,
            'type'     => $type,
            'results'  => $results,
            'page'     => $page,
            'limit'    => $limit,
            'board_id' => $board_id,
            'tag'      => $tag,
        ]);
    }

    // Simple tag suggestion endpoint: /tags/suggest?q=ph
    public function suggestTags(): void {
        header('Content-Type: application/json');
        $db = Database::getConnection();
        $t  = new Tag($db);
        $q  = (string)($_GET['q'] ?? '');
        echo json_encode($t->suggest($q), JSON_UNESCAPED_SLASHES);
    }
}
