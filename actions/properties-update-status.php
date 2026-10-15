<?php
session_start();
header('Content-Type: application/json');
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

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

$properties = new PropertiesTable(new MySQL());
$property   = $properties->getById($id);

if (!$property) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

// Agents can only update their own properties
if ($auth->role_id == 2 && $property->user_id != $auth->id) {
    echo json_encode(['success' => false, 'message' => 'You can only update your own properties']);
    exit;
}

$ok = $properties->updateStatus($id, $status);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Status updated' : 'Invalid status change for this listing type'
]);