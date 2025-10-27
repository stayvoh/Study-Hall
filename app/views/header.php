<?php
// Make sure $currentUser is available, or fetch it if not
if (!isset($currentUser) && isset($_SESSION['uid'])) {
    $profileModel = new Profile($this->db);
    $currentUser = $profileModel->getProfileByUserId($_SESSION['uid']);
    $profilePicUrl = 'get_image.php?id=' . $_SESSION['uid'];
}

// Detect the current page
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

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <!-- Theme toggle button ALWAYS visible -->
    <button id="themeToggle" class="btn btn-outline-light btn-sm me-3" title="Toggle Theme">
        <i id="themeIcon" class="bi bi-moon-stars"></i>
    </button>

    <?php if (!in_array($currentPath, $excludeHeader, true)): ?>
      <!-- Show navbar brand and profile dropdown on pages NOT excluded -->
      <a class="navbar-brand" href="/dashboard">Study Hall</a>

      <div class="d-flex align-items-center">
        <?php if (strpos($currentPath, 'profile') !== 0): ?>
          <!-- Profile dropdown -->
          <div class="dropdown">
            <img 
              src="<?= htmlspecialchars($profilePicUrl ?? '/images/default-avatar.png') ?>" 
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
          <!-- Only show logout button on /profile page -->
          <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</nav>

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
    updateIcon(theme);
  }

  function updateIcon(theme) {
    if (!icon) return;
    icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  // Initialize theme from cookie, localStorage, or system preference
  const cookieTheme = document.cookie.match(/(?:^|;\s*)theme=(light|dark)/)?.[1];
  const storedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initialTheme = cookieTheme || storedTheme || (prefersDark ? 'dark' : 'light');
  setTheme(initialTheme);

  // Toggle button click
  if (btn) {
    btn.addEventListener('click', () => {
      const nextTheme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      setTheme(nextTheme);
    });
  }

  // React to system changes if user hasn’t chosen yet
  try {
    if (!storedTheme && window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        setTheme(e.matches ? 'dark' : 'light');
      });
    }
  } catch (_) {}
});
</script>
