<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

// 1) Validate board id
$boardId = (int)($_GET['id'] ?? 0);
if ($boardId <= 0) {
    http_response_code(400);
    echo "Invalid board id";
    exit;
}

// 2) Load board
$stmt = $db->prepare("SELECT id, name, description, created_at FROM board WHERE id = :id");
$stmt->bindValue(':id', $boardId, PDO::PARAM_INT);
$stmt->execute();
$board = $stmt->fetch();
if (!$board) {
    http_response_code(404);
    echo "Board not found";
    exit;
}

// 3) Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// 4) Posts (with author + tags) for this board
$sql = "
    SELECT
        p.id, p.title, p.body, p.created_at,
        COALESCE(up.username, ua.email) AS author,
        GROUP_CONCAT(DISTINCT CONCAT(t.name, ':', t.slug) SEPARATOR '|') AS tag_blob
    FROM post p
    JOIN user_account ua ON ua.id = p.created_by
    LEFT JOIN user_profile up ON up.user_id = ua.id
    LEFT JOIN post_tag pt ON pt.post_id = p.id
    LEFT JOIN tag t       ON t.id = pt.tag_id
    WHERE p.board_id = :bid
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT :lim OFFSET :off
";
$stmt = $db->prepare($sql);
$stmt->bindValue(':bid', $boardId, PDO::PARAM_INT);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll() ?: [];

$posts = array_map(function($r){
    $tags = [];
    if (!empty($r['tag_blob'])) {
        foreach (explode('|', $r['tag_blob']) as $pair) {
            [$name, $slug] = array_pad(explode(':', $pair, 2), 2, '');
            if ($name !== '' && $slug !== '') $tags[] = ['name'=>$name, 'slug'=>$slug];
        }
    }
    $r['tags']    = $tags;
    $r['excerpt'] = mb_strimwidth((string)($r['body'] ?? ''), 0, 200, '…');
    return $r;
}, $rows);

// 5) Simple page builder that preserves query string
$build = function (int $n) use ($boardId): string {
    $q = $_GET;
    $q['id']   = (int)$boardId;
    $q['page'] = max(1, (int)$n);
    return '/board?' . http_build_query($q);
};

// === FOLLOWING LOGIC ===
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../models/BoardFollow.php';
require_once __DIR__ . '/../models/User.php';

$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
$isFollowing   = $uid ? BoardFollow::isFollowing($uid, $boardId) : false;
$followerCount = BoardFollow::followersCount($boardId);

// ===== View =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($board['name']) ?> · Study Hall</title>
  <?php
  $themeInit = __DIR__ . '/theme-init.php';
  if (is_file($themeInit)) include $themeInit;
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="bg-body">
<?php
  $hdr = __DIR__ . '/header.php';
  if (is_file($hdr)) include $hdr;
?>

<section class="py-4">
  <div class="container" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="d-flex align-items-center gap-3">
        <h3 class="mb-0"><?= htmlspecialchars($board['name']) ?></h3>
        <span class="badge text-bg-light border" title="Followers">
          <i class="bi bi-people me-1"></i><?= (int)$followerCount ?>
        </span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <?php if ($uid): ?>
          <form method="post" action="/boards/<?= (int)$board['id'] ?>/<?= $isFollowing ? 'unfollow' : 'follow' ?>">
            <button type="submit"
                    class="btn btn-sm <?= $isFollowing ? 'btn-outline-danger' : 'btn-outline-primary' ?>">
              <i class="bi <?= $isFollowing ? 'bi-heartbreak' : 'bi-heart' ?>"></i>
              <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
            </button>
          </form>
        <?php endif; ?>

        <a class="btn btn-sm btn-outline-secondary" href="/dashboard">Back to boards</a>
        <a class="btn btn-sm btn-orange" href="/post/create?b=<?= (int)$board['id'] ?>">New post</a>
      </div>
    </div>

    <?php if (!empty($board['description'])): ?>
      <p class="text-muted mb-4"><?= htmlspecialchars($board['description']) ?></p>
    <?php endif; ?>

    <?php if ($posts): ?>
      <div class="list-group shadow-sm">
        <?php foreach ($posts as $p): ?>
          <div class="list-group-item list-group-item-action position-relative">
            <div class="d-flex w-100 justify-content-between">
              <h6 class="mb-1"><?= htmlspecialchars($p['title']) ?></h6>
              <small class="text-muted">
                <?php
                  if (!empty($p['created_at'])) {
                      $dt = new DateTime($p['created_at']);
                      echo htmlspecialchars($dt->format('F j, Y g:i A'));
                  }
                  ?>
            </small>
            </div>

            <?php if (!empty($p['excerpt'])): ?>
              <div class="text-muted mb-2"><?= htmlspecialchars($p['excerpt']) ?></div>
            <?php endif; ?>

            <?php if (!empty($p['tags'])): ?>
              <div class="d-flex flex-wrap gap-1 mt-0">
                <?php foreach ($p['tags'] as $t): ?>
                  <a class="badge rounded-pill text-bg-light border me-1 text-decoration-none"
                     href="/search?type=posts&tag=<?= urlencode($t['slug']) ?>">#<?= htmlspecialchars($t['name']) ?></a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="small text-muted mt-1">by <?= htmlspecialchars($p['author'] ?? 'User') ?></div>

            <!-- Stretched link keeps whole item clickable without nesting anchors -->
            <a href="/post?id=<?= (int)$p['id'] ?>" class="stretched-link" aria-label="Open post"></a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php
        $prev = max(1, $page - 1);
        $next = $page + 1;
      ?>
      <nav class="mt-3">
        <ul class="pagination">
          <li class="page-item <?= $page<=1?'disabled':''; ?>"><a class="page-link" href="<?= $build($prev) ?>">Prev</a></li>
          <li class="page-item"><a class="page-link" href="<?= $build($next) ?>">Next</a></li>
        </ul>
      </nav>
    <?php else: ?>
      <div class="alert alert-light border">No posts yet in this board</div>
      <?php
        $prev = max(1, $page - 1);
        $next = $page + 1;
      ?>
      <nav class="mt-3">
        <ul class="pagination">
          <li class="page-item <?= $page<=1?'disabled':''; ?>">
            <a class="page-link" href="<?= $build($prev) ?>">Prev</a>
          </li>
          <li class="page-item">
            <a class="page-link" href="<?= $build($next) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
