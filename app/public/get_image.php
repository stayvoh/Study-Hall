<?php
declare(strict_types=1);
require __DIR__ . '/../core/Database.php';

$db = (new Database())->getConnection();
// Make sure an ID is provided
$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo 'Invalid user ID';
    exit;
}

// Fetch profile picture and MIME type
$stmt = $db->prepare('SELECT profile_picture, mime_type FROM user_profile WHERE user_id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile || !$profile['profile_picture']) {
    // No image uploaded, serve default
    header('Content-Type: image/png');
    readfile(__DIR__ . '/images/default_profile.png');
    exit;
}

// Serve the image from DB
header('Content-Type: ' . $profile['mime_type']);
header('Content-Length: ' . strlen($profile['profile_picture']));
echo $profile['profile_picture'];
exit;
?>