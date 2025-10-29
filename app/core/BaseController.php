<?php
class BaseController {

    protected $db;
    
    public function __construct() {
        $this->db = Database::getConnection(); // your PDO wrapper
    }

    protected function render(string $view, array $data = []): void {
    if (isset($_SESSION['uid'])) {
        $profileModel = new Profile($this->db);
        $data['currentUser']   = $profileModel->getProfileByUserId($_SESSION['uid']);
        $data['profilePicUrl'] = '/get_image.php?id=' . (int)$_SESSION['uid'];  // pass this too
    }

    extract($data, EXTR_SKIP);

    $path = __DIR__ . '/../views/' . $view;
    if (!str_ends_with($view, '.php')) {
        $path .= '.php';
    }
    require $path;
}
  protected function checkProfanity(array $fields): ?string {
          require_once __DIR__ . '/../helpers/profanityFilter.php';
        if (checkFieldsForProfanity($fields)) {
            return "Input contains inappropriate language.";
        }
        return null;
    }
    
}
?>