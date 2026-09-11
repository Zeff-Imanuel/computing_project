<?php
session_start();  // ✅ MUST BE FIRST
include("../vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Helpers\HTTP;

$table = new UsersTable(new MySQL);

$email = $_POST['email'];
$password = $_POST['password'];

$user = $table->find($email, $password);

if ($user) {
    // Check if suspended
    if ($user->suspended == 1) {
        HTTP::redirect("/index.php", "suspended=account");
    }

    // ✅ Set session
    $_SESSION['user'] = $user;

    // Role-based redirect
    if ($user->role_id == 3) {
        HTTP::redirect("/admin.php");
    } elseif ($user->role_id == 2) {
        HTTP::redirect("/profile.php");
    } else {
        HTTP::redirect("/profile.php");
    }
} else {
    HTTP::redirect("/index.php", "incorrect=login");
}
?>