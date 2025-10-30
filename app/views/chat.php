<?php
$following = $following ?? [];
$chatWithName = $chatWithName ?? 'Select a user';
$chatWithId = $chatWithId ?? 0;
$userAvatar = $userAvatar ?? 'default-avatar.png';
$currentUser = $currentUser ?? ['username' => 'You'];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'light') ?>">
<head>
  <meta charset="UTF-8">
  <title>Chat with <?= htmlspecialchars($chatWithName) ?> · Study Hall</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/css/custom.css" rel="stylesheet">

  <style>
    body.bg-body { background-color: #fafafa; }

    /* Following List */
    .following-list .list-group-item {
      border-radius: 12px;
      margin-bottom: 0.5rem;
      transition: background 0.2s;
    }
    .following-list .list-group-item:hover {
      background-color: #f0f0f0;
    }

    /* Chat container */
    .chat-container {
      height: 70vh;
      overflow-y: auto;
      padding: 1rem;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .message {
      max-width: 70%;
      padding: 0.6rem 1rem;
      border-radius: 20px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1);
      word-break: break-word;
    }

    .message.outgoing {
      background-color: #0d6efd;
      color: #fff;
      margin-left: auto;
      text-align: right;
    }

    .message.incoming {
      background-color: #f0f0f0;
      margin-right: auto;
    }

    .message-time {
      font-size: 0.7rem;
      color: #6c757d;
      margin-top: 2px;
    }

    #chat-input { border-radius: 50px; }
  </style>
</head>
<body class="bg-body text-body">

<?php
$hdr = __DIR__ . '/header.php';
if (is_file($hdr)) include $hdr;
?>

<div class="container mt-5" style="max-width: 900px;">
  <div class="row g-3">
    <!-- Following List -->
    <div class="col-md-4">
      <div class="card shadow-sm p-3 following-list">
        <h6 class="fw-bold mb-3">Following</h6>
        <div class="list-group list-group-flush">
          <?php foreach ($following as $user): ?>
            <a href="?user=<?= $user['user_id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="get_image.php?id=<?= $user['user_id'] ?>" class="rounded-circle me-2" width="40" height="40">
              <?= htmlspecialchars($user['username']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Chat Area -->
    <div class="col-md-8 d-flex flex-column">
      <div class="card shadow-sm flex-grow-1 d-flex flex-column">
        <!-- Header -->
        <div class="card-header d-flex align-items-center bg-light" style="border-bottom: 1px solid #ddd;">
          <img src="<?= $userAvatar ?>" class="rounded-circle me-2" width="40" height="40">
          <h6 class="mb-0"><?= htmlspecialchars($chatWithName) ?></h6>
        </div>

        <!-- Chat Messages -->
        <div id="chat-container" class="chat-container flex-grow-1">
          <?php if (!$chatWithId): ?>
            <p class="text-muted text-center mt-5">Select a user to start chatting</p>
          <?php endif; ?>
        </div>

        <!-- Input -->
        <?php if ($chatWithId): ?>
        <div class="card-footer bg-light border-top p-2">
          <form id="chat-form" class="d-flex align-items-center">
            <input type="text" id="chat-input" class="form-control me-2" placeholder="Message…" required>
            <button type="submit" class="btn btn-primary rounded-pill px-3">Send</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const chatContainer = document.getElementById('chat-container');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatWithId = <?= (int)$chatWithId ?>;

function appendMessage(text, outgoing = true) {
  const div = document.createElement('div');
  div.classList.add('message', outgoing ? 'outgoing' : 'incoming');
  div.textContent = text;

  const time = document.createElement('div');
  time.classList.add('message-time');
  const now = new Date();
  time.textContent = now.getHours() + ':' + String(now.getMinutes()).padStart(2,'0');
  div.appendChild(time);

  const wrapper = document.createElement('div');
  wrapper.classList.add('d-flex', outgoing ? 'justify-content-end' : '');
  wrapper.appendChild(div);

  chatContainer.appendChild(wrapper);
  chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Handle send
chatForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = chatInput.value.trim();
  if (!message) return;
  
  // Append locally
  appendMessage(message, true);
  chatInput.value = '';

  // Send to backend
  await fetch('/chat/send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ toUserId: chatWithId, message })
  });
});
</script>

</body>
</html>
