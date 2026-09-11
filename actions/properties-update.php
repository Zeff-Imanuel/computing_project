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

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing property ID']);
    exit();
}

// Get property
$properties = new PropertiesTable(new MySQL());
$property = $properties->getById($id);

if (!$property) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit();
}

// ✅ Permission check
$is_admin = ($auth->role_id == 3);
$is_agent = ($auth->role_id == 2);
$is_owner = ($property->user_id == $auth->id);

if (!$is_admin && !($is_agent && $is_owner)) {
    echo json_encode(['success' => false, 'message' => 'You can only edit your own properties']);
    exit();
}

// Get form data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$listing_type = $_POST['listing_type'] ?? 'sale';
$property_type = $_POST['property_type'] ?? 'house';
$bedrooms = $_POST['bedrooms'] ?? 0;
$bathrooms = $_POST['bathrooms'] ?? 0;
$area_sqft = $_POST['area_sqft'] ?? 0;
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zip = $_POST['zip'] ?? '';

// Admin-only fields
$status = $is_admin ? ($_POST['status'] ?? 'available') : $property->status;
$featured = $is_admin ? ($_POST['featured'] ?? 0) : $property->featured;

// Validate
if (empty($title) || empty($description) || empty($price) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

try {
    // Update property
    $stmt = (new MySQL())->connect()->prepare("
        UPDATE properties SET
            title = :title,
            description = :description,
            price = :price,
            bedrooms = :bedrooms,
            bathrooms = :bathrooms,
            area_sqft = :area_sqft,
            address = :address,
            city = :city,
            state = :state,
            zip = :zip,
            property_type = :property_type,
            listing_type = :listing_type,
            status = :status,
            featured = :featured,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id,
        ':title' => $title,
        ':description' => $description,
        ':price' => $price,
        ':bedrooms' => $bedrooms,
        ':bathrooms' => $bathrooms,
        ':area_sqft' => $area_sqft,
        ':address' => $address,
        ':city' => $city,
        ':state' => $state,
        ':zip' => $zip,
        ':property_type' => $property_type,
        ':listing_type' => $listing_type,
        ':status' => $status,
        ':featured' => $featured
    ]);

    // Handle new photos
    $uploaded_count = 0;

    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $upload_dir = __DIR__ . '/../images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $files = $_FILES['photos'];
        $file_count = count($files['name']);
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        $photo_table = new PropertyPhotosTable(new MySQL());

        // Check if property has existing photos
        $existing_photos = $photo_table->getByProperty($id);
        $has_main = false;
        foreach ($existing_photos as $ep) {
            if ($ep->is_primary) { $has_main = true; break; }
        }

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] !== 0) continue;
            if (!in_array($files['type'][$i], $allowed_types)) continue;

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $filename = time() . '_' . uniqid() . '_' . $i . '.' . $ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                // First photo of new batch is primary ONLY if no photos exist
                $is_primary = (!$has_main && $i === 0) ? 1 : 0;
                
                $photo_table->insert([
                    'property_id' => $id,
                    'photo_path' => $filename,
                    'is_primary' => $is_primary
                ]);
                
                // Update main property photo if this is the first photo
                if ($is_primary) {
                    $properties->updatePhoto($id, $filename);
                    $has_main = true;
                }
                
                $uploaded_count++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property updated successfully' . ($uploaded_count > 0 ? " with $uploaded_count new photo(s)!" : "!"),
        'id' => $id
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>