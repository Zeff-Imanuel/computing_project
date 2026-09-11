<?php
include("../vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Helpers\HTTP;
use Helpers\Auth;

$auth = Auth::check();

// Create photos directory if not exists
if (!is_dir("photos")) {
    mkdir("photos", 0777, true);
}

$name = $_FILES['photo']['name'];
$type = $_FILES['photo']['type'];
$tmp_name = $_FILES['photo']['tmp_name'];
$size = $_FILES['photo']['size'];

// Allowed types
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($type, $allowed)) {
    HTTP::redirect("/profile.php", "error=type");
}

// Max size: 2MB
if ($size > 2000000) {
    HTTP::redirect("/profile.php", "error=size");
}

// Generate unique filename
$ext = pathinfo($name, PATHINFO_EXTENSION);
$new_name = time() . '_' . uniqid() . '.' . $ext;

if (move_uploaded_file($tmp_name, "photos/$new_name")) {
    $table = new UsersTable(new MySQL);
    $table->updatePhoto($auth->id, $new_name);
    
    // Update session
    $_SESSION['user']->photo = $new_name;
    
    HTTP::redirect("/profile.php", "success=photo");
} else {
    HTTP::redirect("/profile.php", "error=upload");
}
?>