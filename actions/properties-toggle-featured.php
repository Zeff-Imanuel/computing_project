<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    echo json_encode(['success' => false]);
    exit();
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false]);
    exit();
}

$table = new PropertiesTable(new MySQL);
$result = $table->toggleFeatured($id);

echo json_encode(['success' => $result]);
?>