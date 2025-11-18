<?php
class Message {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getMessages($conversationId, $afterId = 0) {
    $sql = "SELECT m.*, COALESCE(up.username, ua.email) as sender_name, 
            up.profile_picture, up.mime_type
        FROM message m 
        JOIN user_account ua ON m.sender_id = ua.id
        LEFT JOIN user_profile up ON up.user_id = ua.id
        WHERE m.conversation_id = :cid";
        if ($afterId > 0) {
            $sql .= " AND m.id > :afterId";
        }
        $sql .= " ORDER BY m.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        if ($afterId > 0) {
            $stmt->bindValue(':afterId', $afterId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendMessage($conversationId, $senderId, $recipientId, $body) {
        $stmt = $this->db->prepare("
            INSERT INTO message (conversation_id, sender_id, recipient_id, body, is_read)
            VALUES (:cid, :sid, :rid, :body, FALSE)
        ");
        $stmt->execute([
            'cid' => $conversationId,
            'sid' => $senderId,
            'rid' => $recipientId,
            'body' => $body
        ]);
        return $this->db->lastInsertId();
    }

    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM message 
                WHERE recipient_id = :uid 
                AND is_read = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getUnreadCountByConversation($userId) {
        $sql = "SELECT conversation_id, COUNT(*) as unread_count 
                FROM message 
                WHERE recipient_id = :uid 
                AND is_read = FALSE 
                GROUP BY conversation_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['conversation_id']] = (int)$row['unread_count'];
        }
        return $counts;
    }

    public function markAsRead($conversationId, $userId) {
        $sql = "UPDATE message 
                SET is_read = TRUE 
                WHERE conversation_id = :cid 
                AND recipient_id = :uid 
                AND is_read = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cid' => $conversationId,
            'uid' => $userId
        ]);
    }
}
?>
