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

    public static function findWithExtras(int $id): ?array {
        $pdo = Database::getConnection();
        $sql = "SELECT p.*, COALESCE(up.username, CONCAT('User #', p.created_by)) AS author
                FROM post p
                LEFT JOIN user_profile up ON up.user_id = p.created_by
                WHERE p.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }




    public static function findOneWithMeta(int $postId): ?array
    {
        $pdo = Database::getConnection();

        // Base post + board + author
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.body, p.created_at, p.board_id,
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
            SELECT c.id, c.body, c.created_at,
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

    // (Optional helpers — safe to keep if other pages use them)

    public static function countByBoard(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM post WHERE board_id = :bid');
        $stmt->bindValue(':bid', $boardId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public static function findByBoard(int $boardId, int $page = 1, int $perPage = 20): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $offset  = ($page - 1) * $perPage;

        $pdo = Database::getConnection();
        $sql = "
            SELECT
                p.id, p.board_id, p.created_by, p.title, p.body,
                p.created_at, p.updated_at,
                COALESCE(up.username, ua.email) AS author
            FROM post p
            JOIN user_account ua ON ua.id = p.created_by
            LEFT JOIN user_profile up ON up.user_id = ua.id
            WHERE p.board_id = :b
            ORDER BY p.created_at DESC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':b',   $boardId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }


}
