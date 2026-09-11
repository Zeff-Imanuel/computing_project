<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

// Return JSON response
header('Content-Type: application/json');

// Check if admin
try {
    $auth = Auth::check();
    if ($auth->role_id != 3) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Auth failed']);
    exit();
}

// Get parameters
$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

// Validate
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing property ID']);
    exit();
}

if (!in_array($status, ['available', 'pending', 'sold', 'rented'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $status]);
    exit();
}

// Update status
try {
    $table = new PropertiesTable(new MySQL);
    $result = $table->updateStatus($id, $status);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated to ' . $status,
            'id' => $id,
            'status' => $status
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update status. Check if property exists.',
            'id' => $id,
            'status' => $status
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>