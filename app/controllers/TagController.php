<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Tag.php';

class TagController extends BaseController {
    public function index(): void {
        $db  = Database::getConnection();
        $tag = new Tag($db);
        $q   = (string)($_GET['q'] ?? '');
        $tags = $tag->popular(200, $q);
        $this->render('tags.php', ['tags' => $tags]);
    }

    public function show(string $slug): void {
        $db  = Database::getConnection();
        $tag = (new Tag($db))->bySlug($slug);
        if (!$tag) { http_response_code(404); echo "Tag not found"; return; }

        $sql = "SELECT p.id, p.title, p.body, p.created_at, p.board_id,
                       COALESCE(up.username, CONCAT('User #', p.created_by)) AS author
                FROM post_tag pt
                JOIN post p ON p.id = pt.post_id
                LEFT JOIN user_profile up ON up.user_id = p.created_by
                WHERE pt.tag_id = :tid
                ORDER BY p.created_at DESC
                LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute([':tid' => $tag['id']]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('tags_show.php', ['tag' => $tag, 'posts' => $posts]);
    }

    // GET /api/tags/suggest?q=ph
    public function suggest(): void {
        header('Content-Type: application/json');
        $db  = Database::getConnection();
        $t   = new Tag($db);
        $q   = (string)($_GET['q'] ?? '');
        echo json_encode($t->suggest($q), JSON_UNESCAPED_SLASHES);
    }
}
