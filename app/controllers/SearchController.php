<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class SearchController extends BaseController
{
    private function renderView(string $view, array $data = []): void {
        // Use BaseController::render if you prefer; this helper is tolerant.
        if (method_exists($this, 'render')) {
            $this->render($view, $data);
            return;
        }
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/' . (substr($view, -4) === '.php' ? $view : $view . '.php');
    }

    public function index(): void
    {
        $db     = Database::getConnection();
        $q      = trim((string)($_GET['q'] ?? ''));
        $type   = (string)($_GET['type'] ?? 'posts');
        $tag    = trim((string)($_GET['tag'] ?? ''));    // tag slug for posts
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        // USERS
        if ($type === 'users') {
            $sql = "
                SELECT ua.id, ua.email, COALESCE(up.username,'') AS username, ua.created_at
                FROM user_account ua
                LEFT JOIN user_profile up ON up.user_id = ua.id
                " . ($q !== '' ? "WHERE (up.username LIKE :q1 OR ua.email LIKE :q2)" : "") . "
                ORDER BY COALESCE(up.username, ua.email) ASC
                LIMIT :lim OFFSET :off
            ";
            $stmt = $db->prepare($sql);
            if ($q !== '') {
                $like = '%'.$q.'%';
                $stmt->bindValue(':q1', $like);
                $stmt->bindValue(':q2', $like);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll() ?: [];
            $this->renderView('search', compact('q','type','tag','results','page','limit'));
            return;
        }

        // TAGS
        if ($type === 'tags') {
            $sql = "
                SELECT t.id, t.name, t.slug,
                       (SELECT COUNT(*) FROM post_tag x WHERE x.tag_id = t.id) AS usage_count
                FROM tag t
                " . ($q !== '' ? "WHERE t.name LIKE :q" : "") . "
                ORDER BY usage_count DESC, t.name ASC
                LIMIT :lim OFFSET :off
            ";
            $stmt = $db->prepare($sql);
            if ($q !== '') {
                $stmt->bindValue(':q', '%'.$q.'%');
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll() ?: [];
            $this->renderView('search', compact('q','type','tag','results','page','limit'));
            return;
        }

        // POSTS (default)
        $where = [];
        if ($q !== '') {
            $where[] = "(p.title LIKE :q1 OR p.body LIKE :q2)";
        }
        if ($tag !== '') {
            $where[] = "EXISTS (
                SELECT 1 FROM post_tag pt
                JOIN tag t ON t.id = pt.tag_id
                WHERE pt.post_id = p.id AND t.slug = :slug
            )";
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT p.id, p.title, p.body, p.created_at,
                   COALESCE(up.username, ua.email) AS author,
                   GROUP_CONCAT(DISTINCT CONCAT(t2.name, ':', t2.slug) SEPARATOR '|') AS tag_blob
            FROM post p
            JOIN user_account ua ON ua.id = p.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            LEFT JOIN post_tag pt2 ON pt2.post_id = p.id
            LEFT JOIN tag t2       ON t2.id = pt2.tag_id
            $whereSql
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $db->prepare($sql);
        if ($q !== '') {
            $like = '%'.$q.'%';
            $stmt->bindValue(':q1', $like);
            $stmt->bindValue(':q2', $like);
        }
        if ($tag !== '') {
            $stmt->bindValue(':slug', $tag);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll() ?: [];
        $results = array_map(function ($r) {
            $tags = [];
            if (!empty($r['tag_blob'])) {
                foreach (explode('|', $r['tag_blob']) as $pair) {
                    [$n, $s] = array_pad(explode(':', $pair, 2), 2, '');
                    if ($n !== '' && $s !== '') $tags[] = ['name' => $n, 'slug' => $s];
                }
            }
            $r['tags'] = $tags;
            return $r;
        }, $rows);
        $type = 'posts';

        $this->renderView('search', compact('q','type','tag','results','page','limit'));
    }
}
