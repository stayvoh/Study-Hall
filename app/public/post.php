<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require_login();
require __DIR__ . '/db.php';

$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) { header('Location: /boards.php'); exit; }

$thread = $pdo->prepare('
  SELECT p.id, p.title, p.body, p.created_at, p.board_id, ua.email AS author
  FROM post p
  JOIN user_account ua ON ua.id = p.user_id
  WHERE p.id = ?
');
$thread->execute([$post_id]);
$thread = $thread->fetch();
if (!$thread) { http_response_code(404); echo 'Post not found'; exit; }

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $err = 'Invalid CSRF token.';
  $body = trim($_POST['body'] ?? '');
  if (!$err && $body === '') $err = 'Comment cannot be empty.';
  if (!$err) {
    $pdo->prepare('INSERT INTO comment(post_id,user_id,body) VALUES (?,?,?)')
        ->execute([$post_id, $_SESSION['uid'], $body]);
    header('Location: /post.php?id='.$post_id); exit;
  }
}

$comments = $pdo->prepare('
  SELECT c.id, c.body, c.created_at, ua.email AS author
  FROM comment c
  JOIN user_account ua ON ua.id = c.user_id
  WHERE c.post_id = ?
  ORDER BY c.created_at ASC
');
$comments->execute([$post_id]);
$comments = $comments->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($thread['title']) ?> – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <a class="btn btn-link mb-3" href="/board.php?b=<?= (int)$thread['board_id'] ?>">&larr; Back to board</a>

  <div class="card mb-4">
    <div class="card-header">
      <h3 class="mb-0"><?= htmlspecialchars($thread['title']) ?></h3>
    </div>
    <div class="card-body">
      <p class="text-muted mb-2">By <?= htmlspecialchars($thread['author']) ?> on <?= htmlspecialchars($thread['created_at']) ?></p>
      <div><?= nl2br(htmlspecialchars($thread['body'])) ?></div>
    </div>
  </div>

  <h5 class="mb-3">Comments (<?= count($comments) ?>)</h5>
  <?php if (!$comments): ?>
    <div class="alert alert-info">No comments yet.</div>
  <?php else: ?>
    <ul class="list-group mb-4">
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

  <div class="card">
    <div class="card-header">Add a comment</div>
    <div class="card-body">
      <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="mb-3">
          <textarea class="form-control" name="body" rows="4" required></textarea>
        </div>
        <button class="btn btn-primary">Post comment</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
