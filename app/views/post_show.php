<?php declare(strict_types=1);
/** @var array $thread */
/** @var array $comments */
/** @var ?string $error */
$error = $error ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($thread['title']) ?> – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 800px">

    <a class="btn btn-link mb-3" href="/board?b=<?= (int)$thread['board_id'] ?>">&larr; Back to Board</a>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-0">
        <h2 class="h4 mb-0"><?= htmlspecialchars($thread['title']) ?></h2>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2">
          By <?= htmlspecialchars($thread['author']) ?> on <?= htmlspecialchars($thread['created_at']) ?>
        </p>
        <div><?= nl2br(htmlspecialchars($thread['body'])) ?></div>
      </div>
    </div>

    <h5 class="mb-3">Comments (<?= count($comments) ?>)</h5>
    <?php if (empty($comments)): ?>
      <div class="alert alert-info shadow-sm">No comments yet.</div>
    <?php else: ?>
      <ul class="list-group mb-4 shadow-sm">
        <?php foreach ($comments as $c): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <small class="text-muted">By <?= htmlspecialchars($c['author']) ?></small>
              <small class="text-muted"><?= htmlspecialchars($c['created_at']) ?></small>
            </div>
            <div class="mt-2"><?= nl2br(htmlspecialchars($c['body'])) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0">Add a Comment</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/post?id=<?= (int)$thread['id'] ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <div class="mb-3">
            <textarea class="form-control" name="body" rows="4" required></textarea>
          </div>
          <button class="btn btn-orange" type="submit">Post Comment</button>
        </form>
      </div>
    </div>

  </div>
</body>
</html>
