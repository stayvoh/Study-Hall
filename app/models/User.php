<?php
class User {
    private $db;
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
        return $stmt->execute(['email' => $email, 'hash' => $hash]);
    }

}
?>