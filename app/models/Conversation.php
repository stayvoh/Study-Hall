<?php
class Conversation {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Get all conversations for a user
    public function getUserConversations(int $userId): array {
        $stmt = $this->db->prepare("
                SELECT DISTINCT 
                       c.id,
                       CASE WHEN c.user_one_id = :uid THEN c.user_two_id ELSE c.user_one_id END AS partner_id,
                       CASE WHEN c.user_one_id = :uid THEN COALESCE(u2.username, ua2.email) ELSE COALESCE(u1.username, ua1.email) END AS partner_name,
                       CASE WHEN c.user_one_id = :uid THEN u2.profile_picture ELSE u1.profile_picture END AS profile_picture,
                       CASE WHEN c.user_one_id = :uid THEN u2.mime_type ELSE u1.mime_type END AS mime_type,
                       c.created_at
                FROM conversation c
                LEFT JOIN user_profile u1 ON u1.user_id = c.user_one_id
                LEFT JOIN user_profile u2 ON u2.user_id = c.user_two_id
                LEFT JOIN user_account ua1 ON ua1.id = c.user_one_id
                LEFT JOIN user_account ua2 ON ua2.id = c.user_two_id
                WHERE (c.user_one_id = :uid OR c.user_two_id = :uid)
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get conversation with full partner info
    public function getConversationById(int $id): ?array {
        $stmt = $this->db->prepare("
                SELECT c.*,
                       COALESCE(u1.username, ua1.email) AS user_one_name, u1.profile_picture AS user_one_pic, u1.mime_type AS user_one_mime,
                       COALESCE(u2.username, ua2.email) AS user_two_name, u2.profile_picture AS user_two_pic, u2.mime_type AS user_two_mime
                FROM conversation c
                LEFT JOIN user_profile u1 ON u1.user_id = c.user_one_id
                LEFT JOIN user_profile u2 ON u2.user_id = c.user_two_id
                LEFT JOIN user_account ua1 ON ua1.id = c.user_one_id
                LEFT JOIN user_account ua2 ON ua2.id = c.user_two_id
                WHERE c.id = :id
            ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Get or create conversation between two users
    public function getOrCreate(int $userId, int $partnerId): array {
        // Check existing conversation
        $stmt = $this->db->prepare("
            SELECT id FROM conversation
            WHERE (user_one_id = :u1 AND user_two_id = :u2)
               OR (user_one_id = :u2 AND user_two_id = :u1)
            LIMIT 1
        ");
        $stmt->execute(['u1' => $userId, 'u2' => $partnerId]);
        $convId = $stmt->fetchColumn();

        if (!$convId) {
            $stmt = $this->db->prepare("
                INSERT INTO conversation (user_one_id, user_two_id, created_at)
                VALUES (:u1, :u2, NOW())
            ");
            $stmt->execute(['u1' => $userId, 'u2' => $partnerId]);
            $convId = (int)$this->db->lastInsertId();
        }

        return $this->getConversationById($convId);
    }
}
