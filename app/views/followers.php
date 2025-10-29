<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'light') ?>">
<head>
  <meta charset="UTF-8">
  <title>Followers - <?= htmlspecialchars($profile['username'] ?? 'Profile') ?> - Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css?v=3" rel="stylesheet">
</head>

<body class="bg-body text-body">

<?php
$hdr = __DIR__ . '/header.php';
if (is_file($hdr)) include $hdr;
?>

<div class="container mt-5" style="max-width: 900px;">
  <h2 class="text-center mb-4">Followers of <?= htmlspecialchars($profile['username']) ?></h2>
    <form method="get" class="d-flex mb-4" role="search">
    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
    <input 
        type="text" 
        name="search" 
        class="form-control me-2" 
        placeholder="Search followers..." 
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
    >
    <button class="btn btn-outline-primary" type="submit">Search</button>
</form>
  <?php if (!empty($followers)): ?>
  <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
      <?php foreach ($followers as $f): ?>
      <div class="col text-center">
          <a href="/profile?id=<?= $f['user_id'] ?>" class="text-decoration-none follower-link">
              <div class="follower-card">
                  <img 
                      src="/get_image.php?id=<?= $f['user_id'] ?>" 
                      alt="<?= htmlspecialchars($f['username']) ?>'s avatar" 
                      class="rounded-circle follower-avatar"
                      onerror="this.onerror=null;this.src='/public/images/default-avatar.jpg';"
                  >
                  <div class="follower-username"><?= htmlspecialchars($f['username']) ?></div>
              </div>
          </a>
      </div>
      <?php endforeach; ?>
  </div>
    <?php else: ?>
        <p class="text-center text-muted">
            <?php if (!empty($_GET['search'])): ?>
                No followers found matching "<?= htmlspecialchars($_GET['search']) ?>"
            <?php else: ?>
                This user has no followers yet.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
