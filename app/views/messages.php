<?php
/** @var array $conversations */
/** @var array|null $activeConversation */
/** @var array $messages */
/** @var int $loggedInUserId */

// Ensure session and current user info so header can use them without trying to access $this->db
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../models/Profile.php';
try {
    $profileModel = new Profile(\Database::getConnection());
    $currentUser = $profileModel->getProfileByUserId($_SESSION['uid'] ?? 0) ?: ['username' => 'User'];
    $profilePicUrl = $_SESSION['uid'] ? '/get_image.php?id=' . $_SESSION['uid'] : '/images/default-avatar.jpg';
} catch (Throwable $e) {
    // fallback minimal values
    $currentUser = ['username' => 'User'];
    $profilePicUrl = '/images/default-avatar.jpg';
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'light') ?>">
<head>
<meta charset="UTF-8">
<title>Messages - Study Hall</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/css/custom.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: system-ui, sans-serif; }
.container-messages { display: flex; height: 90vh; max-width: 1100px; margin: 20px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
.sidebar { width: 30%; border-right: 1px solid #ddd; overflow-y: auto; }
.chat-section { flex: 1; display: flex; flex-direction: column; }
.chat-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; }
.message-list { flex: 1; overflow-y: auto; padding: 15px; }

/* Dark mode background adjustments */
[data-bs-theme="dark"] body {
    background-color: #1a1a1a !important;
}
/* Layout for messages list: use flex column + gap so bubbles don't visually connect */
.message-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 15px;
}

/* Ensure each message sizes to its content and appears as a distinct bubble */
.message {
    width: auto;
    display: block;
    padding: 10px 14px;
    border-radius: 18px;
    margin: 0; /* gap handled by .message-list */
    box-shadow: 0 1px 0 rgba(0,0,0,0.02) inset;
}

/* Dark mode aware message styles */
.message { 
    padding: 8px 12px; 
    border-radius: 20px !important; 
    margin-bottom: 8px; 
    max-width: 75%; 
    word-wrap: break-word; 
}
.sent { 
    background-color: var(--bs-primary); 
    color: var(--bs-white);
    align-self: flex-end;
}
.received { 
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
    align-self: flex-start; 
}

