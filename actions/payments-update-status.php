<?php
session_start();
header('Content-Type: application/json');
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PaymentsTable;

try {
    $auth = Auth::check();
    if ($auth->role_id != 3) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Auth failed']);
    exit;
}

$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$payments = new PaymentsTable(new MySQL());
$ok = $payments->updateStatus($id, $status);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Status updated' : 'Invalid status'
]);