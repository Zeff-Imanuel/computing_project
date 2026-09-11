<?php
namespace Helpers;

class Auth
{
    static function check()
    {
        // ✅ Make sure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        HTTP::redirect("/index.php", "required=auth");
        exit();
    }
}
?>