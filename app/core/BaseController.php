<?php
class BaseController {
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection(); // your PDO wrapper
    }

    protected function render(string $view, array $data = []): void {
        extract($data);
        $path = __DIR__ . '/../views/' . $view;
        if (!str_ends_with($view, '.php')) {
            $path .= '.php';
        }
        require $path;
    }
}
?>