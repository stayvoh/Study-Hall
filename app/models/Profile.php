<?php
class Profile {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create a new user profile
    public function create(int $userId, string $username, ?string $pictureData, ?string $mimeType, ?string $bio): bool {
        $stmt = $this->db->prepare("
            INSERT INTO user_profile (user_id, username, profile_picture, mime_type, bio)
            VALUES (:uid, :username, :profile_picture, :mime_type, :bio)
        ");
        return $stmt->execute([
            'uid' => $userId,
            'username' => $username,
            'bio' => $bio,
            'mime_type' => $mimeType,
            'profile_picture' => $pictureData
        ]);
    }

    // Fetch a user profile by ID
    public function getProfileByUserId(int $userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_profile WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Boards this user is following
    public function getFollowedBoards(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                b.id, b.name, b.description,
                COUNT(p.id) AS post_count
            FROM board_follow bf
            JOIN board b ON bf.board_id = b.id
            LEFT JOIN post p ON b.id = p.board_id
            WHERE bf.user_id = :uid
            GROUP BY b.id, b.name, b.description
            ORDER BY b.created_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if current user is following another user
    public function isFollowing(int $currentUserId, int $profileUserId): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM user_follow WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$currentUserId, $profileUserId]);
        return (bool)$stmt->fetchColumn();
    }

    public function follow(int $currentUserId, int $profileUserId): bool {
        $stmt = $this->db->prepare('INSERT IGNORE INTO user_follow (follower_id, following_id) VALUES (?, ?)');
        return $stmt->execute([$currentUserId, $profileUserId]);
    }

    public function unfollow(int $currentUserId, int $profileUserId): bool {
        $stmt = $this->db->prepare('DELETE FROM user_follow WHERE follower_id = ? AND following_id = ?');
        return $stmt->execute([$currentUserId, $profileUserId]);
    }

    public function countFollowers(int $userId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_follow WHERE following_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function countFollowing(int $userId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_follow WHERE follower_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // New: get posts by a user
    public function countPosts(int $userId): int {
    $stmt = $this->db->prepare('SELECT COUNT(*) FROM post WHERE created_by = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
    }

    public function getPostsByUser(int $userId): array {
        $stmt = $this->db->prepare('SELECT * FROM post WHERE created_by = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getFollowers(int $userId): array {
    $stmt = $this->db->prepare("
        SELECT uf.follower_id AS user_id, up.username, up.profile_picture, up.mime_type
        FROM user_follow uf
        JOIN user_profile up ON uf.follower_id = up.user_id
        WHERE uf.following_id = :uid
        ORDER BY uf.created_at DESC
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFollowing(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT uf.following_id AS user_id, up.username, up.profile_picture, up.mime_type
            FROM user_follow uf
            JOIN user_profile up ON uf.following_id = up.user_id
            WHERE uf.follower_id = :uid
            ORDER BY uf.created_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
?>
