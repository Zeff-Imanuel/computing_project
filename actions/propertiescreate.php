<?php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;
use Helpers\Auth;

// Check if logged in and is agent or admin
try {
    $user = Auth::check();
    if ($user->role_id < 2) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Auth failed: ' . $e->getMessage()]);
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
$featured = $_POST['featured'] ?? 0;

// Validate
if (empty($title) || empty($description) || empty($price) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

try {
    // ============================================
    // STEP 1: Insert property
    // ============================================
    $properties = new PropertiesTable(new MySQL());
    
    $property_id = $properties->insert([
        'user_id' => $user->id,
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'listing_type' => $listing_type,
        'property_type' => $property_type,
        'bedrooms' => $bedrooms,
        'bathrooms' => $bathrooms,
        'area_sqft' => $area_sqft,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'featured' => ($user->role_id == 3) ? $featured : 0,
        'status' => 'available',
        'photo' => null
    ]);

    if (!$property_id) {
        echo json_encode(['success' => false, 'message' => 'Failed to add property.']);
        exit();
    }

    // ============================================
    // STEP 2: Handle multiple photos
    // ============================================
    $uploaded_photos = [];
    $photo_table = new PropertyPhotosTable(new MySQL());

    // ✅ Check for 'photos[]' array
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        
        // Create upload directory
        $upload_dir = __DIR__ . '/../images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $files = $_FILES['photos'];
        $file_count = count($files['name']);

        // Allowed file types
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        for ($i = 0; $i < $file_count; $i++) {
            
            // Skip if file has error
            if ($files['error'][$i] !== 0) {
                continue;
            }

            // Check file type
            if (!in_array($files['type'][$i], $allowed_types)) {
                continue;
            }

            // Generate unique filename
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $filename = time() . '_' . uniqid() . '_' . $i . '.' . $ext;
            $filepath = $upload_dir . $filename;

            // Move uploaded file
            if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                
                // First photo is the main one
                $is_primary = ($i === 0) ? 1 : 0;
                
                // Save to database
                $photo_table->insert([
                    'property_id' => $property_id,
                    'photo_path' => $filename,
                    'is_primary' => $is_primary
                ]);
                
                $uploaded_photos[] = $filename;

                // Update property's main photo (first one)
                if ($i === 0) {
                    $properties->updatePhoto($property_id, $filename);
                }
            }
        }
    }

    // ============================================
    // STEP 3: Return success
    // ============================================
    echo json_encode([
        'success' => true,
        'message' => 'Property added successfully with ' . count($uploaded_photos) . ' photo(s)!',
        'property_id' => $property_id,
        'photos_count' => count($uploaded_photos)
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>