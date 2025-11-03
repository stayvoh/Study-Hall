<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

        $db   = Database::getConnection();
        $tab  = (string)($_GET['tab'] ?? 'all');
        $uid  = (int)($_SESSION['uid'] ?? 0);
        $bq   = trim((string)($_GET['bq'] ?? '')); // keep existing board search filter
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 8; // tweak if desired

        // FOLLOWING TAB (paginate in PHP to avoid changing the data layer)
        if ($tab === 'following' && $uid > 0) {
            $all = BoardFollow::boardsForUser($uid) ?? [];
            // optional filter by $bq to match "all" tab behavior
            if ($bq !== '') {
                $q = mb_strtolower($bq);
                $all = array_values(array_filter($all, static function ($r) use ($q): bool {
                    $name = mb_strtolower((string)($r['name'] ?? ''));
                    $desc = mb_strtolower((string)($r['description'] ?? ''));
                    return (str_contains($name, $q) || str_contains($desc, $q));
                }));
            }
            $totalBoards = count($all);
            $lastPage = max(1, (int)ceil($totalBoards / $perPage));
            if ($page > $lastPage) $page = $lastPage;
            $offset = ($page - 1) * $perPage;
            $boards = array_slice($all, $offset, $perPage);

            $this->render('dashboard', [
                'boards'     => $boards,
                'bq'         => $bq,
                'tab'        => 'following',
                'pagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $totalBoards,
                    'lastPage' => $lastPage,
                ],
            ]);
            return;
        }

        // ALL TAB (server-side pagination)
        $where = '';
        if ($bq !== '') $where = "WHERE b.name LIKE :q OR b.description LIKE :q";

        // total count for pagination
        $countSql = "SELECT COUNT(*) FROM board b $where";
        $countStmt = $db->prepare($countSql);
        if ($bq !== '') $countStmt->bindValue(':q', "%$bq%", PDO::PARAM_STR);
        $countStmt->execute();
        $totalBoards = (int)$countStmt->fetchColumn();

        $lastPage = max(1, (int)ceil($totalBoards / $perPage));
        if ($page > $lastPage) $page = $lastPage;
        $offset = ($page - 1) * $perPage;

        // page of boards + post counts
        $sql  = "
            SELECT b.id, b.name, b.description, b.created_at, COUNT(p.id) AS post_count
            FROM board b
            LEFT JOIN post p ON p.board_id = b.id
            $where
            GROUP BY b.id, b.name, b.description, b.created_at
            ORDER BY b.created_at DESC, b.id DESC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $db->prepare($sql);
        if ($bq !== '') $stmt->bindValue(':q', "%$bq%", PDO::PARAM_STR);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $boards = $stmt->fetchAll() ?: [];

        $this->render('dashboard', [
            'boards'     => $boards,
            'bq'         => $bq,
            'tab'        => 'all',
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalBoards,
                'lastPage' => $lastPage,
            ],
        ]);
    }
}
