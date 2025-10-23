<?php declare(strict_types=1);
/** @var int $boardId */
/** @var ?string $error */
$error = $error ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New Post – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 720px">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h3 mb-0">New Post</h1>
      <a class="btn btn-outline-secondary" href="/board?b=<?= (int)$boardId ?>">Cancel</a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <form method="post" action="/post/create?b=<?= (int)$boardId ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="mb-3">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" maxlength="120" required>
            <div class="form-text">Required. Max 120 characters.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Body</label>
            <textarea class="form-control" name="body" rows="8" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Tags (comma-separated)</label>
            <input type="text" name="tags" class="form-control"
            placeholder="e.g., php, docker, mariadb"
            value="<?= htmlspecialchars($post['tags_csv'] ?? '') ?>">
            <div class="form-text">Short keywords like “php, docker”.</div>
          </div>


          <div class="d-flex gap-2">
            <button class="btn btn-green" type="submit">Publish</button>
            <a class="btn btn-outline-secondary" href="/board?b=<?= (int)$boardId ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
