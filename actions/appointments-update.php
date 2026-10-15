<?php
session_start();
header('Content-Type: application/json');
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

try {
    $auth = Auth::check();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($auth->role_id < 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id     = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$table = new AppointmentsTable(new MySQL());

// Agents can only update their own appointments
if ($auth->role_id == 2) {
    $apt = $table->getById($id);
    if (!$apt || $apt->agent_id != $auth->id) {
        echo json_encode(['success' => false, 'message' => 'You cannot edit this appointment']);
        exit;
    }
}

$ok = $table->updateStatus($id, $status);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Status updated' : 'Update failed'
]);