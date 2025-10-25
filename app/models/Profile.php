<?php

class Profile{
private $db;
  public function __construct($db) {
        $this->db = $db;
    }
    
    public function create(
        int $userId,
        string $username,
        ?string $pictureData,
        ?string $mimeType,
        ?string $bio
        ): bool {
         $stmt = $this->db->prepare("
            INSERT INTO user_profile (user_id, username, profile_picture, mime_type, bio)
            VALUES (:uid, :username,:profile_picture, :mime_type, :bio)
        ");
        return $stmt->execute([
            'uid' => $userId,
            'username' => $username,
            'bio' => $bio,
            'mime_type' => $mimeType,
            'profile_picture' => $pictureData
        ]);

        }

        public function getProfileByUserId(int $userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_profile WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
    }
}
?>