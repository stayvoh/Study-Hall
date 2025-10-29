<?php
        class ProfileController extends BaseController {

            public function profile(): void {

            $db = (new Database())->getConnection();
            $profileModel = new Profile($db);

          
            $loggedInUserId = $_SESSION['uid'] ?? 0;

            $profileId = (int)($_GET['id'] ?? $loggedInUserId); // if no ?id= use self

            // 3. Fetch profile data
            $profile = $profileModel->getProfileByUserId($profileId);
            if (!$profile) {
                http_response_code(404);
                echo "Profile not found";
                exit;
            }
            $userPosts = Post::findByUser($profileId);


            // 4. Fetch counts
            $postCount = $profileModel->countPosts($profileId);
            $followerCount = $profileModel->countFollowers($profileId);
            $followingCount = $profileModel->countFollowing($profileId);

            // 5. Fetch boards followed by this profile
            $followedBoards = $profileModel->getFollowedBoards($profileId);

            // 6. Check if logged-in user is viewing own profile
            $isOwnProfile = $profileId === $loggedInUserId;

            // 7. Check if logged-in user is following this profile
            $isFollowing = !$isOwnProfile && $profileModel->isFollowing($loggedInUserId, $profileId);

            // 8. Profile picture URL (use get_image.php)
            $profilePicUrl = 'get_image.php?id=' . $profileId;

            // 9. Include view
            include __DIR__ . '/../views/profile.php';
        }
        
        public function followers(): void {
            if (session_status() === PHP_SESSION_NONE) session_start();

            $profileId = (int)($_GET['id'] ?? $_SESSION['uid'] ?? 0);
            if (!$profileId) {
                http_response_code(400);
                exit('Invalid user ID');
            }

            $db = (new Database())->getConnection();
            $profileModel = new Profile($db);

            $profile = $profileModel->getProfileByUserId($profileId);
            if (!$profile) {
                http_response_code(404);
                exit('Profile not found');
            }

             $search = trim($_GET['search'] ?? '');
           $followers = $profileModel->getFollowers($profileId, $search);

            include __DIR__ . '/../views/followers.php';
        }

        public function following(): void {
            if (session_status() === PHP_SESSION_NONE) session_start();

            $profileId = (int)($_GET['id'] ?? $_SESSION['uid'] ?? 0);
            if (!$profileId) {
                http_response_code(400);
                exit('Invalid user ID');
            }
            //for search support
            $search = trim($_GET['search']??'');

            $db = (new Database())->getConnection();
            $profileModel = new Profile($db);

            $profile = $profileModel->getProfileByUserId($profileId);
            if (!$profile) {
                http_response_code(404);
                exit('Profile not found');
            }
            //return the specific follower 
            $following = $profileModel->getFollowing($profileId, $search);
            
      

            include __DIR__ . '/../views/following.php';
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
        public function follow(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUserId = $_SESSION['uid'] ?? 0;
        $profileId = (int)($_POST['profile_id'] ?? 0);

        if (!$currentUserId || !$profileId) {
            http_response_code(400);
            exit('Invalid request');
        }

        $db = (new Database())->getConnection();
        $profileModel = new Profile($db);
        $profileModel->follow($currentUserId, $profileId);

        // Redirect back to profile
        header('Location: /profile?id=' . $profileId);
        exit;
       }

    public function unfollow(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUserId = $_SESSION['uid'] ?? 0;
        $profileId = (int)($_POST['profile_id'] ?? 0);

        if (!$currentUserId || !$profileId) {
            http_response_code(400);
            exit('Invalid request');
        }

        $db = (new Database())->getConnection();
        $profileModel = new Profile($db);
        $profileModel->unfollow($currentUserId, $profileId);

        // Redirect back to profile
        header('Location: /profile?id=' . $profileId);
        exit;
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
       if (empty($username) || $error = $this->checkProfanity([$username, $bio])) {
    // Determine the error message
            if (empty($username)) {
                $error = "Username cannot be empty.";
            } else {
                $error = "Your username or bio contains inappropriate language.";
            }
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
