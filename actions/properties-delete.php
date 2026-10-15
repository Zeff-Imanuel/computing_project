<?php
session_start();
header('Content-Type: application/json');
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;

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
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

try {
    $properties = new PropertiesTable(new MySQL());
    $property = $properties->getById($id);

    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Not found']);
        exit;
    }

    // Delete main photo
    if (!empty($property->photo)) {
        $file = __DIR__ . '/../images/' . $property->photo;
        if (file_exists($file)) unlink($file);
    }

    // Delete all photos from property_photos table + disk
    $photo_table = new PropertyPhotosTable(new MySQL());
    $photos = $photo_table->getByProperty($id);
    foreach ($photos as $photo) {
        $f = __DIR__ . '/../images/' . $photo->photo_path;
        if (file_exists($f)) unlink($f);
    }
    $photo_table->deleteByProperty($id);

    // Delete property row
    $properties->delete($id);

    echo json_encode(['success' => true, 'message' => 'Deleted']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}