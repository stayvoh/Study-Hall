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
    <a class="navbar-brand" href="/dashboard">Study Hall</a>

    <div class="d-flex align-items-center">
        <button id="themeToggle" class="btn btn-outline-light btn-sm me-3" title="Toggle Theme">
            <i id="themeIcon" class="bi bi-moon-stars"></i>
        </button>
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
<script>
(function () {
  const root  = document.documentElement;
  const btn   = document.getElementById('themeToggle');
  const icon  = document.getElementById('themeIcon');
  const label = document.getElementById('themeLabel');

  function updateUI(theme) {
    if (!icon || !label) return;
    if (theme === 'dark') {
      icon.className = 'bi bi-sun';
      label.textContent = 'Light';
    } else {
      icon.className = 'bi bi-moon-stars';
      label.textContent = 'Dark';
    }
  }

  function setTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    try { localStorage.setItem('theme', theme); } catch (_) {}
    try { document.cookie = "theme=" + theme + "; path=/; max-age=31536000"; } catch (_) {}
    updateUI(theme);
  }

  // Initialize UI from current attribute (theme-init already set it)
  updateUI(root.getAttribute('data-bs-theme') || 'light');

  if (btn) {
    btn.addEventListener('click', function () {
      const next = (root.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
      setTheme(next);
    });
  }
})();
</script>