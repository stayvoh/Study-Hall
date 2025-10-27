<div class="container my-4" style="max-width:900px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">#<?= htmlspecialchars($tag['name']) ?></h4>
    <a class="btn btn-sm btn-outline-secondary" href="/tags">&larr; All Tags</a>
  </div>

  <?php if (empty($posts)): ?>
    <div class="alert alert-light border">No posts yet for this tag.</div>
  <?php else: ?>
    <div class="list-group shadow-sm">
      <?php foreach ($posts as $p): ?>
        <a href="/post?id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action">
          <div class="d-flex w-100 justify-content-between">
            <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
            <small class="text-muted"><?= htmlspecialchars($p['created_at']) ?></small>
          </div>
          <?php if (!empty($p['body'])): ?>
            <p class="mb-1 text-muted"><?= htmlspecialchars(mb_strimwidth($p['body'], 0, 160, '…')) ?></p>
          <?php endif; ?>
          <div class="small text-muted mt-1">by <?= htmlspecialchars($p['author'] ?? 'User') ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
