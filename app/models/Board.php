<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class Board
{
    public static function all(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name, description FROM board ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, description, created_at, created_by FROM board WHERE id = :id');
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(string $name, string $description, ?int $userId = null): int {
        $pdo = Database::getConnection();
        if ($userId === null && session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['uid'])) {
            $userId = (int)$_SESSION['uid'];
        }
        $stmt = $pdo->prepare('INSERT INTO board (name, description, created_by) VALUES (:n, :d, :u)');
        $stmt->execute([':n'=>$name, ':d'=>$description, ':u'=>$userId]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateOwned(int $id, int $userId, string $name, string $description): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE board SET name = :n, description = :d WHERE id = :id AND created_by = :u');
        return $stmt->execute([':n'=>$name, ':d'=>$description, ':id'=>$id, ':u'=>$userId]);
    }

    public static function isOwnedBy(int $id, int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM board WHERE id = :id AND created_by = :u');
        $stmt->execute([':id'=>$id, ':u'=>$userId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function deleteOwned(int $id, int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM board WHERE id = :id AND created_by = :u');
        $stmt->execute([':id'=>$id, ':u'=>$userId]);
        return $stmt->rowCount() > 0;
    }

    public static function countAll(): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) FROM board');
        return (int)$stmt->fetchColumn();
    }

    public static function listPage(int $limit, int $offset): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT b.id, b.name, b.description,
                (SELECT COUNT(*) FROM post p WHERE p.board_id = b.id) AS post_count
            FROM board b
            ORDER BY b.id DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getCreatedBoards(int $userId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT b.id, b.name, b.description, b.created_at,
                (SELECT COUNT(*) FROM post p WHERE p.board_id = b.id) AS post_count
            FROM board b
            WHERE b.created_by = :user_id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