/* Dark mode aware meta info */
.message .meta { font-size: 0.85rem; color: #495057; margin-bottom: 6px; }
.message .meta small { 
    color: var(--bs-secondary-color); 
    font-size: 0.75rem; 
    margin-left: 6px; 
}
.sent .meta { color: var(--bs-white); }
.sent .meta small { color: rgba(255,255,255,0.75); }

.message .body { white-space: pre-wrap; }

/* Container dark mode */
[data-bs-theme="dark"] .container-messages {
    background: #242424;
    border: 1px solid var(--bs-border-color);
}
[data-bs-theme="dark"] .sidebar {
    border-color: var(--bs-border-color);
    background: #2d2d2d;
}
[data-bs-theme="dark"] .chat-header {
    border-color: var(--bs-border-color);
    background: #2d2d2d;
    color: var(--bs-white);
}

[data-bs-theme="dark"] .message.received {
    background-color: #333333;
    color: var(--bs-white);
}

[data-bs-theme="dark"] .message.sent {
    background-color: #0d6efd;
    color: var(--bs-white);
}

/* Transitions for theme switching */
.container-messages,
.sidebar,
.chat-header,
.message {
    transition: background-color 0.3s, border-color 0.3s, color 0.3s;
}
.chat-form { 
    display: flex; 
    border-top: 1px solid #ddd; 
    padding: 10px; 
    background: transparent;
}
.chat-form input { 
    flex: 1; 
    border: none; 
    border-radius: 20px !important; 
    padding: 10px; 
    background: #f1f3f5; 
}
.chat-form button { 
    border: none; 
    background: #0d6efd; 
    color: white; 
    border-radius: 20px; 
    padding: 0 16px; 
    margin-left: 8px; 
}
.chat-form input:focus { 
    outline: none; 
}

/* Dark mode chat form */
[data-bs-theme="dark"] .chat-form {
    border-color: var(--bs-border-color);
    background: #2d2d2d;
}
[data-bs-theme="dark"] .chat-form input {
    background: #333333;
    color: white;
    border: 1px solid #444;
}
[data-bs-theme="dark"] .chat-form input::placeholder {
    color: #999;
}

.conversation { 
    padding: 12px 16px; 
    border-bottom: 1px solid #eee; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    position: relative;
    transition: background-color 0.2s ease;
}

.conversation:hover { 
    background-color: rgba(13, 110, 253, 0.1); 
}

/* brief highlight when a search moves an item to the top */
.search-highlight {
    box-shadow: 0 0 0 3px rgba(13,110,253,0.12) inset;
    transition: box-shadow 0.3s ease;
}

.conversation-avatar {
    position: relative;
    margin-right: 12px;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    min-width: 0; /* enables text-truncate to work */
    display: flex;
    flex-direction: column;
}

[data-bs-theme="dark"] .conversation:hover {
    background-color: rgba(13, 110, 253, 0.2);
}

[data-bs-theme="dark"] .conversation {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.unread-dot {
    position: absolute;
    top: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background: #dc3545;
    border-radius: 50%;
    border: 2px solid var(--bs-body-bg);
}

.unread-count {
    background: #dc3545;
    color: white;
    font-size: 12px;
    padding: 1px 6px;
    border-radius: 10px;
    margin-left: 6px;
}

.conversation.has-unread {
    background: rgba(13, 110, 253, 0.05);
}

/* Notification bell: handled centrally in header.php to avoid duplicates */
.conversation:hover { background: #f8f9fa; }
.conversation img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; }

.sidebar-divider {
    padding: 8px 16px;
    font-size: 0.875rem;
    color: #6c757d;
    background-color: rgba(0,0,0,0.03);
    border-bottom: 1px solid #dee2e6;
    border-top: 1px solid #dee2e6;
    margin-top: 8px;
}

[data-bs-theme="dark"] .sidebar-divider {
    background-color: rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.1);
    color: #adb5bd;
}
</style>
</head>
<body>
<?php $hdr = __DIR__ . '/header.php'; if (is_file($hdr)) include $hdr; ?>

<div class="container-messages">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-content">
            <div class="p-2">
                <input id="conversation-search" type="search" class="form-control" placeholder="Search conversations or users..." aria-label="Search conversations">
            </div>
            <?php
            // Sort conversations: active conversations first, then potential new ones
            $activeConvs = array_filter($conversations, fn($c) => !empty($c['id']));
            $potentialConvs = array_filter($conversations, fn($c) => empty($c['id']));
            
            // Display active conversations first
            foreach ($activeConvs as $conv): 
                $partnerId = $conv['partner_id'] ?? '';
                $partnerName = $conv['partner_name'] ?? '';
                $profilePic = $conv['profile_picture'] ?? null;
                $mimeType = $conv['mime_type'] ?? 'image/png';
                $unreadCount = (int)($conv['unread_count'] ?? 0);
            ?>
             <div class="conversation <?= $unreadCount > 0 ? 'has-unread' : '' ?>" 
                 tabindex="0"
                 data-user-id="<?= htmlspecialchars($partnerId) ?>" 
                 data-conversation-id="<?= htmlspecialchars($conv['id'] ?? 0) ?>"
                 onclick="startConversation(this)">
                    <div class="conversation-avatar">
                        <img src="<?= !empty($profilePic) ? 'data:' . htmlspecialchars($mimeType) . ';base64,' . base64_encode($profilePic) : '/get_image.php?id=' . htmlspecialchars($partnerId) ?>" 
                             onerror="this.src='/images/default-avatar.jpg'" 
                             alt="<?= htmlspecialchars($partnerName) ?>'s profile picture"
                             loading="lazy">
                        <?php if ($unreadCount > 0): ?>
                            <span class="unread-dot" title="<?= $unreadCount ?> unread message(s)"></span>
                        <?php endif; ?>
                    </div>
                    <div class="conversation-info">
                        <strong><?= htmlspecialchars($partnerName) ?></strong>
                        <?php if ($unreadCount > 0): ?>
                            <span class="unread-count ms-2"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($potentialConvs)): ?>
                <div class="sidebar-divider">Followed Users</div>
                <?php foreach ($potentialConvs as $conv): 
                    $partnerId = $conv['partner_id'] ?? '';
                    $partnerName = $conv['partner_name'] ?? '';
                    $profilePic = $conv['profile_picture'] ?? null;
                    $mimeType = $conv['mime_type'] ?? 'image/png';
                ?>
                    <div class="conversation" tabindex="0" data-user-id="<?= htmlspecialchars($partnerId) ?>" onclick="startConversation(this)">
                        <div class="conversation-avatar">
                            <img src="<?= !empty($profilePic) ? 'data:' . htmlspecialchars($mimeType) . ';base64,' . base64_encode($profilePic) : '/get_image.php?id=' . htmlspecialchars($partnerId) ?>" 
                                 onerror="this.src='/images/default-avatar.jpg'" 
                                 alt="<?= htmlspecialchars($partnerName) ?>'s profile picture"
                                 loading="lazy">
                        </div>
                        <div class="conversation-info">
                            <strong><?= htmlspecialchars($partnerName) ?></strong>
                            <small class="text-muted d-block">Start a conversation</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Section -->
    <div class="chat-section">
        <?php if ($activeConversation): ?>
            <div class="chat-header">
                Chatting with <?= htmlspecialchars($activeConversation['partner_name'] ?? '') ?>
            </div>
            <div id="messages" class="message-list">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['sender_id'] == $loggedInUserId ? 'sent' : 'received' ?>" data-id="<?= $msg['id'] ?>" data-sender="<?= $msg['sender_id'] ?>">
                        <div class="meta">
                            <strong><?= $msg['sender_id'] == $loggedInUserId ? 'You' : htmlspecialchars($activeConversation['partner_name'] ?? '') ?></strong>
                            <small class="message-time" data-created-at="<?= htmlspecialchars($msg['created_at']) ?>"><?php echo htmlspecialchars(date('g:ia', strtotime($msg['created_at']))); ?></small>
                        </div>
                        <div class="body"><?= htmlspecialchars($msg['body']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form id="chat-form" class="chat-form">
                <input type="hidden" name="conversation_id" value="<?= $activeConversation['id'] ?>">
                <input type="hidden" name="partner_id" value="<?= $activeConversation['partner_id'] ?>">
                <input type="text" name="body" id="chat-input" placeholder="Message..." required>
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100">
                <h5>Select a conversation</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>

const messagesDiv = document.getElementById('messages');
const form = document.getElementById('chat-form');
const input = document.getElementById('chat-input');
let lastId = <?= count($messages) ? end($messages)['id'] : 0 ?>;
let conversationId = <?= $activeConversation ? $activeConversation['id'] : 0 ?>;
// track rendered message ids to avoid duplicates from optimistic append + poll
const renderedIds = new Set(<?= json_encode(array_map(fn($m)=>(int)$m['id'],$messages)) ?>);
const partnerName = <?= json_encode($activeConversation['partner_name'] ?? '') ?>;

// Append message to DOM (safe-guard if messages area missing)
function appendMessage(msg) {
    if (!messagesDiv) return;
    // if message has an id we've already rendered, skip it
    if (msg.id && renderedIds.has(parseInt(msg.id, 10))) return;

    const isSent = (msg.sender_id == <?= $loggedInUserId ?>);
    const div = document.createElement('div');
    div.className = 'message ' + (isSent ? 'sent' : 'received');
    if (msg.id) {
        div.dataset.id = msg.id;
        renderedIds.add(parseInt(msg.id, 10));
        lastId = Math.max(lastId, parseInt(msg.id, 10));
    }

    const meta = document.createElement('div');
    meta.className = 'meta';
    const name = document.createElement('strong');
    name.textContent = isSent ? 'You' : (msg.sender_name || partnerName || '');
    const time = document.createElement('small');
    time.className = 'message-time';
    if (msg.created_at) {
        time.dataset.createdAt = msg.created_at;
        time.textContent = formatServerTime(msg.created_at);
    } else {
        // fallback to local now
        const now = new Date();
        time.textContent = now.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit', hour12: true});
    }
    meta.appendChild(name);
    meta.appendChild(time);

    const body = document.createElement('div');
    body.className = 'body';
    body.textContent = msg.body;

    div.appendChild(meta);
    div.appendChild(body);
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

    // Send message (wait for server ack before showing)
if (form) {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        input.disabled = true;
        if (submitButton) submitButton.disabled = true;

        try {
            const res = await fetch('/messages/send', { 
                method: 'POST', 
                body: formData
            });
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            if (!data || !data.success) {
                console.error('Send failed', data);
                alert(data && data.error ? data.error : 'Failed to send message');
            } else {
                // Add the new message to the UI. Use server timestamp when available so displayed time matches persisted time.
                appendMessage({ 
                    sender_id: <?= $loggedInUserId ?>, 
                    sender_name: 'You', 
                    body, 
                    id: data.id,
                    created_at: data.created_at || new Date().toISOString()
                });
                if (data.conversation_id) conversationId = data.conversation_id;
                
                // Clear and re-enable the input
                input.value = '';
                input.focus();
                // Ask header to refresh unread indicator (centralized)
                if (typeof checkUnreadMessages === 'function') checkUnreadMessages();
            }
        } catch (err) {
            console.error('Network error sending message:', err);
            alert('Unable to send message. Please check your connection and try again.');
        } finally {
            input.disabled = false;
            if (submitButton) submitButton.disabled = false;
            input.value = '';
            input.focus();
        }
    });
}

    // Long polling with better performance and cleanup
