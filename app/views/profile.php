<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($currentUser['username'] ?? 'Profile') ?> - Study Hall</title>
  <?php
  $themeInit = __DIR__ . '/theme-init.php';
  if (is_file($themeInit)) include $themeInit;
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php
// Include the header (theme toggle + navbar)
$hdr = __DIR__ . '/header.php'; // adjust the path if needed
if (is_file($hdr)) include $hdr;
?>
<div class="container mt-5" style="max-width: 900px;">
  <!-- Profile Header -->
  <div class="row align-items-center">
    <div class="col-md-4 text-center mb-4 mb-md-0">
      <img 
        src="<?= htmlspecialchars($profilePicUrl) ?>" 
        class="rounded-circle border border-2"
        style="width: 150px; height: 150px; object-fit: cover;"
        alt="Profile Picture"
      >
    </div>
    <div class="col-md-8">
      <div class="d-flex align-items-center mb-3 flex-wrap">
        <h2 class="me-3 mb-0"><?= htmlspecialchars($currentUser['username'] ?? 'Unknown User') ?></h2>
        <a href="/profile/edit" class="btn btn-outline-secondary btn-sm me-2">Edit profile</a>
        <button class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-gear"></i>
        </button>
      </div>
      <div class="d-flex mb-3">
        <div class="me-4"><strong>54</strong> posts</div>
        <div class="me-4"><strong>834</strong> followers</div>
        <div><strong>312</strong> following</div>
      </div>
      <div>
        <span class="text-muted"><?= htmlspecialchars($currentUser['bio'] ?? 'This is your bio...') ?></span>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <!-- Tabs -->
  <ul class="nav nav-tabs" id="profileTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab" aria-controls="posts" aria-selected="true">
        Posts
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="boards-tab" data-bs-toggle="tab" data-bs-target="#boards" type="button" role="tab" aria-controls="boards" aria-selected="false">
        Boards
      </button>
    </li>
  </ul>

  <div class="tab-content mt-3" id="profileTabsContent">
    <!-- Posts Tab -->
    <div class="tab-pane fade show active" id="posts" role="tabpanel" aria-labelledby="posts-tab">
      <div class="row g-2">
        <?php for ($i = 0; $i < 9; $i++): ?>
          <div class="col-4">
            <div class="ratio ratio-1x1 bg-light border">
              <img src="https://via.placeholder.com/300" class="w-100 h-100" style="object-fit: cover;" alt="Post Image">
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Boards Tab -->
    <div class="tab-pane fade" id="boards" role="tabpanel" aria-labelledby="boards-tab">
      <div class="list-group">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <a href="#" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
              <h6 class="mb-1">Board Title <?= $i+1 ?></h6>
              <small class="text-muted">12 posts</small>
            </div>
            <p class="mb-1 text-muted">Short board description goes here.</p>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
