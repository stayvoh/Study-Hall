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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">

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

<div class="container-fluid mb-5 px-0" style="max-width: 1400px;">
  <div class="row gx-5">
    
    <!-- Sidebar: Followed Boards -->
    <aside class="col-md-3 mb-4">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Your Boards</div>
        <ul class="list-group list-group-flush">
          <?php if (!empty($followedBoards)): ?>
            <?php foreach ($followedBoards as $b): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="/board?b=<?= (int)$b['id'] ?>" class="text-decoration-none">
                  <?= htmlspecialchars($b['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Not following any boards yet</li>
          <?php endif; ?>
        </ul>
      </div>
    </aside>

    <!-- Main Content: Activity Feed -->
    <main class="col-md-9">
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

      <!-- Posts -->
      <?php if (!empty($posts) && is_array($posts)): ?>
        <div class="list-group shadow-sm">
          <?php foreach ($posts as $p): ?>
            <a href="/post?id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($p['created_at']) ?></small>
              </div>

              <?php if (!empty($p['excerpt'])): ?>
                <p class="mb-1 text-muted"><?= htmlspecialchars($p['excerpt']) ?></p>
              <?php endif; ?>

              <?php if (!empty($p['tags'])): ?>
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

        <!-- Pagination -->
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
        <div class="alert alert-light border text-muted">No posts to show yet.</div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
