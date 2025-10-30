<?php
declare(strict_types=1);
/** @var int         $boardId */
/** @var ?string     $error */
/** @var array       $old */
/** @var array       $allTags  // list of ['id','name','slug'] */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$csrf = function_exists('csrf_token') ? csrf_token() : ($_SESSION['csrf'] ??= bin2hex(random_bytes(16)));
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$old = $old ?? [];
$sel = array_map('intval', $old['tags'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New post · Study Hall</title>

  <?php
  $themeInit = __DIR__ . '/theme-init.php';
  if (is_file($themeInit)) include $themeInit;
  ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">

<?php
  // Include shared header
  $hdr = __DIR__ . '/header.php';
  if (is_file($hdr)) include $hdr;
?>

<div class="container py-4" style="max-width: 800px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Create a new post</h3>
    <a class="btn btn-outline-secondary btn-sm" href="/board?id=<?= (int)$boardId ?>">Back to board</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" action="/post/create?b=<?= (int)$boardId ?>">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" maxlength="120" required value="<?= h($old['title'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Body</label>
      <textarea name="body" class="form-control" rows="8" required><?= h($old['body'] ?? '') ?></textarea>
    </div>

    <!-- Tags -->
    <div class="mb-3">
      <label class="form-label">Tags</label>
      <input type="text" class="form-control" name="new_tags" placeholder="e.g. help, php, etc">
    </div>

    <button class="btn btn-orange">Publish</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
