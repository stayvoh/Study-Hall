<?php declare(strict_types=1);
/** @var array $board */
/** @var array $posts */
/** @var int $page */
/** @var int $pages */
$boardId = (int)$board['id'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($board['name']) ?> – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 960px">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h1 class="h3 mb-0"><?= htmlspecialchars($board['name']) ?></h1>
      <div class="d-flex gap-2">
        <!-- TODO: point to your Post create route when ready -->
        <a class="btn btn-orange" href="/post/create?b=<?= $boardId ?>">New Post</a>
        <a class="btn btn-outline-secondary" href="/boards">Back to Boards</a>
      </div>
    </div>
    <?php if (!empty($board['description'])): ?>
      <p class="text-muted mb-4"><?= htmlspecialchars($board['description']) ?></p>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
      <div class="alert alert-info shadow-sm">No posts yet. Be the first to start a discussion.</div>
    <?php else: ?>
      <div class="list-group shadow-sm mb-3">
        <?php foreach ($posts as $p): ?>
          <a class="list-group-item list-group-item-action p-3" href="/post?id=<?= (int)$p['id'] ?>">
            <div class="d-flex w-100 justify-content-between">
              <h2 class="h5 mb-1 mb-sm-0"><?= htmlspecialchars($p['title']) ?></h2>
              <small class="text-muted ms-sm-3"><?= htmlspecialchars($p['created_at']) ?></small>
            </div>
            <small class="text-muted">by <?= htmlspecialchars($p['author']) ?></small>
            <p class="mb-0 mt-1">
              <?= htmlspecialchars($p['preview']) ?>
              <?= (strlen($p['preview']) === 180) ? '…' : '' ?>
            </p>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <nav aria-label="Posts navigation">
          <ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="/board?b=<?= $boardId ?>&page=<?= max(1, $page-1) ?>" tabindex="-1">Prev</a>
            </li>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="/board?b=<?= $boardId ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
              <a class="page-link" href="/board?b=<?= $boardId ?>&page=<?= min($pages, $page+1) ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>