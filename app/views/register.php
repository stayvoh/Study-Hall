<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
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
          <h4 class="card-title mb-4 text-center">Register</h4>

          <form method="POST" action="/register">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
              <label for="email" class="form-label">Email address</label>
              <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="confirm" class="form-label">Confirm Password</label>
              <input type="password" id="confirm" name="confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-green w-100">Register</button>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="/login" class="login-link">Login</a></p>
            </div>

          </form>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
