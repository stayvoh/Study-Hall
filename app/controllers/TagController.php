<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Tag.php';

class TagController {
    private function render(string $view, array $data = []): void {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/' . $view;
    }

    public function index(): void {
        $db = Database::getConnection();
        $tags = $db->query("
            SELECT t.id, t.name, t.slug,
                   (SELECT COUNT(*) FROM post_tag x WHERE x.tag_id = t.id) AS usage_count
            FROM tag t
            ORDER BY usage_count DESC, t.name ASC
            LIMIT 200
        ")->fetchAll() ?: [];

        $this->render('tags.php', ['tags' => $tags]);
    }

    public function show(string $slug): void {
        $db = Database::getConnection();

        $tagStmt = $db->prepare("SELECT id, name, slug FROM tag WHERE slug = :slug");
        $tagStmt->execute([':slug' => $slug]);
        $tag = $tagStmt->fetch();

        if (!$tag) {
            http_response_code(404);
            echo 'Tag not found';
            return;
        }

        $stmt = $db->prepare("
            SELECT p.id, p.title, p.body, p.created_at,
                   COALESCE(up.username, ua.email) AS author
            FROM post p
            JOIN user_account ua ON ua.id = p.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE EXISTS (
                SELECT 1 FROM post_tag pt WHERE pt.post_id = p.id AND pt.tag_id = :tid
            )
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':tid' => $tag['id']]);
        $posts = $stmt->fetchAll() ?: [];

        $this->render('tags_show.php', ['tag' => $tag, 'posts' => $posts]);
    }

    public function suggest(): void {
        header('Content-Type: application/json');
        $db  = Database::getConnection();
        $t   = new Tag($db);
        $q   = (string)($_GET['q'] ?? '');
        echo json_encode($t->suggest($q), JSON_UNESCAPED_SLASHES);
    }
}
