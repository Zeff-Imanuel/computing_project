<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

header('Content-Type: application/json');

try {
    $auth = Auth::check();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

$property_id = $data['property_id'] ?? 0;
$agent_id = $data['agent_id'] ?? 0;
$appointment_date = $data['appointment_date'] ?? '';
$message = $data['message'] ?? '';

// Validate
if (!$property_id || !$agent_id || !$appointment_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Check date is in the future
if (strtotime($appointment_date) < time()) {
    echo json_encode(['success' => false, 'message' => 'Appointment date must be in the future']);
    exit();
}

// Prevent user from booking their own property
if ($auth->id == $agent_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot book your own property']);
    exit();
}

try {
    $table = new AppointmentsTable(new MySQL);
    $result = $table->insert([
        'property_id' => $property_id,
        'user_id' => $auth->id,
        'agent_id' => $agent_id,
        'appointment_date' => $appointment_date,
        'message' => $message
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to book appointment'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>