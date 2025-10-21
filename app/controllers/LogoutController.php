<?php
class LogoutController extends BaseController {
    public function index() {
        session_destroy();
        setcookie('remember_me', '', time() - 3600, '/'); // clear remember me cookie
        header("Location: /login");
        exit;
    }
}
?>