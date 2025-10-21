<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/custom.css">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="text-center mb-4">
        <img src="/images/SHLogo.png" alt="Study Hall" style="max-width: 200px;">
      </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-4 text-center">Reset Password</h4>

          <form method="POST" action="/reset">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="mb-3">
              <label for="password" class="form-label">New password</label>
              <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-green w-100">Reset Password</button>
          </form>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <div class="text-center mt-3">
            <a href="/login" class="login-link">Back to login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
