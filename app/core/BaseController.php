<?php
class BaseController {
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection(); // your PDO wrapper
    }

    protected function render(string $view, array $data = []): void {
        if (isset($_SESSION['uid'])) {
        $profileModel = new Profile($this->db);
        $data['currentUser'] = $profileModel->getProfileByUserId($_SESSION['uid']);
      require __DIR__ . "/../views/header.php";         
    }               
        extract($data);
      
        require __DIR__ . '/../views/' . $view . '.php';
   
   
   
   
    }
    
}
?>