let isPolling = false;
let pollTimeout = null;

// Unread indicator handled in header.php; no local implementation here to avoid duplicates.

async function poll() {
    if (!conversationId || !isPolling) return;
    
    try {
        const res = await fetch(`/messages/poll?conversation_id=${conversationId}&last_id=${lastId}`);
        const data = await res.json();
        if (Array.isArray(data)) {
            data.forEach(msg => {
                // Update lastId to the newest message
                if (msg.id > lastId) lastId = msg.id;
                
                // Format the message with sender name
                const formattedMsg = {
                    ...msg,
                    sender_name: msg.sender_id == <?= $loggedInUserId ?> ? 'You' : (msg.sender_name || 'User')
                };
                appendMessage(formattedMsg);
            });
            
            // Update unread status in header if needed
            if (data.some(msg => msg.sender_id !== <?= $loggedInUserId ?>)) {
                if (typeof checkUnreadMessages === 'function') checkUnreadMessages();
            }
        }
    } catch (err) { 
        console.error('Poll error', err);
    }
    
    if (isPolling) {
        pollTimeout = setTimeout(poll, 3000); // Poll every 3 seconds
    }
}

// Header manages the global unread indicator. Ensure it's refreshed on load.
if (typeof checkUnreadMessages === 'function') checkUnreadMessages();
if (conversationId) {
    isPolling = true;
    poll();
}

