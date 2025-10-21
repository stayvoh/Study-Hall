<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => false, // set true if serving over HTTPS
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Auto-login via "remember me" cookie
 */
if (empty($_SESSION['uid']) && !empty($_COOKIE['remember_me'])) {
    [$uid, $token] = explode(':', $_COOKIE['remember_me'], 2);

    try {
        require_once __DIR__ . '/Database.php';
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT remember_token, remember_expiry FROM user_account WHERE id = :id");
        $stmt->execute(['id' => $uid]);
        $row = $stmt->fetch();

        if ($row &&
            $row['remember_expiry'] > date('Y-m-d H:i:s') &&
            hash_equals($row['remember_token'], hash('sha256', $token))) {
            $_SESSION['uid'] = $uid;
        }
    } catch (Exception $e) {
        // Fail silently if DB not available
    }
}

/** CSRF token */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

/** Auth helpers */
function require_guest(): void {
    if (!empty($_SESSION['uid'])) {
        header('Location: /dashboard');
        exit;
    }
}

function require_login(): void {
    if (empty($_SESSION['uid'])) {
        header('Location: /login');
        exit;
    }
}
?>