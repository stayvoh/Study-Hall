<?php
class BaseController {
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection(); // your PDO wrapper
    }

    protected function render(string $view, array $data = []): void {
        extract($data);
        require __DIR__ . '/../views/' . $view . '.php';
    }
}
?>