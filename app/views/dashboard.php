<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard · Study Hall</title>

  <!-- Theme init BEFORE CSS to avoid flash -->
  <script id="theme-init">
    (function () {
      const cookieTheme = document.cookie.match(/(?:^|;\s*)theme=(light|dark)/)?.[1];
      const storedTheme = localStorage.getItem('theme');
      const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      const theme = cookieTheme || storedTheme || (prefersDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (for sun/moon) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body"><!-- bg-body adapts with theme -->

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">Study Hall</a>

    <div class="d-flex align-items-center gap-2">
      <!-- Theme Toggle -->
      <button id="themeToggle" class="btn btn-outline-light btn-sm" type="button" aria-label="Toggle theme">
        <i class="bi bi-moon-stars" id="themeIcon" aria-hidden="true"></i>
        <span class="ms-1 d-none d-sm-inline" id="themeLabel">Dark</span>
      </button>

      <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold mb-2">Welcome to Study Hall</h1>
    <p class="lead text-muted mb-4">Learn, Collaborate, Build Together</p>

    <!-- Unified Search: dropdown-only filter -->
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

<div class="container mb-5" style="max-width: 1000px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Activity Feed</h4>
    <ul class="nav nav-pills">
      <?php $feed = $_GET['feed'] ?? 'all'; ?>
      <li class="nav-item">
        <a class="nav-link <?= $feed==='all'?'active':''; ?>" href="/dashboard?feed=all">All</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $feed==='following'?'active':''; ?>" href="/dashboard?feed=following">Following</a>
      </li>
    </ul>
  </div>

  <!-- Real posts list if provided; fall back to placeholders -->
  <?php if (!empty($posts) && is_array($posts)): ?>
    <div class="list-group shadow-sm">
      <?php foreach ($posts as $p): ?>
        <a href="/post?id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action">
          <div class="d-flex w-100 justify-content-between">
            <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
            <small class="text-muted"><?= htmlspecialchars($p['created_at']) ?></small>
          </div>

          <?php if (!empty($p['excerpt']) || !empty($p['body'])): ?>
            <p class="mb-1 text-muted">
              <?= htmlspecialchars($p['excerpt'] ?? mb_strimwidth($p['body'], 0, 160, '…')) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($p['tags']) && is_array($p['tags'])): ?>
            <div class="mt-1">
              <?php foreach ($p['tags'] as $t): ?>
                <a class="badge rounded-pill text-bg-light border me-1"
                   href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= htmlspecialchars($t['name']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="small text-muted mt-1">
            by <?= htmlspecialchars($p['author'] ?? 'User') ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php
      $page   = max(1, (int)($_GET['page'] ?? 1));
      $prev   = $page > 1 ? $page - 1 : 1;
      $next   = $page + 1;
      $build  = function($n) { $q = $_GET; $q['page']=$n; return '/dashboard?'.http_build_query($q); };
    ?>
    <nav class="mt-3">
      <ul class="pagination">
        <li class="page-item <?= $page<=1?'disabled':''; ?>"><a class="page-link" href="<?= $build($prev) ?>">Prev</a></li>
        <li class="page-item"><a class="page-link" href="<?= $build($next) ?>">Next</a></li>
      </ul>
    </nav>

  <?php else: ?>
    <div class="list-group shadow-sm">
      <a href="#" class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1">Question on Lecture 3</h6>
          <small class="text-muted">1 hour ago</small>
        </div>
        <p class="mb-1 text-muted">Why does this work?</p>
      </a>
      <a href="#" class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1">Project Idea</h6>
          <small class="text-muted">1 day ago</small>
        </div>
        <p class="mb-1 text-muted">Looking for teammate skilled in JS.</p>
      </a>
      <a href="#" class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1">Lost and Found Web App</h6>
          <small class="text-muted">1 week ago</small>
        </div>
        <p class="mb-1 text-muted">New updates pushed to repo.</p>
      </a>
    </div>
  <?php endif; ?>
</div>

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
