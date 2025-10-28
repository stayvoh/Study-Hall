<?php
class ProfileController extends BaseController {

    public function profile(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['uid'])) {
            header('Location: /login');
            exit;
        }

        $currentUserId = $_SESSION['uid'];

    // Get the profile to view: either own or someone else's
             $profileId = (int)($_GET['id'] ?? $currentUserId);

            // Load profile
            $profileModel = new Profile($this->db);
            $currentUser = $profileModel->getProfileByUserId($profileId);
            $followedBoards = $profileModel->getFollowedBoards($profileId);
            $profilePicUrl = '/profile/avatar/' . $profileId;

            // Only show follow/unfollow if viewing another user's profile
            $isFollowing = false;
            if ($profileId !== $currentUserId) {
                $isFollowing = $profileModel->isFollowing($currentUserId, $profileId);
            }

            $followerCount = $profileModel->countFollowers($profileId);
            $followingCount = $profileModel->countFollowing($profileId);

            // Render profile view
            $this->render('profile', [
                'currentUser' => $currentUser,
                'profilePicUrl' => $profilePicUrl,
                'followedBoards' => $followedBoards,
                'isFollowing' => $isFollowing,
                'followerCount' => $followerCount,
                'followingCount' => $followingCount,
                'isOwnProfile' => $profileId === $currentUserId
            ]);
    }

    /**
     * Serve the profile avatar image
     */
    public function avatar($userId = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $userId ?? $_SESSION['uid'] ?? 0;

        if (!$userId) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $stmt = $this->db->prepare("SELECT profile_picture, mime_type FROM user_profile WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch();

        if (!$profile || !$profile['profile_picture']) {
            header('Content-Type: image/png');
            readfile('/public/images/default-avatar.jpg');
            exit;
        }

        header('Content-Type: ' . $profile['mime_type']);
        echo $profile['profile_picture'];
    }

    /**
     * Show the edit profile form
     */
    public function edit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $stmt = $this->db->prepare("SELECT username, bio FROM user_profile WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            http_response_code(404);
            exit('Profile not found');
        }

        $profilePicUrl = '/get_image.php?id=' . $_SESSION['uid'];


        $this->render('EditProfile', [
            'currentUser' => $profile,
            'profilePicUrl' => $profilePicUrl
        ]);
    }
    

    /**
     * Handle updating the profile
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $username = trim($_POST['username'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        // Validate username
        if (empty($username)) {
            $error = "Username cannot be empty.";

            // Fetch current profile for re-render
            $stmt = $this->db->prepare("SELECT username, bio FROM user_profile WHERE user_id = :id");
            $stmt->execute(['id' => $userId]);
            $profile = $stmt->fetch();

           $profilePicUrl = '/get_image.php?id=' . $userId;


            $this->render('EditProfile', [
                'currentUser' => $profile,
                'profilePicUrl' => $profilePicUrl,
                'error' => $error
            ]);
            return;
        }

        // Handle profile picture upload
        $profilePicture = null;
        $mimeType = null;
        if (!empty($_FILES['profile_picture']['tmp_name'])) {
            $file = $_FILES['profile_picture'];
            $profilePicture = file_get_contents($file['tmp_name']);
            $mimeType = mime_content_type($file['tmp_name']);
        }

        // Update database
        $sql = "UPDATE user_profile SET username = :username, bio = :bio";
        if ($profilePicture) {
            $sql .= ", profile_picture = :profile_picture, mime_type = :mime_type";
        }
        $sql .= " WHERE user_id = :id";

        $stmt = $this->db->prepare($sql);
        $params = [
            'username' => $username,
            'bio' => $bio,
            'id' => $userId
        ];
        if ($profilePicture) {
            $params['profile_picture'] = $profilePicture;
            $params['mime_type'] = $mimeType;
        }

        $stmt->execute($params);

        // Redirect to profile page after update
        header('Location: /profile');
        exit;
    }
}
?>
