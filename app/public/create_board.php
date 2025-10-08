<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require_login();
require __DIR__ . '/db.php';

$err = null; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $err = 'Invalid CSRF';
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  if (!$err && $name === '') $err = 'Name required';
  if (!$err) {
    $stmt = $pdo->prepare('INSERT INTO board (course_id, name, description) VALUES (NULL, ?, ?)');
    $stmt->execute([$name, $desc ?: null]);
    $ok = true;
  }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Create Board – Study Hall</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4"><div class="container" style="max-width:640px">
  <h1 class="mb-3">Create Board</h1>
  <?php if ($ok): ?><div class="alert alert-success">Board created. <a href="/boards.php">Back to boards</a></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" maxlength="100" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description (optional)</label>
      <textarea class="form-control" name="description" rows="3"></textarea>
    </div>
    <button class="btn btn-primary">Create</button>
    <a class="btn btn-secondary" href="/boards.php">Cancel</a>
  </form>
</div></body></html>
