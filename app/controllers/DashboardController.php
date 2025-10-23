<?php
class DashboardController extends BaseController
{
    public function index()
    {
        require_login();

        $feed = $_GET['feed'] ?? 'all';
        $userId = $_SESSION['uid'];

        if ($feed === 'following') {
            // TODO: adjust this when you have a "user_follow_board" table
            // For now, just placeholder query to simulate "following"
            $stmt = $this->db->prepare("
                SELECT p.id, p.title, p.body, p.created_at,
                COALESCE(up.username, u.email) AS author
                FROM post p
                JOIN user_account u ON u.id = p.user_id
                LEFT JOIN user_profile up ON up.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $activities = $stmt->fetchAll();
        } else {
            // Global feed: recent posts from all boards
            $stmt = $this->db->prepare("
                SELECT p.id, p.title, p.body, p.created_at,
                COALESCE(up.username, u.email) AS author
                FROM post p
                JOIN user_account u ON u.id = p.user_id
                LEFT JOIN user_profile up ON up.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $activities = $stmt->fetchAll();
        }

        $this->render('dashboard', [
            'feed' => $feed,
            'activities' => $activities,
        ]);
    }
}
?>
