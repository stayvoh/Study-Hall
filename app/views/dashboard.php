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
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css?v=20251103b" rel="stylesheet">
</head>
<body class="bg-body">
<?php
  $hdr = __DIR__ . '/header.php';
  if (is_file($hdr)) include $hdr;
  function dash_page_url(int $p): string {
    $params = $_GET ?? [];
    $params['page'] = $p;
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    return $path . '?' . http_build_query($params);
  }
?>
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-5 fw-semibold mb-2">Welcome to Study Hall</h1>
    <p class="lead text-muted mb-4">Learn, Collaborate, Build Together</p>

    <!-- Unified Search -->
    <form class="row g-2 justify-content-center" method="get" action="/search" style="max-width:900px;margin:0 auto;">
      <div class="col-12 col-md-5">
        <input class="form-control form-control-lg me-2" type="search"
               name="q" placeholder="Search posts, users, or tags…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      </div>
      <div class="col-6 col-md-2">
        <?php $type = $_GET['type'] ?? 'posts'; ?>
        <select class="form-select form-select-lg" name="type" aria-label="Result type" onchange="toggleTagField()">
          <option value="posts" <?= $type==='posts'?'selected':''; ?>>Posts</option>
          <option value="users" <?= $type==='users'?'selected':''; ?>>Users</option>
          <option value="tags"  <?= $type==='tags' ?'selected':''; ?>>Tags</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-orange btn-lg" type="submit">Search</button>
      </div>
    </form>
  </div>
</section>

<!-- Boards section -->
<?php if (!empty($boards) && is_array($boards)): ?>
<div class="container mb-4" style="max-width: 1000px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="container-fluid px-0">
      <div class="d-flex justify-content-between align-items-center mb-2" style="width:100%;">
        <h4 class="mb-0">Boards</h4>
        <a href="/board/create" class="btn btn-sm btn-orange">
          <i class="bi bi-plus-lg"></i> Create Board
        </a>
      </div>
    </div>
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

  <!-- pagination -->
  <?php if (!empty($pagination) && ($pagination['lastPage'] ?? 1) > 1): 
    $page = (int)$pagination['page'];
    $last = (int)$pagination['lastPage'];

    $start = max(1, $page - 2);
    $end   = min($last, $page + 2);
    if ($end - $start < 4) {
      if ($start === 1) { $end = min($last, $start + 4); }
      if ($end === $last) { $start = max(1, $end - 4); }
    }
  ?>
    <nav aria-label="Boards pagination" class="mt-3">
      <ul class="pagination justify-content-center mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page > 1 ? dash_page_url(1) : '#' ?>" aria-label="First">
            <span aria-hidden="true">&laquo;&laquo;</span>
          </a>
        </li>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page > 1 ? dash_page_url($page - 1) : '#' ?>" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= dash_page_url($i) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?= $page >= $last ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page < $last ? dash_page_url($page + 1) : '#' ?>" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
        <li class="page-item <?= $page >= $last ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page < $last ? dash_page_url($last) : '#' ?>" aria-label="Last">
            <span aria-hidden="true">&raquo;&raquo;</span>
          </a>
        </li>
      </ul>
      <p class="text-center text-muted small mt-2 mb-0">
        Showing page <?= $page ?> of <?= $last ?><?= isset($pagination['total']) ? ' · ' . (int)$pagination['total'] . ' boards' : '' ?>
      </p>
    </nav>
  <?php endif; ?>
</div>
<?php else: ?>
  <div class="container mb-5" style="max-width: 1000px;">
    <div class="alert alert-light border">No boards found.</div>
  </div>
<?php endif; ?>

<script>
  function toggleTagField() {
    const sel = document.querySelector('select[name="type"]');
    const tag = document.getElementById('tagField');
    if (!sel || !tag) return;
    tag.disabled = (sel.value !== 'posts');
  }
  document.addEventListener('DOMContentLoaded', toggleTagField);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
