<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require __DIR__ . '/db.php';
require_guest();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $error = 'Invalid CSRF token.';
  $email = trim(strtolower($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';

  if (!$error) {
    $stmt = $pdo->prepare('SELECT id, password_hash FROM user_account WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password_hash'])) {
      // Rotate session ID to prevent fixation
      session_regenerate_id(true);
      $_SESSION['uid'] = (int)$row['id'];
      $_SESSION['email'] = $email;
      header('Location: /index.php'); exit;
    } else {
      $error = 'Invalid email or password.';
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Hall - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:480px">
  <h1 class="mb-3">Sign in</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <button class="btn btn-primary">Login</button>
    <a class="btn btn-link" href="/register.php">Create account</a>
  </form>
</div>
</body>
</html>
