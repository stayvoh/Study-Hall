<?php
declare(strict_types=1);

class Post
{
    public static function findByBoard(int $boardId, int $limit, int $offset): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT p.id, p.title, LEFT(p.body, 180) AS preview, p.created_at, ua.email AS author
             FROM post p
             JOIN user_account ua ON ua.id = p.user_id
             WHERE p.board_id = ?
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$boardId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public static function countByBoard(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS c 
             FROM post 
             WHERE board_id = ?"
        );
        $stmt->execute([$boardId]);
        return (int)$stmt->fetch()['c'];
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT p.id, p.title, p.body, p.created_at, p.board_id, ua.email AS author
             FROM post p
             JOIN user_account ua ON ua.id = p.user_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $boardId, int $userId, string $title, string $body): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO post (board_id, user_id, title, body, is_question)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$boardId, $userId, $title, $body]);
        return (int)$pdo->lastInsertId();
    }
}
