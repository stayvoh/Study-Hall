<?php
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Conversation.php';

class MessageController extends BaseController {
    // AJAX endpoint to get or create conversation
    public function getOrCreate() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) exit(json_encode(['success' => false]));

        $partnerId = (int)($_GET['partner_id'] ?? 0);
        if (!$partnerId) exit(json_encode(['success' => false]));

    // release session lock early so other requests (send/poll) aren't blocked
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

    $db = Database::getConnection();
        $conversationModel = new Conversation($db);

        $conv = $conversationModel->getOrCreate($userId, $partnerId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'conversation_id' => $conv['id']]);
        exit;
    }

    // Show messages page
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $loggedInUserId = $_SESSION['uid'] ?? 0;
        if (!$loggedInUserId) exit('Not logged in');

        $db = Database::getConnection();
        $conversationModel = new Conversation($db);
        $msgModel = new Message($db);

        $messages = [];
        $activeConversation = null;

        // Get all user conversations and map them by partner_id
        $conversations = [];
        $convMap = [];
        $activeConvs = $conversationModel->getUserConversations($loggedInUserId);
        foreach ($activeConvs as $conv) {
            $pid = (int)$conv['partner_id'];
            $convMap[$pid] = $conv;
        }

        // Get unread message counts
        $msgModel = new Message($db);
        $unreadCounts = $msgModel->getUnreadCountByConversation($loggedInUserId);
        
        // Mark messages as read if viewing a conversation
        if ($activeId = (int)($_GET['conversation'] ?? 0)) {
            $msgModel->markAsRead($activeId, $loggedInUserId);
        }

        // Get users the current user is following
        require_once __DIR__ . '/../models/Profile.php';
        $profileModel = new Profile($db);
        $following = $profileModel->getFollowing($loggedInUserId);

        // Process each followed user
        foreach ($following as $f) {
            $partnerId = (int)$f['user_id'];
            if (isset($convMap[$partnerId])) {
                // User has an active conversation
                $conv = $convMap[$partnerId];
                $conv['unread_count'] = $unreadCounts[$conv['id']] ?? 0;
                $conversations[] = $conv;
            } else {
                // No active conversation, add as potential chat
                $conversations[] = [
                    'id' => 0,
                    'partner_id' => $partnerId,
                    'partner_name' => $f['username'],
                    'profile_picture' => $f['profile_picture'] ?? null,
                    'mime_type' => $f['mime_type'] ?? null,
                    'created_at' => null,
                    'unread_count' => 0
                ];
            }
        }

        // Sort conversations: active conversations by date first, then potential ones alphabetically
        usort($conversations, function($a, $b) {
            $aHasId = !empty($a['id']);
            $bHasId = !empty($b['id']);
            
            // Put active conversations first
            if ($aHasId !== $bHasId) {
                return $aHasId ? -1 : 1;
            }
            
            if ($aHasId) {
                // Both are active conversations, sort by date
                $aTime = strtotime($a['created_at'] ?? '0');
                $bTime = strtotime($b['created_at'] ?? '0');
                return $bTime - $aTime;
            } else {
                // Both are potential conversations, sort by name
                return strcasecmp($a['partner_name'], $b['partner_name']);
            }
        });

        // Determine which conversation to load
        $activeId   = (int)($_GET['conversation'] ?? 0);
        $startUserId = (int)($_GET['user_id'] ?? 0);

        // If user clicked a partner, get or create conversation
        if ($startUserId && !$activeId) {
            $conv = $conversationModel->getOrCreate($loggedInUserId, $startUserId);
            $activeId = $conv['id'];

            // Redirect to conversation URL so refresh keeps messages
            header("Location: /messages?conversation=$activeId");
            exit;
        }

        // Load conversation and messages if available
        if ($activeId) {
            $conv = $conversationModel->getConversationById($activeId);
            if ($conv) {
                if ($conv['user_one_id'] == $loggedInUserId) {
                    $partnerName = $conv['user_two_name'];
                    $partnerPic  = $conv['user_two_pic'];
                    $partnerMime = $conv['user_two_mime'];
                    $partnerId   = $conv['user_two_id'];
                } else {
                    $partnerName = $conv['user_one_name'];
                    $partnerPic  = $conv['user_one_pic'];
                    $partnerMime = $conv['user_one_mime'];
                    $partnerId   = $conv['user_one_id'];
                }

                $activeConversation = [
                    'id' => $conv['id'],
                    'partner_id' => $partnerId,
                    'partner_name' => $partnerName,
                    'profile_picture' => $partnerPic,
                    'mime_type' => $partnerMime
                ];

                $messages = $msgModel->getMessages($activeId, 0);
            }
        }

        require __DIR__ . '/../views/messages.php';
    }

    // Send a message
    public function send() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) {
            header('Content-Type: application/json');
            error_log("[messages][send] not logged in\n", 3, __DIR__ . '/../logs/messages.log');
            exit(json_encode(['success' => false, 'error' => 'Not logged in']));
        }

        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            header('Content-Type: application/json');
            // log occurrence for debugging
            error_log("[messages][send] Empty message from user {$userId} conversation={$conversationId}\n", 3, __DIR__ . '/../logs/messages.log');
            exit(json_encode(['success' => false, 'error' => 'Empty message']));
        }

    // release session lock before doing DB writes so long-polling doesn't block this request
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

    $db = Database::getConnection();
        $conversationModel = new Conversation($db);
        $msgModel = new Message($db);

        // Create conversation if it doesn't exist
        if (!$conversationId && isset($_POST['partner_id'])) {
            $partnerId = (int)$_POST['partner_id'];
            $conv = $conversationModel->getOrCreate($userId, $partnerId);
            $conversationId = $conv['id'];
        }

        // Get recipient_id from conversation
        $conv = $conversationModel->getConversationById($conversationId);
        if (!$conv) {
            error_log("[messages][send] Conversation not found: {$conversationId}\n", 3, __DIR__ . '/../logs/messages.log');
            echo json_encode(['success' => false, 'error' => 'Conversation not found']);
            exit;
        }

        $recipientId = ($conv['user_one_id'] == $userId) ? $conv['user_two_id'] : $conv['user_one_id'];
        try {
            $msgId = $msgModel->sendMessage($conversationId, $userId, $recipientId, $body);
            
            if (!$msgId) {
                error_log("[messages][send] Failed to insert message: conv={$conversationId} sender={$userId} recipient={$recipientId}\n", 3, __DIR__ . '/../logs/messages.log');
                echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                exit;
            }

            // fetch created_at for the inserted message so client can display consistent time
            $stmt = $db->prepare('SELECT created_at FROM message WHERE id = :id');
            $stmt->execute(['id' => $msgId]);
            $createdAt = $stmt->fetchColumn();
            $createdIso = null;
            if ($createdAt) {
                try {
                    $dt = new DateTime($createdAt);
                    $createdIso = $dt->format(DateTime::ATOM);
                } catch (Exception $e) {
                    $createdIso = $createdAt;
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $msgId,
                'conversation_id' => $conversationId,
                'created_at' => $createdIso
            ]);
        } catch (PDOException $e) {
            error_log("[messages][send] Database error: " . $e->getMessage() . "\n", 3, __DIR__ . '/../logs/messages.log');
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
            exit;
        } catch (Exception $e) {
            error_log("[messages][send] Error: " . $e->getMessage() . "\n", 3, __DIR__ . '/../logs/messages.log');
            echo json_encode(['success' => false, 'error' => 'An error occurred']);
            exit;
        }
        exit;
    }

    // Polling for new messages
    public function poll() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) exit;

        // We're about to hold the request open for up to ~25s. Close the session to avoid blocking other requests.
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

        $conversationId = (int)($_GET['conversation_id'] ?? 0);
        $lastMessageId = (int)($_GET['last_id'] ?? 0);
        if (!$conversationId) exit(json_encode([]));

        $msgModel = new Message(Database::getConnection());

        $start = time();
        while (time() - $start < 25) {
            $newMessages = $msgModel->getMessages($conversationId, $lastMessageId);
            if (!empty($newMessages)) {
                // Normalize created_at to ISO8601 so clients always receive timestamps with timezone info
                foreach ($newMessages as &$m) {
                    if (!empty($m['created_at'])) {
                        try {
                            $dt = new DateTime($m['created_at']);
                            $m['created_at'] = $dt->format(DateTime::ATOM);
                        } catch (Exception $e) {
                            // leave as-is
                        }
                    }
                }
                unset($m);
                echo json_encode($newMessages);
                flush();
                exit;
            }
            usleep(500000); // 0.5 seconds
        }

        echo json_encode([]);
    }

    // Get unread message count for notifications
    public function unreadCount() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) {
            header('Content-Type: application/json');
            exit(json_encode(['unreadCount' => 0]));
        }

        $msgModel = new Message(Database::getConnection());
        $count = $msgModel->getUnreadCount($userId);

        header('Content-Type: application/json');
        echo json_encode(['unreadCount' => $count]);
    }

    // Get updated conversation list with unread counts
    public function getConversations() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['uid'] ?? 0;
        if (!$userId) {
            header('Content-Type: application/json');
            exit(json_encode(['conversations' => []]));
        }

        $db = Database::getConnection();
        $conversationModel = new Conversation($db);
        $msgModel = new Message($db);

        // Get conversations with unread counts
        $conversations = $conversationModel->getUserConversations($userId);
        $unreadCounts = $msgModel->getUnreadCountByConversation($userId);

        // Add unread counts to conversations
        foreach ($conversations as &$conv) {
            $conv['unread_count'] = $unreadCounts[$conv['id']] ?? 0;
        }

        header('Content-Type: application/json');
        echo json_encode(['conversations' => $conversations]);
    }
}
