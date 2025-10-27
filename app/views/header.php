<?php
// =====================================================
// HEADER (NAVBAR)
// =====================================================

// Ensure $currentUser is loaded for dropdown display
if (!isset($currentUser) && isset($_SESSION['uid'])) {
    $profileModel = new Profile($this->db);
    $currentUser  = $profileModel->getProfileByUserId($_SESSION['uid']);
    $profilePicUrl = 'get_image.php?id=' . $_SESSION['uid'];
}
?>

<nav class="navbar navbar-dark bg-orange">
  <div class="container-fluid">
    <!-- Brand / Home link -->
    <a class="navbar-brand" href="/dashboard">
      <img src="/images/SHLogo.png" alt="Study Hall Logo" height="50">
    </a>

    <!-- Right side: theme toggle + profile dropdown -->
    <div class="d-flex align-items-center">
      
      <!-- =====================================================
           THEME TOGGLE BUTTON
           - Swaps between dark/light using Bootstrap 5.3's
             data-bs-theme attribute.
           ===================================================== -->
      <button id="themeToggle" class="btn btn-outline-light btn-sm me-3" title="Toggle Theme">
        <i id="themeIcon" class="bi bi-moon-stars"></i>
      </button>

      <!-- =====================================================
           PROFILE DROPDOWN
           ===================================================== -->
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

<!-- =====================================================
     THEME TOGGLE SCRIPT
     - Runs after DOM is loaded
     - Updates <html data-bs-theme> and saves choice
     ===================================================== -->
<script>
(function () {
  const root = document.documentElement;
  const btn  = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');

  // Set theme on <html> and persist in localStorage + cookie
  function setTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);
    document.cookie = "theme=" + theme + "; path=/; max-age=31536000";
    updateUI(theme);
  }

  // Update button/icon state
  function updateUI(theme) {
    if (!icon || !btn) return;
    if (theme === 'dark') {
      icon.className = 'bi bi-sun';            // Sun icon in dark mode
      btn.classList.remove('btn-outline-light');
      btn.classList.add('btn-outline-warning');
    } else {
      icon.className = 'bi bi-moon-stars';     // Moon icon in light mode
      btn.classList.remove('btn-outline-warning');
      btn.classList.add('btn-outline-light');
    }
  }

  // Initialize from current <html data-bs-theme>
  const current = root.getAttribute('data-bs-theme') || 'light';
  updateUI(current);

  // Toggle on click
  if (btn) {
    btn.addEventListener('click', function () {
      const next = (root.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
      setTheme(next);
    });
  }
})();
</script>
