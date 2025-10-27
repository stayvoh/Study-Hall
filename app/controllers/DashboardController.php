<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

/**
 * Controller for the Dashboard view.
 * - Shows recent posts (with authors and tags)
 * - Optionally lists boards the current user follows
 */
class DashboardController extends BaseController
{
    public function index(): void
    {
        $db     = Database::getConnection();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        // =====================================================
        // FETCH RECENT POSTS
        // =====================================================
        $sql = "
            SELECT
                p.id, p.title, p.body, p.created_at,
                COALESCE(up.username, ua.email) AS author,
                GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', t.slug) SEPARATOR '|') AS tag_blob
            FROM post p
            JOIN user_account ua ON ua.id = p.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            LEFT JOIN post_tag pt ON pt.post_id = p.id
            LEFT JOIN tag t       ON t.id = pt.tag_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        // Shape data for the view (tags, excerpt)
        $posts = array_map(function($r) {
            $tags = [];
            if (!empty($r['tag_blob'])) {
                foreach (explode('|', $r['tag_blob']) as $pair) {
                    [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
                    if ($name !== '' && $slug !== '') {
                        $tags[] = ['name' => $name, 'slug' => $slug];
                    }
                }
            }
            $r['tags']    = $tags;
            $r['excerpt'] = mb_strimwidth((string)($r['body'] ?? ''), 0, 160, '…');
            return $r;
        }, $rows);

        // =====================================================
        // FETCH FOLLOWED BOARDS (if logged in)
        // =====================================================
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $followedBoards = $userId > 0 ? Board::followedByUser($userId) : [];

        // =====================================================
        // RENDER DASHBOARD
        // =====================================================
        $this->render('dashboard', [
            'posts'          => $posts,
            'followedBoards' => $followedBoards
        ]);
    }
}
