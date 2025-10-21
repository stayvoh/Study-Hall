<?php
class ResetPasswordController extends BaseController {

    public function showForm() {
        require_guest();
        $token = $_GET['token'] ?? '';
        $this->render('reset', ['token' => $token]);
    }

    public function reset() {
        require_guest();

        // CSRF check
        if (!csrf_check($_POST['csrf'] ?? '')) {
            $this->render('reset', ['error' => 'Invalid CSRF token', 'token' => $_POST['token'] ?? '']);
            return;
        }

        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($token) || empty($password)) {
            $this->render('reset', ['error' => 'Missing fields', 'token' => $token]);
            return;
        }

        // Validate token
        $stmt = $this->db->prepare("
            SELECT user_id, expires_at 
            FROM password_reset 
            WHERE token = :token
        ");
        $stmt->execute(['token' => hash('sha256', $token)]);
        $row = $stmt->fetch();

        if ($row && $row['expires_at'] > date('Y-m-d H:i:s')) {
            // Token valid → update password
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->prepare("
                UPDATE user_account SET password_hash = :hash WHERE id = :id
            ")->execute([
                'hash' => $hash,
                'id'   => $row['user_id']
            ]);

            // Delete used token
            $this->db->prepare("DELETE FROM password_reset WHERE user_id = :id")
                     ->execute(['id' => $row['user_id']]);

            header("Location: /login");
            exit;
        } else {
            $this->render('reset', ['error' => 'Invalid or expired reset link', 'token' => $token]);
        }
    }
}
