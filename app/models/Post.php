<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class Post
{
    /**
     * Backward-compatible create(): accepts (boardId, userId, title, body, [isQuestion])
     * The 5th argument is ignored (column removed) to avoid "too many args" fatals.
     */
    public static function create(
        int $boardId,
        int $userId,
        string $title,
        string $body,
        $isQuestion = null // 👈 compatibility shim: accept but IGNORE
    ): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO post (board_id, created_by, title, body)
            VALUES (:b, :u, :t, :bd)
        ");
        $stmt->bindValue(':b', $boardId, \PDO::PARAM_INT);
        $stmt->bindValue(':u', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':t', $title, \PDO::PARAM_STR);
        $stmt->bindValue(':bd', $body, \PDO::PARAM_STR);
        $stmt->execute();
        return (int)$pdo->lastInsertId();
    }

    /**
     * Fetch a post and attach tags + comments for your PostController::show().
     * Returns:
     *  [
     *    id, title, body, created_at, board_id,
     *    author (username or email),
     *    tags => [ ['id','name','slug'], ... ],
     *    comments => [ ['id','body','created_at','author'], ... ],
     *  ]
     */
    public static function findOneWithMeta(int $postId): ?array
        {
            $pdo = Database::getConnection();

            // Base post + board + author
            $stmt = $pdo->prepare("
                SELECT p.id, p.title, p.body, p.created_at, p.board_id, p.created_by,
                    COALESCE(up.username, ua.email) AS author
                FROM post p
                JOIN user_account ua ON ua.id = p.created_by
                LEFT JOIN user_profile up ON up.user_id = ua.id
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->bindValue(':id', $postId, \PDO::PARAM_INT);
            $stmt->execute();
            $post = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$post) return null;

            // Tags
            $t = $pdo->prepare("
                SELECT t.id, t.name, t.slug
                FROM tag t
                JOIN post_tag pt ON pt.tag_id = t.id
                WHERE pt.post_id = :pid
                ORDER BY t.name ASC
            ");
            $t->bindValue(':pid', $postId, \PDO::PARAM_INT);
            $t->execute();
            $tags = $t->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Comments
            $c = $pdo->prepare("
                SELECT c.id, c.body, c.created_at, c.created_by,
                    COALESCE(up.username, ua.email) AS author
                FROM comment c
                JOIN user_account ua ON ua.id = c.created_by
                LEFT JOIN user_profile up ON up.user_id = ua.id
                WHERE c.post_id = :pid
                ORDER BY c.created_at ASC
            ");
            $c->bindValue(':pid', $postId, \PDO::PARAM_INT);
            $c->execute();
            $comments = $c->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $post['tags'] = $tags;
            $post['comments'] = $comments;

            return $post;
        }

        public static function findByUser(int $userId, int $limit = 50): array {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT p.id, p.title, p.body, p.created_at, p.board_id,
                     p.created_by,
                    COALESCE(up.username, ua.email) AS author
                FROM post p
                JOIN user_account ua ON ua.id = p.created_by
                LEFT JOIN user_profile up ON up.user_id = ua.id
                WHERE p.created_by = :uid
                ORDER BY p.created_at DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }


    // (Optional helpers — safe to keep if other pages use them)

    public static function countByBoard(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM post WHERE board_id = :bid');
        $stmt->bindValue(':bid', $boardId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public static function findByBoard(int $boardId, int $perPage = 20, int $offset = 0): array
{
    $pdo = Database::getConnection();
    $sql = "
        SELECT
            p.id, p.board_id, p.created_by, p.title, p.body,
            p.created_at,
            COALESCE(up.username, ua.email) AS author,
            GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', t.slug) SEPARATOR '|') AS tag_blob
        FROM post p
        JOIN user_account ua ON ua.id = p.created_by
        LEFT JOIN user_profile up ON up.user_id = ua.id
        LEFT JOIN post_tag pt ON pt.post_id = p.id
        LEFT JOIN tag t       ON t.id = pt.tag_id
        WHERE p.board_id = :b
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT :lim OFFSET :off
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':b',   $boardId, \PDO::PARAM_INT);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

    return array_map(function ($r) {
        // Parse tags
        $tags = [];
        if (!empty($r['tag_blob'])) {
            foreach (explode('|', $r['tag_blob']) as $pair) {
                [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
                if ($name !== '' && $slug !== '') {
                    $tags[] = ['name' => $name, 'slug' => $slug];
                }
            }
        }
        $r['tags']    = $tags;
        $r['excerpt'] = mb_strimwidth((string)($r['body'] ?? ''), 0, 200, '…');
        return $r;
    }, $rows);
}



}
