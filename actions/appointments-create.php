<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

function out($arr) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode($arr);
    exit;
}

// 1. Auth
try {
    $auth = Auth::check();
} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Please login first']);
}

// 2. Read input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    // Try form data as fallback
    $data = $_POST;
}

$property_id      = (int)($data['property_id'] ?? 0);
$agent_id         = (int)($data['agent_id'] ?? 0);
$appointment_date = trim((string)($data['appointment_date'] ?? ''));
$message          = trim((string)($data['message'] ?? ''));

// 3. Validate
if (!$property_id || !$agent_id || !$appointment_date) {
    out(['success' => false, 'message' => 'Missing required fields']);
}

if (strtotime($appointment_date) === false) {
    out(['success' => false, 'message' => 'Invalid date format']);
}

if (strtotime($appointment_date) < time()) {
    out(['success' => false, 'message' => 'Date must be in the future']);
}

if ((int)$auth->id === $agent_id) {
    out(['success' => false, 'message' => 'You cannot book your own property']);
}

// 4. Insert
try {
    $table = new AppointmentsTable(new MySQL());
    $result = $table->insert([
        'property_id'      => $property_id,
        'user_id'          => $auth->id,
        'agent_id'         => $agent_id,
        'appointment_date' => $appointment_date,
        'message'          => $message,
        'status'           => 'pending'
    ]);

    if ($result) {
        out(['success' => true, 'message' => 'Appointment booked successfully!']);
    } else {
        out(['success' => false, 'message' => 'Insert failed']);
    }
} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}