<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require_login();
require __DIR__ . '/db.php';

$board_id = (int)($_GET['b'] ?? 0);
if ($board_id <= 0) { header('Location: /boards.php'); exit; }

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$board = $pdo->prepare('SELECT id, name, description FROM board WHERE id = ?');
$board->execute([$board_id]);
$board = $board->fetch();
if (!$board) { http_response_code(404); echo 'Board not found'; exit; }

$count = (int)$pdo->prepare('SELECT COUNT(*) FROM post WHERE board_id = ?')
                 ->execute([$board_id]) ?: 0;
$total = (int)$pdo->query("SELECT COUNT(*) AS c FROM post WHERE board_id = {$board_id}")
                  ->fetch()['c'];

$posts = $pdo->prepare("
  SELECT p.id, p.title, LEFT(p.body, 180) AS preview, p.created_at,
         ua.email AS author
  FROM post p
  JOIN user_account ua ON ua.id = p.user_id
  WHERE p.board_id = ?
  ORDER BY p.created_at DESC
  LIMIT ? OFFSET ?
");
$posts->execute([$board_id, $perPage, $offset]);
$posts = $posts->fetchAll();

$pages = max(1, (int)ceil($total / $perPage));
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Hall – <?= htmlspecialchars($board['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex align-items-center justify-content-between">
    <h1 class="mb-3"><?= htmlspecialchars($board['name']) ?></h1>
    <a class="btn btn-primary" href="/new_post.php?b=<?= (int)$board_id ?>">New Post</a>
  </div>
  <p class="text-muted"><?= htmlspecialchars($board['description'] ?? '') ?></p>

  <?php if (!$posts): ?>
    <div class="alert alert-info">No posts yet. Be the first!</div>
  <?php else: ?>
    <div class="list-group mb-3">
      <?php foreach ($posts as $p): ?>
        <a class="list-group-item list-group-item-action" href="/post.php?id=<?= (int)$p['id'] ?>">
          <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1"><?= htmlspecialchars($p['title']) ?></h5>
            <small class="text-muted"><?= htmlspecialchars($p['created_at']) ?></small>
          </div>
          <small class="text-muted">by <?= htmlspecialchars($p['author']) ?></small>
          <p class="mb-1"><?= htmlspecialchars($p['preview']) ?><?= strlen($p['preview'])===180 ? '…' : '' ?></p>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i===$page ? 'active' : '' ?>">
            <a class="page-link" href="/board.php?b=<?= (int)$board_id ?>&page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
<div class="d-flex align-items-center justify-content-between mb-3">
  <a class="btn btn-outline-secondary" href="/boards.php">&larr; Back to Boards</a>
  </div>
</body>
</html>
