<?php
require __DIR__ . '/session.php'; // for csrf_token(), display_name(), etc.
$type = $type ?? 'posts';
function isActive($t, $type) { return $t === $type ? 'active' : ''; }
?>
<div class="container my-4">
  <form method="get" action="/search" class="d-flex gap-2 mb-3">
    <input class="form-control" type="search" name="q" placeholder="Search…" value="<?= htmlspecialchars($q ?? '') ?>">
    <select class="form-select" name="type">
      <option value="posts" <?= $type==='posts'?'selected':''; ?>>Posts</option>
      <option value="users" <?= $type==='users'?'selected':''; ?>>Users</option>
      <option value="tags"  <?= $type==='tags'?'selected':''; ?>>Tags</option>
    </select>
    <input class="form-control" type="text" name="tag" placeholder="Filter by tag (slug)" value="<?= htmlspecialchars($tag ?? '') ?>" <?= $type!=='posts' ? 'disabled' : '' ?>>
    <input class="form-control" type="number" name="board_id" min="0" placeholder="Board ID" value="<?= (int)($board_id ?? 0) ?>" <?= $type!=='posts' ? 'disabled' : '' ?>>
    <button class="btn btn-primary">Search</button>
  </form>

  <?php if ($type === 'posts'): ?>
    <?php foreach (($results ?? []) as $r): ?>
      <div class="card mb-2">
        <div class="card-body">
          <a href="/post?id=<?= (int)$r['id'] ?>" class="h6 d-block mb-1"><?= htmlspecialchars($r['title']) ?></a>
          <div class="text-muted small mb-2">
            by <?= htmlspecialchars($r['author']) ?> • <?= htmlspecialchars($r['created_at']) ?>
          </div>
          <div class="mb-2"><?= nl2br(htmlspecialchars(mb_strimwidth($r['body'], 0, 220, '…'))) ?></div>
          <!-- Tag chips (if you want, render via an include that loads Tag::tagsForPost) -->
        </div>
      </div>
    <?php endforeach; ?>
  <?php elseif ($type === 'users'): ?>
    <?php foreach (($results ?? []) as $u): ?>
      <div class="card mb-2">
        <div class="card-body">
          <div class="h6 mb-1"><?= htmlspecialchars($u['username'] ?: $u['email']) ?></div>
          <div class="text-muted small">Joined <?= htmlspecialchars($u['created_at']) ?> • <?= htmlspecialchars($u['email']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: /* tags */ ?>
    <?php foreach (($results ?? []) as $t): ?>
      <div class="card mb-2">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <a href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>" class="h6 mb-1 d-block">#<?= htmlspecialchars($t['name']) ?></a>
            <div class="text-muted small">Used in <?= (int)$t['usage_count'] ?> posts</div>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">Filter posts</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Simple pager -->
  <nav class="mt-3">
    <ul class="pagination">
      <?php $prev = max(1, ($page ?? 1) - 1); $next = ($page ?? 1) + 1; ?>
      <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled':''; ?>"><a class="page-link" href="<?= '/search?'.http_build_query(['q'=>$q,'type'=>$type,'tag'=>$tag,'board_id'=>$board_id,'page'=>$prev]) ?>">Prev</a></li>
      <li class="page-item"><a class="page-link" href="<?= '/search?'.http_build_query(['q'=>$q,'type'=>$type,'tag'=>$tag,'board_id'=>$board_id,'page'=>$next]) ?>">Next</a></li>
    </ul>
  </nav>
</div>
