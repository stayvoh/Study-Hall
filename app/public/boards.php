<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require_login();
require __DIR__ . '/db.php';

$stmt = $pdo->query('SELECT id, name, description, created_at FROM board ORDER BY name');
$boards = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Hall – Boards</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h1 class="mb-3">Boards</h1>
  <a class="btn btn-secondary mb-3" href="/index.php">Home</a>
  <div class="list-group">
    <?php foreach ($boards as $b): ?>
      <a class="list-group-item list-group-item-action" href="/board.php?b=<?= (int)$b['id'] ?>">
        <div class="d-flex w-100 justify-content-between">
          <h5 class="mb-1"><?= htmlspecialchars($b['name']) ?></h5>
          <small class="text-muted"><?= htmlspecialchars($b['created_at']) ?></small>
        </div>
        <p class="mb-1 text-muted"><?= htmlspecialchars($b['description'] ?? '') ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
