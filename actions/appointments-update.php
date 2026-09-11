<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

header('Content-Type: application/json');

$auth = Auth::check();
if ($auth->role_id < 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

if (!$id || !in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid params']);
    exit();
}

$table = new AppointmentsTable(new MySQL);
$result = $table->updateStatus($id, $status);

echo json_encode([
    'success' => $result,
    'message' => $result ? 'Status updated' : 'Failed'
]);
?>