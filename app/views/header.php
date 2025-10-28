<?php
// Ensure $currentUser and profile picture are loaded
if (!isset($currentUser) && isset($_SESSION['uid'])) {
    $profileModel = new Profile($this->db);
    $currentUser = $profileModel->getProfileByUserId($_SESSION['uid']);
    $profilePicUrl = 'get_image.php?id=' . $_SESSION['uid'];
}

// Detect current page
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Pages where header elements (except theme toggle) should NOT appear
$excludeHeader = [
    'profile/edit',
    'login',
    'register',
    'forgot',
    'reset',
];
?>

<nav class="navbar navbar-dark bg-dark position-relative">
  <div class="container-fluid">

    <!-- Left cluster -->
    <div class="d-flex align-items-center">
      <!-- Sidebar toggle -->
      <button class="btn btn-outline-light btn-sm me-2" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
        <i class="bi bi-list"></i>
      </button>

      <!-- Theme toggle -->
      <button id="themeToggle" class="btn btn-outline-light btn-sm" title="Toggle Theme">
        <i id="themeIcon" class="bi bi-moon-stars"></i>
      </button>
    </div>

    <?php if (!in_array($currentPath, $excludeHeader, true)): ?>
      <!-- Title always dead center -->
      <a class="navbar-brand position-absolute top-50 start-50 translate-middle" href="/dashboard">
        Study Hall
      </a>

      <!-- Right cluster -->
      <div class="d-flex align-items-center ms-auto">
        <?php if (strpos($currentPath, 'profile') !== 0): ?>
          <div class="dropdown">
            <img 
              src="<?= htmlspecialchars($profilePicUrl ?? '/public/images/default-avatar.jpg') ?>" 
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
        <?php else: ?>
          <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarLabel">Followed Boards</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <?php if (!empty($followedBoards)): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($followedBoards as $board): ?>
          <li class="list-group-item bg-dark text-white">
            <a href="/board?id=<?= urlencode($board['id']) ?>" class="text-white text-decoration-none">
              <?= htmlspecialchars($board['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted px-3">You are not following any boards yet.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Theme toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.documentElement;
  const btn  = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');

  function setTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);
    document.cookie = "theme=" + theme + "; path=/; max-age=31536000";
    if (icon) {
      icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
  }

  if (!btn) return;

  // Initialize theme
  const cookieTheme = document.cookie.match(/(?:^|;\s*)theme=(light|dark)/)?.[1];
  const storedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initialTheme = cookieTheme || storedTheme || (prefersDark ? 'dark' : 'light');
  setTheme(initialTheme);

  // Button click
  btn.addEventListener('click', () => {
    const nextTheme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    setTheme(nextTheme);
  });

  // Watch system changes if no stored choice
  if (!storedTheme && window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
      setTheme(e.matches ? 'dark' : 'light');
    });
  }
});
</script>
