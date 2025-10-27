<?php
declare(strict_types=1);

class Board
{

    public function __construct(private PDO $db) {}

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

    public function allWithCounts(): array
    {
        $sql = "
            SELECT 
                b.id,
                b.name,
                b.description,
                b.created_at,
                COUNT(p.id) AS post_count
            FROM board b
            LEFT JOIN post p ON p.board_id = b.id
            GROUP BY b.id, b.name, b.description, b.created_at
            ORDER BY b.created_at DESC, b.id DESC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function followersCount(int $boardId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_follow WHERE board_id = ?");
        $stmt->execute([$boardId]);
        return (int)$stmt->fetchColumn();
    }
}
