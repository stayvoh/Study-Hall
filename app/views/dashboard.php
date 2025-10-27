<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard · Study Hall</title>
  <?php
  $themeInit = __DIR__ . '/theme-init.php';
  if (is_file($themeInit)) include $themeInit;
  ?>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (for sun/moon) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body"><!-- bg-body adapts with theme -->

<?php
  $hdr = __DIR__ . '/header.php';   // adjust path if your header lives in /views/partials/header.php
  if (is_file($hdr)) include $hdr;
?>
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold mb-2">Welcome to Study Hall</h1>
    <p class="lead text-muted mb-4">Learn, Collaborate, Build Together</p>

    <!-- Unified Search -->
    <form class="row g-2 justify-content-center" method="GET" action="/search" style="max-width:900px;margin:0 auto;">
      <div class="col-12 col-md-6">
        <input class="form-control form-control-lg me-2" type="search"
               name="q" placeholder="Search posts, users, or tags…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <?php $type = $_GET['type'] ?? 'posts'; ?>
        <select class="form-select form-select-lg" name="type" aria-label="Result type">
          <option value="posts" <?= $type==='posts'?'selected':''; ?>>Posts</option>
          <option value="users" <?= $type==='users'?'selected':''; ?>>Users</option>
          <option value="tags"  <?= $type==='tags' ?'selected':''; ?>>Tags</option>
        </select>
      </div>
      <div class="col-6 col-md-1 d-grid">
        <button class="btn btn-primary btn-lg" type="submit">Search</button>
      </div>
    </form>
  </div>
</section>

<!-- Boards section -->
<?php if (!empty($boards) && is_array($boards)): ?>
<div class="container mb-4" style="max-width: 1000px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Boards</h4>
  </div>

  <div class="row g-3">
    <?php foreach ($boards as $b): ?>
      <div class="col-12 col-md-6">
        <a class="card text-decoration-none h-100" href="/board?id=<?= (int)$b['id'] ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="card-title mb-0"><?= htmlspecialchars($b['name']) ?></h6>
              <?php if (isset($b['post_count'])): ?>
                <span class="badge text-bg-light"><?= (int)$b['post_count'] ?> posts</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($b['description'])): ?>
              <p class="card-text text-muted mt-2 mb-0"><?= htmlspecialchars($b['description']) ?></p>
            <?php endif; ?>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
  <div class="container mb-5" style="max-width: 1000px;">
    <div class="alert alert-light border">No boards found.</div>
  </div>
<?php endif; ?>

<!-- Theme toggle logic -->
<script>
(function () {
  const root = document.documentElement;
  const btn  = document.getElementById('themeToggle');
  const icon = document.getElementById('themeIcon');
  const label= document.getElementById('themeLabel');

  function setTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);
    document.cookie = "theme=" + theme + "; path=/; max-age=31536000";
    updateUI(theme);
  }

  function updateUI(theme) {
    if (!icon || !label) return;
    if (theme === 'dark') {
      icon.className = 'bi bi-sun';          // show sun in dark mode (tap to go light)
      label.textContent = 'Light';
    } else {
      icon.className = 'bi bi-moon-stars';   // show moon in light mode (tap to go dark)
      label.textContent = 'Dark';
    }
  }

  // Initialize button state
  const current = root.getAttribute('data-bs-theme') || 'light';
  updateUI(current);

  // Bind click
  if (btn) {
    btn.addEventListener('click', function () {
      const next = (root.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
      setTheme(next);
    });
  }

  // React to system changes if the user hasn’t chosen yet
  try {
    const stored = localStorage.getItem('theme');
    if (!stored && window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        setTheme(e.matches ? 'dark' : 'light');
      });
    }
  } catch (_) {}
})();
</script>

</body>
</html>
