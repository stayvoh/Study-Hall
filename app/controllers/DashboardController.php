<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        $uid = (int)($_SESSION['uid'] ?? 0);
        $tab = ($_GET['tab'] ?? 'all');
        $bq  = trim((string)($_GET['bq'] ?? ''));

        // Always fetch followed boards for sidebar
        $followedBoards = $uid > 0 ? BoardFollow::boardsForUser($uid) : [];

        // Fetch boards for main dashboard content
        $db  = Database::getConnection();
        $sql = "
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
        $allBoards = $stmt->fetchAll() ?: [];

        // Render with both sets
        $this->render('dashboard', [
            'boards'         => $allBoards,      // for main dashboard listing
            'followedBoards' => $followedBoards, // for sidebar
            'bq'             => $bq,
            'tab'            => $tab,
        ]);
    }
}
