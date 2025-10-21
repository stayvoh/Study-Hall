<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ForgotPasswordController extends BaseController {

    public function showForm() {
        require_guest();
        $this->render('forgot');
    }

    public function sendReset() {
        require_guest();

        // CSRF check
        if (!csrf_check($_POST['csrf'] ?? '')) {
            $this->render('forgot', ['error' => 'Invalid CSRF token']);
            return;
        }

        $email = trim(strtolower($_POST['email'] ?? ''));
        $stmt = $this->db->prepare("SELECT id FROM user_account WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate token + expiry
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->db->prepare("
                REPLACE INTO password_reset (user_id, token, expires_at) 
                VALUES (:uid, :token, :exp)
            ")->execute([
                'uid'   => $user['id'],
                'token' => hash('sha256', $token),
                'exp'   => $expiry
            ]);

            // Send email with PHPMailer (using Mailtrap for dev)
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.mailtrap.io'; // Mailtrap host
                $mail->SMTPAuth = true;
                $mail->Username = '4c5b9266a98d94';
                $mail->Password = '3a03f667810150';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@studyhall.local', 'StudyHall');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $resetLink = "http://localhost:8080/reset?token=$token";
                $mail->Body    = "Click this link to reset your password: <a href=\"$resetLink\">Reset Password</a>";
                $mail->AltBody = "Copy and paste this link in your browser: $resetLink";

                $mail->send();
                $this->render('forgot', ['message' => 'A reset link has been sent to your email.']);
            } catch (Exception $e) {
                $this->render('forgot', ['error' => 'Email could not be sent.']);
            }

        } else {
            $this->render('forgot', ['error' => 'Email not found']);
        }
    }
}
