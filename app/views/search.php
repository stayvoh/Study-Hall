<?php
declare(strict_types=1);
/** @var string $q */
/** @var string $type */
/** @var string $tag */
/** @var array  $results */
/** @var int    $page */
/** @var int    $limit */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$build = function($n){ $qv = $_GET; $qv['page'] = $n; return '/search?'.http_build_query($qv); };
$prev = max(1, (int)$page - 1);
$next = (int)$page + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search · Study Hall</title>

  <!-- Theme init BEFORE CSS to avoid flash -->
  <script>
    (function () {
      const cookieTheme = document.cookie.match(/(?:^|;\s*)theme=(light|dark)/)?.[1];
      const storedTheme = localStorage.getItem('theme');
      const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      const theme = cookieTheme || storedTheme || (prefersDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
  <style>
    .search-card:hover { background: var(--bs-tertiary-bg); }
  </style>
</head>
<body class="bg-body">

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">Study Hall</a>
    <div class="d-flex align-items-center gap-2">
      <button id="themeToggle" class="btn btn-outline-light btn-sm" type="button" aria-label="Toggle theme">
        <i class="bi bi-moon-stars" id="themeIcon" aria-hidden="true"></i>
        <span class="ms-1 d-none d-sm-inline" id="themeLabel">Dark</span>
      </button>
      <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<section class="py-4">
  <div class="container" style="max-width:1000px;">
    <h3 class="fw-semibold mb-3">Search</h3>

    <!-- Search bar (dropdown-only filter + optional tag slug) -->
    <form class="row g-2 align-items-center mb-4" method="GET" action="/search">
      <div class="col-12 col-md-6">
        <input class="form-control" name="q" placeholder="Search posts, users, or tags…" value="<?= h($q ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="type" aria-label="Result type" onchange="toggleTagField()">
          <option value="posts" <?= ($type==='posts')?'selected':''; ?>>Posts</option>
          <option value="users" <?= ($type==='users')?'selected':''; ?>>Users</option>
          <option value="tags"  <?= ($type==='tags') ?'selected':''; ?>>Tags</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input id="tagField" class="form-control" name="tag" placeholder="Filter by tag (slug)" value="<?= h($tag ?? '') ?>" <?= ($type!=='posts')?'disabled':''; ?>>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-primary">Search</button>
      </div>
    </form>

    <!-- Results -->
    <?php if ($type === 'posts'): ?>
      <?php if (empty($results)): ?>
        <div class="alert alert-light border">No posts found.</div>
      <?php else: ?>
        <div class="list-group shadow-sm">
          <?php foreach ($results as $r): ?>
            <a href="/post?id=<?= (int)$r['id'] ?>" class="list-group-item list-group-item-action search-card">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?= h($r['title']) ?></h6>
                <small class="text-muted"><?= h($r['created_at'] ?? '') ?></small>
              </div>
              <?php if (!empty($r['author'])): ?>
                <div class="small text-muted mb-1">by <?= h($r['author']) ?></div>
              <?php endif; ?>
              <?php if (!empty($r['body'])): ?>
                <p class="mb-1 text-muted"><?= h(mb_strimwidth((string)$r['body'], 0, 160, '…')) ?></p>
              <?php endif; ?>
              <?php if (!empty($r['tags'])): ?>
                <div class="mt-1">
                  <?php foreach ($r['tags'] as $t): ?>
                    <a class="badge rounded-pill text-bg-light border me-1"
                       href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= h($t['name']) ?></a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($type === 'users'): ?>
      <?php if (empty($results)): ?>
        <div class="alert alert-light border">No users found.</div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($results as $u): ?>
            <div class="col-12 col-md-6">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <h6 class="card-title mb-1"><?= h($u['username'] ?: $u['email']) ?></h6>
                  <div class="text-muted small">Joined <?= h($u['created_at'] ?? '') ?> • <?= h($u['email']) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php else: /* tags */ ?>
      <?php if (empty($results)): ?>
        <div class="alert alert-light border">No tags found.</div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($results as $t): ?>
            <div class="col-12 col-sm-6 col-lg-4">
              <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title mb-1">
                    <a class="text-decoration-none" href="/tag/<?= urlencode($t['slug']) ?>">#<?= h($t['name']) ?></a>
                  </h6>
                  <div class="text-muted small mb-3"><?= (int)($t['usage_count'] ?? 0) ?> posts</div>
                  <div class="mt-auto d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary flex-fill" href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">Filter posts</a>
                    <a class="btn btn-sm btn-outline-secondary flex-fill" href="/tag/<?= urlencode($t['slug']) ?>">View tag</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Pager -->
    <nav class="mt-3">
      <ul class="pagination">
        <li class="page-item <?= ($page<=1)?'disabled':''; ?>">
          <a class="page-link" href="<?= h($build($prev)) ?>">Prev</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="<?= h($build($next)) ?>">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</section>

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
    if (theme === 'dark') { icon.className = 'bi bi-sun'; label.textContent = 'Light'; }
    else { icon.className = 'bi bi-moon-stars'; label.textContent = 'Dark'; }
  }
  updateUI(root.getAttribute('data-bs-theme') || 'light');
  btn?.addEventListener('click', function () {
    const next = (root.getAttribute('data-bs-theme') === 'dark') ? 'light' : 'dark';
    setTheme(next);
  });

  // enable/disable tag field based on type
  window.toggleTagField = function(){
    const sel = document.querySelector('select[name="type"]');
    const tag = document.getElementById('tagField');
    if (!sel || !tag) return;
    tag.disabled = (sel.value !== 'posts');
  };
  toggleTagField();
})();
</script>

</body>
</html>
