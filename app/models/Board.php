<?php
declare(strict_types=1);

class Board
{
    public static function all(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, name, description, created_at 
             FROM board 
             ORDER BY name'
        );
        return $stmt->fetchAll();
    }

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

    public static function create(string $name, ?string $desc): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO board (course_id, name, description) 
             VALUES (NULL, ?, ?)'
        );
        $stmt->execute([$name, $desc ?: null]);
    }
}
