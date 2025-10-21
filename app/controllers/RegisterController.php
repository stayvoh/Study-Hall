<?php
class RegisterController extends BaseController {

    public function showForm() {
        // Only guests should see this page
        require_guest();
        $this->render('register');
    }

    public function register() {
        require_guest();

        // CSRF check
        if (!csrf_check($_POST['csrf'] ?? '')) {
            $this->render('register', ['error' => 'Invalid CSRF token']);
            return;
        }

        $email    = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if ($password !== $confirm) {
            $this->render('register', ['error' => 'Passwords do not match']);
            return;
        }

        $userModel = new User($this->db);

        try {
            if ($userModel->create($email, $password)) {
                // Option A: log them in immediately
                $_SESSION['uid'] = $this->db->lastInsertId();
                header("Location: /dashboard");
                exit;

                // Option B: redirect to login instead
                // header("Location: /login");
                // exit;
            } else {
                $this->render('register', ['error' => 'Could not create user']);
            }
        } catch (PDOException $e) {
            $this->render('register', ['error' => 'Email already exists']);
        }
    }
}
