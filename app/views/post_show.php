<?php declare(strict_types=1);
/** @var array      $post      {id,title,body,created_at,author,board_id,created_by} */
/** @var array      $comments  list of {author,created_at,body,created_by} */
/** @var array|null $tags      optional list of {id,name,slug} */
/** @var ?string    $error */

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$error = $error ?? null;
$boardId = (int)($post['board_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($post['title'] ?? 'Post') ?> – Study Hall</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/custom.css" rel="stylesheet">
  <style>
    .author-wrapper {
    position: relative;
    display: inline-block;
   padding: 5px 20px 30px 10px;
    cursor: pointer;
    }

    .author-name {
        display: inline-block;
    }

    .view-profile-link {
        display: none;
        position: absolute;
        top: 100%; /* just below the name */
        left: 0;
        margin-top: 5px;
        padding: 6px 10px;
        background: #f8f9fa;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.9rem;
        color: #0d6efd;
        text-decoration: none;
        white-space: nowrap;
        z-index: 10;
    }

    /* Hover effect on the wrapper, not just the name */
    .author-wrapper:hover .view-profile-link,
    .author-wrapper .view-profile-link:hover {
        display: inline-block;
        pointer-events: auto;
    }



  </style>
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 800px">

    <?php if ($boardId > 0): ?>
      <a class="btn btn-link mb-3" href="/board?id=<?= $boardId ?>">&larr; Back to Board</a>
    <?php else: ?>
      <a class="btn btn-link mb-3" href="/dashboard">&larr; Back to Dashboard</a>
    <?php endif; ?>

    <!-- Post -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h4 mb-1"><?= h($post['title']) ?></h2>
        <div class="author-wrapper text-muted small mb-3" data-user-id="<?= $post['created_by'] ?? 0 ?>">
        <span class="author-name">by <?= h($post['author'] ?? 'User') ?> • <?= h($post['created_at'] ?? '') ?>
        </span>
            <a href="/profile?id=<?= $post['created_by'] ?? 0 ?>" class="view-profile-link">View Profile</a>
        </div>


        <?php if (!empty($tags) && is_array($tags)): ?>
          <div class="mb-3">
            <?php foreach ($tags as $t): ?>
              <a class="badge rounded-pill text-bg-light border me-1"
                 href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= h($t['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="fs-6">
          <?= nl2br(h($post['body'] ?? '')) ?>
        </div>
      </div>
    </div>

    <!-- Comments -->
    <h5 class="mb-3">Comments (<?= (int)count($comments ?? []) ?>)</h5>

    <?php if (empty($comments)): ?>
      <div class="alert alert-info shadow-sm">No comments yet.</div>
    <?php else: ?>
      <ul class="list-group mb-4 shadow-sm">
        <?php foreach ($comments as $c): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <div class="author-wrapper small text-muted" data-user-id="<?= $c['created_by'] ?? 0 ?>">
                  <span class="author-name">By <?= h($c['author'] ?? 'User') ?></span>
                  <a href="/profile?id=<?= $c['created_by'] ?? 0 ?>" class="view-profile-link">View Profile</a>
              </div>

              <small class="text-muted"><?= h($c['created_at'] ?? '') ?></small>
            </div>
            <div class="mt-2"><?= nl2br(h($c['body'] ?? '')) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- Add Comment -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0">Add a Comment</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger shadow-sm"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/post?id=<?= (int)$post['id'] ?>">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <div class="mb-3">
            <textarea class="form-control" name="body" rows="4" required></textarea>
          </div>
          <button class="btn btn-orange" type="submit">Post Comment</button>
        </form>
      </div>
    </div>

  </div>
</body>
</html>
