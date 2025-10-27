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

    public static function create(int $boardId, int $userId, string $title, string $body, int $isQuestion = 1): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO post (board_id, created_by, title, body, is_question)
            VALUES (:b, :u, :t, :bd, :q)
        ");
        $stmt->bindValue(':b',  $boardId, PDO::PARAM_INT);
        $stmt->bindValue(':u',  $userId,  PDO::PARAM_INT);
        $stmt->bindValue(':t',  $title,   PDO::PARAM_STR);
        $stmt->bindValue(':bd', $body,    PDO::PARAM_STR);
        $stmt->bindValue(':q',  $isQuestion, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$pdo->lastInsertId();
    }

    /** Returns posts for a board with author and tags (for board_show). */
    public static function findByBoard(int $boardId, int $limit, int $offset): array {
        $pdo = Database::getConnection();
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

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
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bid', $boardId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $tags = [];
            if (!empty($r['tag_blob'])) {
                foreach (explode('|', $r['tag_blob']) as $pair) {
                    [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
                    if ($name !== '' && $slug !== '') $tags[] = ['name' => $name, 'slug' => $slug];
                }
            }
            $r['tags'] = $tags;
            $r['excerpt'] = mb_strimwidth((string)($r['body'] ?? ''), 0, 200, '…');
        }
        unset($r);

        return $rows;
    }

    /** Single post + tags + comments, shaped for controller->show() */
    public static function findOneWithMeta(int $postId): ?array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT
                p.id, p.board_id, p.title, p.body, p.is_question, p.created_at,
                COALESCE(up.username, ua.email) AS author
            FROM post p
            JOIN user_account ua ON ua.id = p.created_by
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE p.id = :pid
            LIMIT 1
        ";
        $st = $pdo->prepare($sql);
        $st->bindValue(':pid', $postId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // tags
        $ts = $pdo->prepare("
            SELECT t.id, t.name, t.slug
            FROM post_tag pt
            JOIN tag t ON t.id = pt.tag_id
            WHERE pt.post_id = :pid
            ORDER BY t.name
        ");
        $ts->bindValue(':pid', $postId, PDO::PARAM_INT);
        $ts->execute();
        $row['tags'] = $ts->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // comments
        $cs = $pdo->prepare("
            SELECT c.id, c.body, c.is_answer, c.is_accepted, c.created_at,
                   COALESCE(up.username, ua.email) AS author
            FROM comment c
            JOIN user_account ua ON ua.id = c.created_by
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE c.post_id = :pid
            ORDER BY c.created_at ASC
        ");
        $cs->bindValue(':pid', $postId, PDO::PARAM_INT);
        $cs->execute();
        $row['comments'] = $cs->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $row;
    }
}
