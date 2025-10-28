<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BaseController.php';

class SearchController extends BaseController
{
    public function index(): void
    {
        $db    = Database::getConnection();
        $type  = (string)($_GET['type'] ?? 'posts');
        $q     = trim((string)($_GET['q'] ?? ''));
        $tag   = trim((string)($_GET['tag'] ?? ''));
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $off   = ($page - 1) * $limit;
        $results = [];

        /* ---------- USERS ---------- */
        if ($type === 'users') {
            $sql = "
                SELECT ua.id, ua.email, ua.created_at, COALESCE(up.username, '') AS username
                FROM user_account ua
                LEFT JOIN user_profile up ON up.user_id = ua.id";
            $params = [];
            if ($q !== '') {
                $sql .= " WHERE COALESCE(up.username, ua.email) LIKE :q";
                $params[':q'] = "%{$q}%";
            }
            $sql .= " ORDER BY ua.created_at DESC LIMIT :lim OFFSET :off";
            $st = $db->prepare($sql);
            foreach ($params as $k => $v) $st->bindValue($k, $v);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->bindValue(':off', $off,  PDO::PARAM_INT);
            $st->execute();
            $results = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        /* ---------- TAGS ---------- */
        } elseif ($type === 'tags') {
            $sql = "
                SELECT t.id, t.name, t.slug, COUNT(pt.post_id) AS usage_count
                FROM tag t
                LEFT JOIN post_tag pt ON pt.tag_id = t.id";
            $params = [];
            if ($q !== '') {
                $sql .= " WHERE t.name LIKE :q";
                $params[':q'] = "%{$q}%";
            }
            $sql .= "
                GROUP BY t.id, t.name, t.slug
                ORDER BY usage_count DESC, t.name ASC
                LIMIT :lim OFFSET :off";
            $st = $db->prepare($sql);
            foreach ($params as $k => $v) $st->bindValue($k, $v);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->bindValue(':off', $off,  PDO::PARAM_INT);
            $st->execute();
            $results = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        /* ---------- POSTS (default) ---------- */
        } else {
            $sql = "
                SELECT
                    p.id, p.title, p.body, p.created_at, p.board_id,
                    COALESCE(up.username, ua.email) AS author,
                    GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', t.slug) SEPARATOR '|') AS tag_blob
                FROM post p
                JOIN user_account ua ON ua.id = p.created_by
                LEFT JOIN user_profile up ON up.user_id = ua.id
                LEFT JOIN post_tag pt ON pt.post_id = p.id
                LEFT JOIN tag t       ON t.id = pt.tag_id";
            $where  = [];
            $params = [];

            if ($q !== '') {
                $where[] = '(p.title LIKE :q OR p.body LIKE :q)';
                $params[':q'] = "%{$q}%";
            }
            if ($tag !== '') {
                $where[] = "EXISTS (
                    SELECT 1 FROM post_tag pt2
                    JOIN tag t2 ON t2.id = pt2.tag_id
                    WHERE pt2.post_id = p.id AND t2.slug = :tag
                )";
                $params[':tag'] = $tag;
            }
            if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
            $sql .= "
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT :lim OFFSET :off";

            $st = $db->prepare($sql);
            foreach ($params as $k => $v) $st->bindValue($k, $v);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->bindValue(':off', $off,  PDO::PARAM_INT);
            $st->execute();

            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $results = array_map(function(array $r) {
                $tags = [];
                if (!empty($r['tag_blob'])) {
                    foreach (explode('|', $r['tag_blob']) as $pair) {
                        [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
                        if ($name !== '' && $slug !== '') $tags[] = ['name'=>$name, 'slug'=>$slug];
                    }
                }
                $r['tags'] = $tags;
                return $r;
            }, $rows);
            $type = 'posts';
        }

        $this->render('search.php', [
            'q'       => $q,
            'type'    => $type,
            'tag'     => $tag,
            'results' => $results,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }
}
