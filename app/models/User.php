<?php
class User {
    private $db;
    public ?int $lastInsertId = null;
    public function __construct($db) {
        $this->db = $db;
    }
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM user_account WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    public function create(string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO user_account (email, password_hash) VALUES (:email, :hash)");
           $success = $stmt->execute(['email' => $email, 'hash' => $hash]);
        if ($success) {
            $this->lastInsertId = (int) $this->db->lastInsertId();
        }
        return $success;
    }

}
?>