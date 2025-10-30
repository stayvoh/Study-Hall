<?php declare(strict_types=1);
/** @var array      $post      {id,title,body,created_at,author,board_id,created_by} */
/** @var array      $comments  list of {author,created_at,body,created_by} */
/** @var array|null $tags      optional list of {id,name,slug} */
/** @var ?string    $error */

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$error = $error ?? null;
$boardId = (int)($post['board_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($post['title'] ?? 'Post') ?> – Study Hall</title>

  <?php
    $themeInit = __DIR__ . '/theme-init.php';
    if (is_file($themeInit)) include $themeInit;
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">
  <?php
    $hdr = __DIR__ . '/header.php';
    if (is_file($hdr)) include $hdr;
  ?>

  <div class="container py-4" style="max-width: 800px">

    <?php if ($boardId > 0): ?>
      <a href="/board?id=<?= $boardId ?>" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Board
      </a>
    <?php else: ?>
      <a class="btn btn-link mb-3" href="/dashboard">&larr; Back to Dashboard</a>
    <?php endif; ?>

    <!-- Post -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h4 mb-1"><?= h($post['title']) ?></h2>
        <div class="text-muted small mb-3">
          by <?= h($post['author'] ?? 'User') ?> • 
          <?php
            if (!empty($post['created_at'])) {
                $dt = new DateTime($post['created_at']);
                echo h($dt->format('F j, Y g:i A')); // October 29, 2025 4:39 PM
            }
            ?>
          <?php if (!empty($post['created_by'])): ?>
            <span class="mx-1 text-secondary">•</span>
            <a href="/profile?id=<?= (int)$post['created_by'] ?>"
              class="btn btn-sm btn-outline-secondary py-0 px-2 align-baseline">
              <i class="bi bi-person"></i> View Profile
            </a>
          <?php endif; ?>
        </div>

        <?php if (!empty($tags) && is_array($tags)): ?>
          <div class="mb-3">
            <?php foreach ($tags as $t): ?>
              <a class="badge rounded-pill text-bg-light border me-1"
                 href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= h($t['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="fs-6">
          <?= nl2br(h($post['body'] ?? '')) ?>
        </div>
      </div>
    </div>

    <!-- Comments -->
    <h5 class="mb-3">Comments (<?= (int)count($comments ?? []) ?>)</h5>

    <?php if (empty($comments)): ?>
      <div class="alert alert-info shadow-sm">No comments yet.</div>
    <?php else: ?>
      <ul class="list-group mb-4 shadow-sm">
        <?php foreach ($comments as $c): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <div class="small text-muted">
                By <?= h($c['author'] ?? 'User') ?>
                <?php if (!empty($c['created_by'])): ?>
                  • <a href="/profile?id=<?= (int)$c['created_by'] ?>">View Profile</a>
                <?php endif; ?>
              </div>
                <?php
                if (!empty($c['created_at'])) {
                    $dt = new DateTime($c['created_at']);
                    echo '<small class="text-muted">' . h($dt->format('F j, Y g:i A')) . '</small>';
                }
                ?>
            </div>
            <div class="mt-2"><?= nl2br(h($c['body'] ?? '')) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- Add Comment -->
    <div class="card border-0 shadow-sm">
      <div class="card-header">Add a Comment</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger shadow-sm"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/post?id=<?= (int)$post['id'] ?>">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <div class="mb-3">
            <textarea class="form-control" name="body" rows="4" required></textarea>
          </div>
          <button class="btn btn-blue" type="submit">Post Comment</button>
        </form>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
