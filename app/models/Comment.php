<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class Comment
{
    public static function allByPost(int $postId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT c.id, c.body, c.created_at, c.created_by,
                    COALESCE(up.username, ua.email) AS author
             FROM comment c
             JOIN user_account ua ON ua.id = c.created_by
             LEFT JOIN user_profile up ON up.user_id = ua.id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC"
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(int $postId, int $userId, string $body): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO comment (post_id, created_by, body)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$postId, $userId, $body]);
    }

    public static function deleteOwned(int $commentId, int $userId): ?int {
        $pdo = Database::getConnection();

        $sel = $pdo->prepare('
            SELECT c.post_id, c.created_by AS comment_author, b.created_by AS board_owner
            FROM comment c
            JOIN post p ON p.id = c.post_id
            JOIN board b ON b.id = p.board_id
            WHERE c.id = :id
            LIMIT 1
        ');
        $sel->execute([':id' => $commentId]);
        $row = $sel->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;

        $postId = (int)$row['post_id'];
        $isAuthor = ((int)$row['comment_author'] === $userId);
        $isOwner  = ((int)$row['board_owner']  === $userId);
        if (!$isAuthor && !$isOwner) return null;

        $del = $pdo->prepare('DELETE FROM comment WHERE id = :id');
        $del->execute([':id' => $commentId]);
        return $del->rowCount() > 0 ? $postId : null;
    }

    public static function deleteByBoardOwner(int $commentId, int $ownerUserId): bool {
        return self::deleteOwned($commentId, $ownerUserId) !== null;
    }
}