// Convert server timestamps to localized display for all existing messages
function formatServerTime(ts) {
    if (!ts) return '';
    // If timestamp is in 'YYYY-MM-DD HH:MM:SS' format, convert to ISO assuming server time is UTC
    let iso = ts;
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(ts)) {
        iso = ts.replace(' ', 'T') + 'Z';
    }
    try {
        const d = new Date(iso);
        return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
    } catch (e) {
        return ts;
    }
}

function updateMessageTimesOnLoad() {
    document.querySelectorAll('.message-time[data-created-at]').forEach(el => {
        const ts = el.dataset.createdAt;
        if (ts) el.textContent = formatServerTime(ts);
    });
}

updateMessageTimesOnLoad();

// Cleanup when leaving the page
window.addEventListener('beforeunload', () => {
    isPolling = false;
    if (pollTimeout) {
        clearTimeout(pollTimeout);
    }
});

// Start conversation or redirect to existing
function startConversation(el) {
    const userId = el.getAttribute('data-user-id');

    // Remove conversation-specific unread dot immediately in the UI
    try {
        el.querySelector('.unread-dot')?.remove();
    } catch (_) {}
    if (typeof checkUnreadMessages === 'function') checkUnreadMessages();

    // Use fetch to get or create conversation ID first
    fetch(`/messages/getOrCreate?partner_id=${userId}`)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            if (data && data.conversation_id) {
                // Instead of redirecting, reload the page to maintain state
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('conversation', data.conversation_id);
                window.location.href = currentUrl.toString();
            } else {
                console.error('Invalid response from server', data);
                alert('Could not start conversation');
            }
        })
        .catch(err => {
            console.error('Error starting conversation', err);
            alert('Error starting conversation');
        });
}
</script>

