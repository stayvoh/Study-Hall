<?php
declare(strict_types=1);

class Board
{
    // =====================================================
    // FETCH METHODS
    // =====================================================

    /**
     * Get all boards (ordered by name).
     */
    public static function all(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, name, description, created_at
             FROM board
             ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    /**
     * Find a single board by its ID.
     */
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, name, description
             FROM board
             WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get all boards followed by a specific user.
     * Requires user_follow_board table.
     */
    public static function followedByUser(int $uid): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT b.id, b.name, b.description
             FROM user_follow_board ufb
             JOIN board b ON b.id = ufb.board_id
             WHERE ufb.user_id = ?
             ORDER BY b.name'
        );
        $stmt->execute([$uid]);
        return $stmt->fetchAll() ?: [];
    }

    // =====================================================
    // CREATE METHODS
    // =====================================================

    /**
     * Create a new board.
     * Currently sets course_id to NULL.
     */
    public static function create(string $name, ?string $desc): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO board (course_id, name, description)
             VALUES (NULL, ?, ?)'
        );
        $stmt->execute([$name, $desc ?: null]);
    }

    // =====================================================
    // FOLLOW METHODS
    // =====================================================

    /**
     * Follow a board for a user.
     */
    public static function follow(int $uid, int $boardId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO user_follow_board (user_id, board_id)
             VALUES (?, ?)'
        );
        $stmt->execute([$uid, $boardId]);
    }

    /**
     * Unfollow a board for a user.
     */
    public static function unfollow(int $uid, int $boardId): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM user_follow_board
             WHERE user_id = ? AND board_id = ?'
        );
        $stmt->execute([$uid, $boardId]);
    }
}
