<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

header('Content-Type: application/json');

$auth = Auth::check();
if ($auth->role_id != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit();
}

$table = new AppointmentsTable(new MySQL);
$result = $table->delete($id);

echo json_encode([
    'success' => $result,
    'message' => $result ? 'Deleted' : 'Failed'
]);
?>