<div class="container my-4" style="max-width:900px;">
  <h4 class="mb-3">All Tags</h4>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach (($tags ?? []) as $t): ?>
      <a class="badge text-bg-light border" href="/tag/<?= urlencode($t['slug']) ?>">
        #<?= htmlspecialchars($t['name']) ?>
        <?php if (isset($t['usage_count'])): ?>
          <span class="text-muted">(<?= (int)$t['usage_count'] ?>)</span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
