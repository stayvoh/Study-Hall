<?php
declare(strict_types=1);

class ProfileController extends BaseController
{
    public function avatar($userId = null): void
    {
        // If no ID specified, use logged-in user
        $userId = $userId ?? $_SESSION['uid'] ?? 0;

        if (!$userId) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $stmt = $this->db->prepare(
            "SELECT profile_picture, mime_type FROM user_profile WHERE user_id = :id"
        );
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch();

        if (!$profile || !$profile['profile_picture']) {
            header('Content-Type: image/png');
            readfile('images/default-avatar.png');
            exit;
        }

        header('Content-Type: ' . $profile['mime_type']);
        echo $profile['profile_picture'];
    }
}
