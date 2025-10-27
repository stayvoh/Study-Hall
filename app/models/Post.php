<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class Post
{
    public static function countByBoard(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM post WHERE board_id = :bid');
        $stmt->bindValue(':bid', $boardId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Returns posts for a board with author and tags.
     * - Author = user_profile.username if present, else user_account.email
     * - Tags are returned as [['name'=>..., 'slug'=>...], ...] in 'tags' key
     */
    public static function findByBoard(int $boardId, int $limit, int $offset): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT
                p.id,
                p.title,
                p.body,
                p.created_at,
                COALESCE(up.username, ua.email) AS author,
                GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', t.slug) SEPARATOR '|') AS tag_blob
            FROM post p
            JOIN user_account ua ON ua.id = p.created_by
            LEFT JOIN user_profile up ON up.user_id = ua.id
            LEFT JOIN post_tag pt ON pt.post_id = p.id
            LEFT JOIN tag t       ON t.id = pt.tag_id
            WHERE p.board_id = :bid
            GROUP BY p.id, p.title, p.body, p.created_at, author
            ORDER BY p.created_at DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bid', $boardId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // unpack tags + add excerpt to mirror your board_show usage
        foreach ($rows as &$r) {
            $tags = [];
            if (!empty($r['tag_blob'])) {
                foreach (explode('|', $r['tag_blob']) as $pair) {
                    [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
                    if ($name !== '' && $slug !== '') {
                        $tags[] = ['name' => $name, 'slug' => $slug];
                    }
                }
            }
            $r['tags'] = $tags;
            $r['excerpt'] = mb_strimwidth((string)($r['body'] ?? ''), 0, 200, '…');
        }
        unset($r);

        return $rows;
    }
}
