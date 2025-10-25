<?php
// Make sure $currentUser is available, or fetch it if not
if (!isset($currentUser) && isset($_SESSION['uid'])) {
    $profileModel = new Profile($this->db);
    $currentUser = $profileModel->getProfileByUserId($_SESSION['uid']);
    $profilePicUrl = 'get_image.php?id=' . $_SESSION['uid'];
}
?>
<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">StudyHall</a>

    <div class="d-flex align-items-center">
      <div class="dropdown">
        <img 
          src="<?= $profilePicUrl ?>" 
          alt="Profile Picture" 
          class="rounded-circle dropdown-toggle" 
          id="profileDropdown" 
          data-bs-toggle="dropdown" 
          style="width:40px; height:40px; cursor:pointer;"
        >
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li class="px-3 py-2">
            <strong><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars($currentUser['email'] ?? '') ?></small>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/profile">Profile</a></li>
          <li><a class="dropdown-item" href="/settings">Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/logout">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
