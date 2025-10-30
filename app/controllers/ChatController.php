<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Profile.php';

use GetStream\StreamChat\Client;
use GetStream\StreamChat\JWT;

class ChatController extends BaseController {
    private Client $client;
    protected $db;
    private string $apiSecret = 'zdax5c7jzf5w7rhp7k7wxs54df3nuy8652jtq84crxvuru4awrxzfeqpaxjwskfv';

    public function __construct() {
        $this->client = new Client(
            'ucpwbv8my437', // API Key
            $this->apiSecret // API Secret
        );
        $this->db = (new Database())->getConnection();
    }

    // Returns Stream token JSON
    public function token(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['uid'] ?? null;
        $userName = $_SESSION['username'] ?? 'User';

        if (!$userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }

        // Generate JWT token
        $token = JWT::createUserToken((string)$userId, $this->apiSecret);

        header('Content-Type: application/json');
        echo json_encode([
            'token' => $token,
            'userId' => (string)$userId,
            'userName' => $userName
        ]);
    }

    // Loads chat page
    public function chatPage(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['uid'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $profileModel = new Profile($this->db);
        $currentUser = $profileModel->getProfileByUserId($userId);

        // Chat recipient
        $chatWithId = (int)($_GET['user'] ?? 0);
        $chatWithName = 'Select a user';
        if ($chatWithId) {
            $recipient = $profileModel->getProfileByUserId($chatWithId);
            if ($recipient) $chatWithName = $recipient['username'];
        }

        // Users the current user is following
        $following = $profileModel->getFollowing($userId);

        // Pass all data to view
        $this->render('chat', [
            'currentUser' => $currentUser,
            'userAvatar' => 'get_image.php?id=' . $userId,
            'chatWithId' => $chatWithId,
            'chatWithName' => $chatWithName,
            'following' => $following,
        ]);
    }
}
?>