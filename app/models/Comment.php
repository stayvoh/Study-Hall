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
        $sel = $pdo->prepare('SELECT post_id FROM comment WHERE id = :id AND created_by = :uid');
        $sel->execute([':id'=>$commentId, ':uid'=>$userId]);
        $postId = $sel->fetchColumn();
        if (!$postId) return null;

        $del = $pdo->prepare('DELETE FROM comment WHERE id = :id AND created_by = :uid');
        $del->execute([':id'=>$commentId, ':uid'=>$userId]);
        return $del->rowCount() > 0 ? (int)$postId : null;
    }

    public static function deleteByBoardOwner(int $commentId, int $ownerUserId): bool {
        $pdo = Database::getConnection();
        $chk = $pdo->prepare('
            SELECT 1
            FROM comment c
            JOIN post p ON p.id = c.post_id
            JOIN board b ON b.id = p.board_id
            WHERE c.id = :cid AND b.created_by = :uid
            LIMIT 1
        ');
        $chk->execute([':cid'=>$commentId, ':uid'=>$ownerUserId]);
        if (!$chk->fetchColumn()) return false;

        $del = $pdo->prepare('DELETE FROM comment WHERE id = :cid');
        $del->execute([':cid'=>$commentId]);
        return $del->rowCount() > 0;
    }

}
