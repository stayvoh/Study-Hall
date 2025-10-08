<?php
declare(strict_types=1);

/**
 * Session + CSRF helpers
 */
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'httponly' => true,
  'secure'   => false, // set true behind HTTPS
  'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

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
    header('Location: /index.php'); exit;
  }
}
function require_login(): void {
  if (empty($_SESSION['uid'])) {
    header('Location: /login.php'); exit;
  }
}
