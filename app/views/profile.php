<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'light') ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($profile['username'] ?? 'Profile') ?> - Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    [data-bs-theme="dark"] .bg-body-secondary { background-color: #2c2c2c !important; }
    [data-bs-theme="dark"] .border-secondary { border-color: #444 !important; }
    .ratio img { object-fit: cover; }
  </style>
</head>
<body class="bg-body text-body">

<?php
// Include navbar with theme toggle
$hdr = __DIR__ . '/header.php';
if (is_file($hdr)) include $hdr;
?>

<div class="container mt-5" style="max-width: 900px;">
  <!-- Profile Header -->
  <div class="row align-items-center">
    <div class="col-md-4 text-center mb-4 mb-md-0">
      <img 
        src="<?= htmlspecialchars($profilePicUrl) ?>" 
        class="rounded-circle border border-secondary"
        style="width: 150px; height: 150px;"
        alt="Profile Picture"
      >
    </div>
    <div class="col-md-8">
      <div class="d-flex align-items-center mb-3 flex-wrap">
        <h2 class="me-3 mb-0"><?= htmlspecialchars($profile['username'] ?? 'Unknown User') ?></h2>

        <?php if (!$isOwnProfile): ?>
          <form method="POST" action="<?= $isFollowing ? '/profile/unfollow' : '/profile/follow' ?>" class="me-2">
            <input type="hidden" name="profile_id" value="<?= $profile['user_id'] ?? $profile['id'] ?>">
            <button type="submit" class="btn <?= $isFollowing ? 'btn-outline-primary' : 'btn-primary' ?> btn-sm">
              <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
            </button>
          </form>
        <?php else: ?>
          <a href="/profile/edit" class="btn btn-outline-secondary btn-sm me-2">Edit profile</a>
        <?php endif; ?>
      </div>

      <div class="d-flex mb-3">
        <div class="me-4"><strong><?= $postCount ?? 0 ?></strong> posts
      </div>

        <div class="me-4">
          <a href="/profile/followers?id=<?= $profile['user_id'] ?>" class="text-decoration-none">
          <strong><?= $followerCount ?? 0 ?></strong> followers
        </a>
        </div>
        <div>
          <a href="/profile/following?id=<?= $profile['user_id'] ?>" class="text-decoration-none">
          <strong><?= $followingCount ?? 0 ?></strong> following</div>
        </a>
      </div>

      <div>
        <span class="text-muted"><?= htmlspecialchars($profile['bio'] ?? 'This is the user bio...') ?></span>
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
  <?php if (!empty($userPosts)): ?>
    <div class="list-group">
      <?php foreach ($userPosts as $post): ?>
        <div class="list-group-item list-group-item-action mb-3 shadow-sm bg-body-secondary border border-secondary">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><?= htmlspecialchars($post['title']) ?></h5>
            <small class="text-muted"><?php
            if (!empty($post['created_at'])) {
                $dt = new DateTime($post['created_at']);
                echo htmlspecialchars($dt->format('F j, Y g:i A')); // e.g., October 29, 2025 4:39 PM
            }
            ?>
          </small>
          </div>
          <div class="mb-2"><?= nl2br(htmlspecialchars($post['body'])) ?></div>
          <small class="text-muted">by <?= htmlspecialchars($post['author'] ?? 'User') ?></small>

          <?php if (!empty($post['tags'])): ?>
            <div class="mt-2">
              <?php foreach ($post['tags'] as $tag): ?>
                <a href="/search?type=posts&tag=<?= urlencode($tag['slug']) ?>" class="badge rounded-pill text-bg-light border me-1">#<?= htmlspecialchars($tag['name']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <a href="/post?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">View Post</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-muted">This user hasn't posted anything yet.</p>
  <?php endif; ?>
</div>



    <!-- Boards Tab -->
    <div class="tab-pane fade" id="boards" role="tabpanel" aria-labelledby="boards-tab">
      <?php if (!empty($followedBoards)): ?>
        <div class="list-group">
          <?php foreach ($followedBoards as $board): ?>
            <a href="/board?id=<?= htmlspecialchars($board['id']) ?>" class="list-group-item list-group-item-action bg-body-secondary border border-secondary mb-2">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?= htmlspecialchars($board['name']) ?></h6>
                <small class="text-muted"><?= (int)$board['post_count'] ?> posts</small>
              </div>
              <p class="mb-1 text-muted"><?= htmlspecialchars($board['description'] ?? 'No description available.') ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted">This user isn’t following any boards yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
