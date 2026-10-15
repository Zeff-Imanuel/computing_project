<?php
include("../vendor/autoload.php");

use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;

// ===== GET INPUT =====
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$role_id  = (int)($_POST['role_id'] ?? 1);

// ===== 1. CHECK REQUIRED FIELDS =====
if (empty($name) || empty($email) || empty($password)) {
    HTTP::redirect("/register.php", "error=missing_fields");
}

// ===== 2. NAME: letters and spaces only =====
if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
    HTTP::redirect("/register.php", "error=invalid_name");
}

// ===== 3. EMAIL: valid format =====
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    HTTP::redirect("/register.php", "error=invalid_email");
}

// ===== 4. PHONE: numbers, spaces, +, - only =====
if (!empty($phone) && !preg_match('/^[0-9+\s\-]+$/', $phone)) {
    HTTP::redirect("/register.php", "error=invalid_phone");
}

// ===== 5. ADDRESS: letters, numbers, basic punctuation =====
if (!empty($address) && !preg_match('/^[A-Za-z0-9\s,.\-\/]+$/', $address)) {
    HTTP::redirect("/register.php", "error=invalid_address");
}

// ===== 6. PASSWORD: at least 6 characters =====
if (strlen($password) < 6) {
    HTTP::redirect("/register.php", "error=weak_password");
}

// ===== 7. CHECK EMAIL DOESN'T ALREADY EXIST =====
$table = new UsersTable(new MySQL);

$existing = $table->findByEmail($email);
if ($existing) {
    HTTP::redirect("/register.php", "error=email_exists");
}

// ===== 8. INSERT USER =====
$table->insert([
    "name"     => $name,
    "email"    => $email,
    "phone"    => $phone,
    "address"  => $address,
    "password" => $password,
    "role_id"  => $role_id
]);

HTTP::redirect("/index.php", "account=created");
?>