<script>
// Sidebar search filter (client-side) with reorder/restore behavior
const searchInput = document.getElementById('conversation-search');
if (searchInput) {
    const sidebarContent = document.querySelector('.sidebar .sidebar-content');
    const searchWrapper = sidebarContent.querySelector('div.p-2');
    const divider = sidebarContent.querySelector('.sidebar-divider');
    // capture original order of conversation items
    const originalOrder = Array.from(sidebarContent.querySelectorAll('.conversation')).map(el => ({
        userId: el.dataset.userId || '',
        convId: el.dataset.conversationId || ''
    }));
    const originalDividerIndex = divider ? Array.from(sidebarContent.children).indexOf(divider) : -1;

    const debounce = (fn, wait = 200) => {
        let t = null;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    };

    const filterConversations = () => {
        const q = searchInput.value.trim().toLowerCase();
        const items = Array.from(sidebarContent.querySelectorAll('.conversation'));

        if (!q) {
            // restore original order and show all
            originalOrder.forEach(o => {
                const el = sidebarContent.querySelector(`.conversation[data-user-id="${o.userId}"][data-conversation-id="${o.convId}"]`);
                if (el) sidebarContent.appendChild(el);
            });
            // restore divider position
            if (divider) {
                if (originalDividerIndex >= 0 && originalDividerIndex < sidebarContent.children.length) {
                    sidebarContent.insertBefore(divider, sidebarContent.children[originalDividerIndex]);
                } else {
                    sidebarContent.appendChild(divider);
                }
            }
            items.forEach(item => item.style.display = '');
            return;
        }

        items.forEach(item => {
            const name = (item.querySelector('.conversation-info strong')?.textContent || '').toLowerCase();
            const uid  = (item.dataset.userId || '').toString();
            const match = name.includes(q) || uid.includes(q);
            item.style.display = match ? '' : 'none';
        });

        // move first visible match to top (right after search box)
        const firstVisible = sidebarContent.querySelector('.conversation:not([style*="display: none"])');
        if (firstVisible && searchWrapper) {
            sidebarContent.insertBefore(firstVisible, searchWrapper.nextSibling);
        }

        // hide divider if no potential convs visible
        if (divider) {
            const anyPotentialVisible = Array.from(sidebarContent.querySelectorAll('.conversation'))
                .some(i => i.style.display !== 'none' && (i.dataset.conversationId === undefined || i.dataset.conversationId === '0' || i.dataset.conversationId === ''));
            divider.style.display = anyPotentialVisible ? '' : 'none';
        }
    };

    searchInput.addEventListener('input', debounce(filterConversations, 200));

    // Pressing Enter while focused on the search input will apply the filter and move/focus the first visible match
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            // run the filter immediately
            filterConversations();
            const first = sidebarContent.querySelector('.conversation:not([style*="display: none"])');
            if (first) {
                // visually and accessibly focus the first match
                try { first.focus(); } catch (__) {}
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // highlight briefly
                first.classList.add('search-highlight');
                setTimeout(() => first.classList.remove('search-highlight'), 900);
            }
        }
    });
}
</script>

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
