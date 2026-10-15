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

$property_id = $_POST['property_id'] ?? 0;
$user_id     = $_POST['user_id'] ?? 0;
$amount      = $_POST['amount'] ?? 0;
$method      = $_POST['payment_method'] ?? 'cash';
$pay_date    = $_POST['payment_date'] ?? '';
$status      = $_POST['status'] ?? 'pending';
$notes       = $_POST['notes'] ?? '';

if (!$property_id || !$user_id || !$amount || !$pay_date) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (floatval($amount) <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
    exit;
}

if (!in_array($method, ['cash', 'bank', 'card', 'online'])) $method = 'cash';
if (!in_array($status, ['pending', 'completed', 'failed', 'refunded'])) $status = 'pending';

try {
    $payments = new PaymentsTable(new MySQL());

    $id = $payments->insert([
        'property_id'    => $property_id,
        'user_id'        => $user_id,
        'amount'         => $amount,
        'payment_method' => $method,
        'payment_date'   => date('Y-m-d H:i:s', strtotime($pay_date)),
        'status'         => $status,
        'notes'          => $notes,
    ]);

    if ($id) {
        echo json_encode(['success' => true, 'message' => 'Payment recorded', 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert payment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}