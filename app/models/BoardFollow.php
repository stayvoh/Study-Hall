<?php
declare(strict_types=1);

class BoardFollow
{
    public static function follow(int $userId, int $boardId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO board_follow (user_id, board_id) VALUES (?, ?)"
        );
        return $stmt->execute([$userId, $boardId]);
    }

    public static function unfollow(int $userId, int $boardId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "DELETE FROM board_follow WHERE user_id = ? AND board_id = ?"
        );
        return $stmt->execute([$userId, $boardId]);
    }

    public static function isFollowing(int $userId, int $boardId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT 1 FROM board_follow WHERE user_id = ? AND board_id = ? LIMIT 1"
        );
        $stmt->execute([$userId, $boardId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function followersCount(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM board_follow WHERE board_id = ?"
        );
        $stmt->execute([$boardId]);
        return (int)$stmt->fetchColumn();
    }

    /** Boards the user is following (with post counts for the dashboard) */
    public static function boardsForUser(int $userId): array {
        $pdo = Database::getConnection();
        $sql = "
          SELECT b.id, b.name, b.description, b.created_at,
                 COUNT(p.id) AS post_count
          FROM board_follow bf
          JOIN board b ON b.id = bf.board_id
          LEFT JOIN post p ON p.board_id = b.id
          WHERE bf.user_id = ?
          GROUP BY b.id, b.name, b.description, b.created_at
          ORDER BY b.created_at DESC, b.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
