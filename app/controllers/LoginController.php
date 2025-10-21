<?php
class LoginController extends BaseController {

    public function showForm() {
        // Only guests should see the login page
        require_guest();
        $this->render('login');
    }

    public function login() {
        require_guest();

        // CSRF check
        if (!csrf_check($_POST['csrf'] ?? '')) {
            $this->render('login', ['error' => 'Invalid CSRF token']);
            return;
        }

        $email = trim(strtolower($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';

        $userModel = new User($this->db);
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['uid'] = $user['id'];

            // Handle "Remember me"
            if (!empty($_POST['remember'])) {
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

                // Store hashed token in DB
                $stmt = $this->db->prepare(
                    "UPDATE user_account SET remember_token = :token, remember_expiry = :exp WHERE id = :id"
                );
                $stmt->execute([
                    'token' => hash('sha256', $token),
                    'exp'   => $expiry,
                    'id'    => $user['id']
                ]);

                // Store plain token in cookie
                setcookie('remember_me', $user['id'] . ':' . $token, [
                    'expires'  => time() + 60 * 60 * 24 * 30, // 30 days
                    'path'     => '/',
                    'httponly' => true,
                    'secure'   => false, // set true when using HTTPS
                    'samesite' => 'Lax',
                ]);
            }

            header("Location: /dashboard");
            exit;

        } else {
            $this->render('login', ['error' => 'Invalid credentials']);
        }
    }
}
