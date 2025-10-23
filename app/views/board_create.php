<?php declare(strict_types=1);
/** @var ?string $error */
/** @var ?bool $success */
$error   = $error   ?? null;
$success = $success ?? false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Board – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 720px">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h3 mb-0">Create Board</h1>
      <a class="btn btn-outline-secondary" href="/boards">Cancel</a>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success shadow-sm">
        Board created. <a class="alert-link" href="/boards">Back to boards</a>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <form method="post" action="/board/create" novalidate>
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" maxlength="100" required>
            <div class="form-text">Required. Max 100 characters.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(optional)</span></label>
            <textarea class="form-control" name="description" rows="3" maxlength="255"></textarea>
            <div class="form-text">Up to 255 characters.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-green" type="submit">Create</button>
            <a class="btn btn-outline-secondary" href="/boards">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</body>
</html>
