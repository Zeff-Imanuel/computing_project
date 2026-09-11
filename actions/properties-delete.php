<?php
session_start();
include("../vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    echo json_encode(['success' => false]);
    exit();
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    // Get property to delete photos
    $properties = new PropertiesTable(new MySQL);
    $property = $properties->getById($id);
    
    if ($property && $property->photo) {
        $photo_path = __DIR__ . '/../images/' . $property->photo;
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    // Delete photos from property_photos table
    $photo_table = new PropertyPhotosTable(new MySQL);
    $photos = $photo_table->getByProperty($id);
    
    foreach ($photos as $photo) {
        $photo_path = __DIR__ . '/../images/' . $photo->photo_path;
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    $photo_table->deleteByProperty($id);
    
    // Delete property
    $properties->delete($id);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>