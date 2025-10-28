<?php
/** @var array $board */
/** @var array $posts */
/** @var int   $page */
/** @var int   $pages */
/** @var bool  $isFollowing */
/** @var int   $followerCount */
/** @var string $csrf */

$uid = $_SESSION['uid'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($board['name']) ?> · Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">
<?php include __DIR__ . '/header.php'; ?>

<section class="py-4">
  <div class="container" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="d-flex align-items-center gap-3">
        <h3 class="mb-0"><?= htmlspecialchars($board['name']) ?></h3>
        <span class="badge text-bg-light border" title="Followers">
          <i class="bi bi-people me-1"></i><?= (int)$followerCount ?>
        </span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <?php if ($uid): ?>
          <form method="post" action="/boards/<?= (int)$board['id'] ?>/<?= $isFollowing ? 'unfollow' : 'follow' ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <button type="submit"
                  class="btn btn-sm <?= $isFollowing ? 'btn-outline-danger' : 'btn-outline-primary' ?>">
                  <i class="bi <?= $isFollowing ? 'bi-heartbreak' : 'bi-heart' ?>"></i>
                  <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                </button>
            </form>

        <?php endif; ?>

        <a class="btn btn-sm btn-outline-secondary" href="/dashboard">Back to boards</a>
        <a class="btn btn-sm btn-primary" href="/post/create?b=<?= (int)$board['id'] ?>">New post</a>
      </div>
    </div>

    <?php if (!empty($board['description'])): ?>
      <p class="text-muted mb-4"><?= htmlspecialchars($board['description']) ?></p>
    <?php endif; ?>

    <?php if (!empty($posts)): ?>
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
            <div class="small text-muted mt-1">by <?= htmlspecialchars($p['author'] ?? 'User') ?></div>
          </a>
        <?php endforeach; ?>
      </div>

      <nav class="mt-3">
        <ul class="pagination">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="/board?id=<?= (int)$board['id'] ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
          </li>
          <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="/board?id=<?= (int)$board['id'] ?>&page=<?= min($pages, $page + 1) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php else: ?>
      <div class="alert alert-light border">No posts yet in this board.</div>
    <?php endif; ?>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
