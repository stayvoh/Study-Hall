<?php
declare(strict_types=1);
require __DIR__ . '/session.php';
require __DIR__ . '/db.php';
require_guest();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) $errors[] = 'Invalid CSRF token.';
  $email = trim(strtolower($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password2'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
  if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

  if (!$errors) {
    // Check duplicate
    $stmt = $pdo->prepare('SELECT 1 FROM user_account WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = 'An account with that email already exists.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $pdo->prepare('INSERT INTO user_account(email, password_hash) VALUES (?, ?)')
          ->execute([$email, $hash]);
      $uid = (int)$pdo->lastInsertId();
      // optional profile stub:
      $pdo->prepare('INSERT INTO user_profile(user_id, username) VALUES (?, ?)')
          ->execute([$uid, explode('@', $email, 2)[0]]);
      $success = true;
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Hall - Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:560px">
  <h1 class="mb-3">Create your account</h1>

  <?php if ($success): ?>
    <div class="alert alert-success">Account created. You can now <a href="/login.php">log in</a>.</div>
  <?php endif; ?>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" minlength="8" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm Password</label>
      <input class="form-control" type="password" name="password2" minlength="8" required>
    </div>
    <button class="btn btn-primary">Register</button>
    <a class="btn btn-link" href="/login.php">Have an account? Log in</a>
  </form>
</div>
</body>
</html>
