<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require_login();
require __DIR__ . '/db.php';

$board_id = (int)($_GET['b'] ?? 0);
if ($board_id <= 0) { header('Location: /boards.php'); exit; }

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $err = 'Invalid CSRF token.';
  $title = trim($_POST['title'] ?? '');
  $body  = trim($_POST['body'] ?? '');
  if (!$err) {
    if ($title === '' || $body === '') $err = 'Title and body are required.';
  }
  if (!$err) {
    $stmt = $pdo->prepare('INSERT INTO post(board_id,user_id,title,body,is_question) VALUES (?,?,?,?,1)');
    $stmt->execute([$board_id, $_SESSION['uid'], $title, $body]);
    header('Location: /post.php?id='.$pdo->lastInsertId()); exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Post – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:720px">
  <h1 class="mb-3">New Post</h1>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" maxlength="120" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Body</label>
      <textarea class="form-control" name="body" rows="8" required></textarea>
    </div>
    <button class="btn btn-primary">Publish</button>
    <a class="btn btn-secondary" href="/board.php?b=<?= (int)$board_id ?>">Cancel</a>
  </form>
</div>
</body>
</html>
