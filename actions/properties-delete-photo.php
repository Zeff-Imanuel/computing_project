<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;
use Helpers\Auth;

try {
    $auth = Auth::check();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Auth failed']);
    exit();
}

$photo_id = $_GET['id'] ?? 0;

if (!$photo_id) {
    echo json_encode(['success' => false, 'message' => 'Missing photo ID']);
    exit();
}

$db = (new MySQL())->connect();

// Get photo + property owner
$stmt = $db->prepare("
    SELECT pp.*, p.user_id as property_owner
    FROM property_photos pp
    LEFT JOIN properties p ON pp.property_id = p.id
    WHERE pp.id = :id
");
$stmt->execute([':id' => $photo_id]);
$photo = $stmt->fetch();

if (!$photo) {
    echo json_encode(['success' => false, 'message' => 'Photo not found']);
    exit();
}

// Permission check
$is_admin = ($auth->role_id == 3);
$is_owner = ($photo->property_owner == $auth->id);

if (!$is_admin && !$is_owner) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete this photo']);
    exit();
}

try {
    // Delete file
    $file = __DIR__ . '/../images/' . $photo->photo_path;
    if (file_exists($file)) {
        unlink($file);
    }

    // Delete from database
    $stmt = $db->prepare("DELETE FROM property_photos WHERE id = :id");
    $stmt->execute([':id' => $photo_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Photo deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>