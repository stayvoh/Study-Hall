<?php
declare(strict_types=1);

class Comment
{
    public static function allByPost(int $postId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT c.id, c.body, c.created_at, ua.email AS author
             FROM comment c
             JOIN user_account ua ON ua.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC"
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public static function create(int $postId, int $userId, string $body): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO comment (post_id, user_id, body)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$postId, $userId, $body]);
    }
}
