<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Boards – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet"> <!-- adjust path if different -->
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 960px">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h3 mb-0">Boards</h1>
      <div class="d-flex gap-2">
        <a class="btn btn-green" href="/board/create">Create Board</a>
        <a class="btn btn-outline-secondary" href="/dashboard">Dashboard</a>
      </div>
    </div>

    <?php if (empty($boards)): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <h2 class="h5 mb-2">No boards yet</h2>
          <p class="text-muted mb-4">Create the first board to get started.</p>
          <a class="btn btn-green" href="/board/create">Create Board</a>
        </div>
      </div>
    <?php else: ?>
      <div class="list-group shadow-sm">
        <?php foreach ($boards as $b): ?>
          <a class="list-group-item list-group-item-action p-3" href="/board?b=<?= (int)$b['id'] ?>">
            <div class="d-flex w-100 justify-content-between">
              <h2 class="h5 mb-1"><?= htmlspecialchars($b['name']) ?></h2>
              <small class="text-muted"><?= htmlspecialchars($b['created_at']) ?></small>
            </div>
            <?php if (!empty($b['description'])): ?>
              <p class="mb-0 text-muted"><?= htmlspecialchars($b['description']) ?></p>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
