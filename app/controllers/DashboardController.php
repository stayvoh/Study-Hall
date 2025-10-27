<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class DashboardController extends BaseController
{
    public function index(): void
{
    $db   = Database::getConnection();
    $bq   = trim((string)($_GET['bq'] ?? ''));
    $sql  = "
        SELECT b.id, b.name, b.description, b.created_at, COUNT(p.id) AS post_count
        FROM board b
        LEFT JOIN post p ON p.board_id = b.id
    ";
    $where = '';
    if ($bq !== '') {
        $where = "WHERE b.name LIKE :q OR b.description LIKE :q";
    }
    $sql .= " $where GROUP BY b.id, b.name, b.description, b.created_at
              ORDER BY b.created_at DESC, b.id DESC";

    $stmt = $db->prepare($sql);
    if ($bq !== '') $stmt->bindValue(':q', "%$bq%", PDO::PARAM_STR);
    $stmt->execute();
    $boards = $stmt->fetchAll() ?: [];

    // No posts on the dashboard anymore
    $this->render('dashboard', [
        'boards' => $boards,
        'bq'     => $bq,
    ]);
}
}
