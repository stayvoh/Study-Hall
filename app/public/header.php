<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$userId = $_SESSION['uid'] ?? null;
$username = 'Guest';
$profilePicUrl = '/images/default_profile.png';

if ($userId) {
    // Fetch user info
    $stmt = $pdo->prepare('SELECT username, profile_picture FROM user_profile WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $username = htmlspecialchars($user['username']);
        $profilePicUrl = 'get_image.php?id=' . $userId;
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light px-3">
  <a class="navbar-brand" href="/boards.php">Study Hall</a>
  <div class="ms-auto">
    <?php if ($userId): ?>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="<?= $profilePicUrl ?>" alt="Profile" class="rounded-circle" width="40" height="40">
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="/profile.php">View Profile</a></li>
          <li><a class="dropdown-item" href="/edit_profile.php">Edit Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
        </ul>
      </div>
    <?php else: ?>
      <a class="btn btn-primary" href="/login.php">Login</a>
    <?php endif; ?>
  </div>
</nav>
