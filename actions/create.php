<?php
include("../vendor/autoload.php");

use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;

// Check required fields
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password'])) {
    HTTP::redirect("/register.php", "error=missing_fields");
}

$table = new UsersTable(new MySQL);

// Check if email already exists
$existing = $table->findByEmail($_POST['email']);
if ($existing) {
    HTTP::redirect("/register.php", "error=email_exists");
}

// Insert user
$table->insert([
    "name" => $_POST['name'],
    "email" => $_POST['email'],
    "phone" => $_POST['phone'],
    "address" => $_POST['address'],
    "password" => $_POST['password'],
    "role_id" => $_POST['role_id'] ?? 1
]);

HTTP::redirect("/index.php", "account=created");
?>