<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
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
          <h4 class="card-title mb-4 text-center">Login</h4>

          <form method="POST" action="/login">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <!-- Email -->
            <div class="form-outline mb-4">
              <input type="email" id="email" name="email" class="form-control" required />
              <label class="form-label" for="email">Email address</label>
            </div>

            <!-- Password -->
            <div class="form-outline mb-4">
              <input type="password" id="password" name="password" class="form-control" required />
              <label class="form-label" for="password">Password</label>
            </div>

            <!-- Remember + Forgot -->
            <div class="row mb-4">
              <div class="col d-flex justify-content-start">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                  <label class="form-check-label" for="remember"> Remember me </label>
                </div>
              </div>
              <div class="col text-end">
                <a href="/forgot" class="forgot-link">Forgot password?</a>
              </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-orange btn-block w-100 mb-4">Sign in</button>

            <!-- Register -->
            <div class="text-center">
              <p>Not a member? <a href="/register" class="register-link">Register</a></p>
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
