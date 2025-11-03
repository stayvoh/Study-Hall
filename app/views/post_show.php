<?php declare(strict_types=1);
/** @var array      $post */
/** @var array      $comments */
/** @var array|null $tags */
/** @var ?string    $error */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$error = $error ?? null;
$boardId = (int)($post['board_id'] ?? 0);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$csrf = function_exists('csrf_token') ? csrf_token() : $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($post['title'] ?? 'Post') ?> – Study Hall</title>
  <?php $themeInit = __DIR__ . '/theme-init.php'; if (is_file($themeInit)) include $themeInit; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">
  <?php $hdr = __DIR__ . '/header.php'; if (is_file($hdr)) include $hdr; ?>

  <div class="container py-4" style="max-width: 800px">
    <?php if ($boardId > 0): ?>
      <a href="/board?id=<?= $boardId ?>" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Board
      </a>
    <?php else: ?>
      <a class="btn btn-link mb-3" href="/dashboard">&larr; Back to Dashboard</a>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h4 mb-2"><?= h($post['title']) ?></h2>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="text-muted small">
            by <?= h($post['author'] ?? 'User') ?>
            <?php if (!empty($post['created_at'])) { $dt = new DateTime($post['created_at']); ?>
              • <?= h($dt->format('F j, Y g:i A')) ?>
            <?php } ?>
            <?php if (!empty($post['created_by'])): ?>
              <span class="mx-1 text-secondary">•</span>
              <a href="/profile?id=<?= (int)$post['created_by'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2 align-baseline">
                <i class="bi bi-person"></i> View Profile
              </a>
            <?php endif; ?>
          </div>

          <?php if (!empty($_SESSION['uid']) && (int)$_SESSION['uid'] === (int)($post['created_by'] ?? 0)): ?>
            <div class="d-flex align-items-center gap-2">
              <a href="/post/edit?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil-square"></i> Edit
              </a>
              <form method="post" action="/post/delete?id=<?= (int)$post['id'] ?>" onsubmit="return confirm('Delete this post? This cannot be undone.');">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($tags) && is_array($tags)): ?>
          <div class="mb-3">
            <?php foreach ($tags as $t): ?>
              <a class="badge rounded-pill text-bg-light border me-1 text-decoration-none" href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= h($t['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="fs-6">
          <?= nl2br(h($post['body'] ?? '')) ?>
        </div>
      </div>
    </div>

    <h5 class="mb-3">Comments (<?= (int)count($comments ?? []) ?>)</h5>

    <?php if (empty($comments)): ?>
      <div class="alert alert-info shadow-sm">No comments yet.</div>
    <?php else: ?>
      <ul class="list-group mb-4 shadow-sm">
        <?php foreach ($comments as $c): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
              <div class="small text-muted">
                By <?= h($c['author'] ?? 'User') ?>
                <?php if (!empty($c['created_by'])): ?>
                  <span class="mx-1 text-secondary">•</span>
                  <a href="/profile?id=<?= (int)$c['created_by'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2 align-baseline">
                    <i class="bi bi-person"></i> View Profile
                  </a>
                <?php endif; ?>
              </div>
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($c['created_at'])) { $dt = new DateTime($c['created_at']); ?>
                  <small class="text-muted"><?= h($dt->format('F j, Y g:i A')) ?></small>
                <?php } ?>

                <?php if (!empty($_SESSION['uid']) && (int)$_SESSION['uid'] === (int)($c['created_by'] ?? 0)): ?>
                  <form method="post" action="/comment/delete?id=<?= (int)($c['id'] ?? 0) ?>" onsubmit="return confirm('Delete this comment?');">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                      <i class="bi bi-x-circle"></i> Delete
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>

            <div class="mt-2"><?= nl2br(h($c['body'] ?? '')) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-header">Add a Comment</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger shadow-sm"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/post?id=<?= (int)$post['id'] ?>">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <div class="mb-3">
            <textarea class="form-control" name="body" rows="4" required></textarea>
          </div>
          <button class="btn btn-orange px-3" type="submit">
            <i class="bi bi-send me-1"></i> Post Comment
          </button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
