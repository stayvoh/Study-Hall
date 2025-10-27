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

        $email= trim(strtolower($_POST['email'] ?? ''));     
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $username =trim($_POST['userName'] ?? '');
        $bio      =trim($_POST['bio'] ?? null);

        if ($password !== $confirm) {
            $this->render('register', ['error' => 'Passwords do not match']);
            return;
        }
         $profilePic = null;
        $mimeType   = null;

        if (!empty($_FILES['profile_picture']['tmp_name'])) {
            $profilePic = file_get_contents($_FILES['profile_picture']['tmp_name']);
            $mimeType   = mime_content_type($_FILES['profile_picture']['tmp_name']);
        }

        $userModel = new User($this->db);
        $profileModel = new Profile($this->db);

        try {
             $this->db->beginTransaction();
            //creates a user and checks if it was actually created
            $success = $userModel->create($email, $password);
            if (!$success) {
                throw new Exception("Could not create user account");
            }
            $userId = $userModel->lastInsertId;
            if (!$profileModel->create($userId, $username, $profilePic, $mimeType, $bio)) {
                throw new Exception("Could not create user profile");
            }

            $this->db->commit();

        
            $_SESSION['uid'] = $userId;
            header("Location: /dashboard");
            exit;
        } catch (Exception $e) {
            $this->render('register', ['error' => $e->getMessage()]);
        }catch(PDOException $e){
            $this->db->rollBack();
            if (str_contains($e->getMessage(), 'uq_user_email')) {
                $this->render('register', ['error' => 'Email already exists']);
            } elseif (str_contains($e->getMessage(), 'uq_profile_username')) {
                $this->render('register', ['error' => 'Username already taken']);
            } else {
                $this->render('register', ['error' => 'Database error: ' . $e->getMessage()]);
            }
        }
    }
}
?>