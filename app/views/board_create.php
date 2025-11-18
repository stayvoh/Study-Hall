<?php
declare(strict_types=1);
/** @var int     $boardId */
/** @var ?string $error */
/** @var array   $old */
/** @var string  $mode */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$csrf = function_exists('csrf_token') ? csrf_token() : ($_SESSION['csrf'] ??= bin2hex(random_bytes(16)));
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$mode    = $mode ?? 'create';
$old     = $old  ?? ['name'=>'','description'=>''];
$boardId = (int)($boardId ?? 0);
$pageTitle   = ($mode === 'edit') ? 'Edit board · Study Hall' : 'Create board · Study Hall';
$heading     = ($mode === 'edit') ? 'Edit Board' : 'Create a Board';
$submitLabel = ($mode === 'edit') ? 'Save Changes' : 'Create Board';
$formAction = ($mode === 'edit') ? '/board/update?id=' . $boardId : '/board/create';
$backHref = ($mode === 'edit') ? '/board?id=' . $boardId : '/dashboard';
$backText = 'Back';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= h($pageTitle) ?></title>
  <?php $themeInit = __DIR__ . '/theme-init.php'; if (is_file($themeInit)) include $themeInit; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">
<?php $hdr = __DIR__ . '/header.php'; if (is_file($hdr)) include $hdr; ?>

<div class="container py-4" style="max-width: 800px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><?= h($heading) ?></h3>
    <a class="btn btn-outline-secondary btn-sm" href="<?= h($backHref) ?>"><?= h($backText) ?></a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= h($formAction) ?>">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <div class="mb-3">
      <label class="form-label">Board Name</label>
      <input name="name" class="form-control" maxlength="100" required value="<?= h($old['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"><?= h($old['description'] ?? '') ?></textarea>
    </div>
    <button class="btn btn-orange"><?= h($submitLabel) ?></button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
