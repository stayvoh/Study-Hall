<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require __DIR__ . '/db.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h1 class="mb-3">Study Hall</h1>

  <?php if (!empty($_SESSION['uid'])): ?>
    <div class="alert alert-success">You are logged in as <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>.</div>
    <a class="btn btn-primary" href="/boards.php">Boards</a>
    <a class="btn btn-secondary" href="/logout.php">Logout</a>
  <?php else: ?>
    <div class="alert alert-info">You are not logged in.</div>
    <a class="btn btn-primary" href="/login.php">Login</a>
    <a class="btn btn-outline-primary" href="/register.php">Register</a>
  <?php endif; ?>

  <hr>
  <p class="text-muted">DB connection OK (<?= htmlspecialchars(getenv('DB_NAME') ?: 'studyhall') ?>)</p>
</div>
</body>
</html>
