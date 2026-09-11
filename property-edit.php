<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;

$auth = Auth::check();

$id = $_GET['id'] ?? 0;

if (!$id) {
    HTTP::redirect("/home.php", "error=invalid");
}

$properties_table = new PropertiesTable(new MySQL);
$property = $properties_table->getById($id);

if (!$property) {
    HTTP::redirect("/home.php", "error=not_found");
}

// ✅ Permission check
$is_admin = ($auth->role_id == 3);
$is_agent = ($auth->role_id == 2);
$is_owner = ($property->user_id == $auth->id);

if (!$is_admin && !($is_agent && $is_owner)) {
    HTTP::redirect("/home.php", "error=unauthorized");
}

// Get photos
$photo_table = new PropertyPhotosTable(new MySQL);
$photos = $photo_table->getByProperty($id);

$is_logged_in = true;
$user = $auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Myanmar Real Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a56db;
            --primary-dark: #1e40af;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --gray: #64748b;
            --light-gray: #f1f5f9;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.15);
            --radius: 12px;
        }

        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }

        .page-header {
            background: var(--secondary);
            padding: 50px 0 40px;
            text-align: center;
            color: white;
        }
        .page-header h1 { font-size: 2.5rem; font-weight: 800; }
        .page-header h1 span { color: var(--accent); }
        .page-header p { color: rgba(255,255,255,0.7); }

        .form-container {
            background: white;
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin: 30px 0;
        }
        .form-container .form-label { font-weight: 600; }
        .form-container .form-control,
        .form-container .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.3s;
        }
        .form-container .form-control:focus,
        .form-container .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }
        .form-container .btn-submit {
            background: var(--primary);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
        }
        .form-container .btn-submit:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }
        .form-container .btn-cancel {
            background: var(--gray);
            color: white;
            padding: 12px 35px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }
        .form-container .btn-cancel:hover {
            background: #475569;
            color: white;
        }

        /* ===== PHOTO MANAGEMENT ===== */
        .photo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .photo-item {
            position: relative;
            width: 150px;
            height: 130px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            background: var(--light-gray);
        }
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-item .primary-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(34, 197, 94, 0.95);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            text-align: center;
            padding: 4px;
            text-transform: uppercase;
        }
        .photo-item .delete-photo {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .photo-item .delete-photo:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .preview-item {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px dashed #22c55e;
            background: var(--light-gray);
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .new-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #22c55e;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            text-align: center;
            padding: 3px;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: 100%;
        }
        .toast-message {
            padding: 16px 24px;
            border-radius: var(--radius);
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast-message.show { transform: translateX(0); }
        .toast-message.success { background: #22c55e; }
        .toast-message.error { background: #ef4444; }
        .toast-message.info { background: var(--primary); }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 9997;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.show { display: flex; }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .badge-info {
            background: #eff6ff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.8rem; }
            .form-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <div id="toastContainer" class="toast-container"></div>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <section class="page-header">
        <div class="container">
            <h1>Edit <span>Property</span></h1>
            <p>Update property information</p>
        </div>
    </section>

    <section class="container">
        <div class="form-container">

            <!-- Info Badge -->
            <div class="text-center">
                <?php if ($is_admin): ?>
                    <span class="badge-info">
                        <i class="fas fa-shield-alt"></i> Editing as Admin
                    </span>
                <?php else: ?>
                    <span class="badge-info">
                        <i class="fas fa-user-tie"></i> Editing your property
                    </span>
                <?php endif; ?>
            </div>

            <form id="editPropertyForm" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $property->id ?>">

                <div class="row g-3">
                    <!-- Title -->
                    <div class="col-md-12">
                        <label class="form-label">Property Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" 
                               value="<?= htmlspecialchars($property->title) ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="col-md-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" rows="4" required><?= htmlspecialchars($property->description) ?></textarea>
                    </div>

                    <!-- Price / Listing / Type -->
                    <div class="col-md-4">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" 
                               value="<?= $property->price ?>" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Listing Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="listing_type" required>
                            <option value="sale" <?= $property->listing_type == 'sale' ? 'selected' : '' ?>>For Sale</option>
                            <option value="rent" <?= $property->listing_type == 'rent' ? 'selected' : '' ?>>For Rent</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Property Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="property_type" required>
                            <option value="house" <?= $property->property_type == 'house' ? 'selected' : '' ?>>House</option>
                            <option value="apartment" <?= $property->property_type == 'apartment' ? 'selected' : '' ?>>Apartment</option>
                            <option value="condo" <?= $property->property_type == 'condo' ? 'selected' : '' ?>>Condo</option>
                            <option value="villa" <?= $property->property_type == 'villa' ? 'selected' : '' ?>>Villa</option>
                            <option value="land" <?= $property->property_type == 'land' ? 'selected' : '' ?>>Land</option>
                            <option value="commercial" <?= $property->property_type == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                        </select>
                    </div>

                    <!-- Bed / Bath / Area -->
                    <div class="col-md-4">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" class="form-control" id="bedrooms" 
                               value="<?= $property->bedrooms ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" class="form-control" id="bathrooms" 
                               value="<?= $property->bathrooms ?>" step="0.5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Area (sqft)</label>
                        <input type="number" class="form-control" id="area_sqft" 
                               value="<?= $property->area_sqft ?>">
                    </div>

                    <!-- Address -->
                    <div class="col-md-12">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" 
                               value="<?= htmlspecialchars($property->address) ?>" required>
                    </div>

                    <!-- City / State / Zip -->
                    <div class="col-md-4">
                        <label class="form-label">City <span class="text-danger">*</span></label>
                        <select class="form-select" id="city" required>
                            <option value="Yangon" <?= $property->city == 'Yangon' ? 'selected' : '' ?>>Yangon</option>
                            <option value="Mandalay" <?= $property->city == 'Mandalay' ? 'selected' : '' ?>>Mandalay</option>
                            <option value="Naypyidaw" <?= $property->city == 'Naypyidaw' ? 'selected' : '' ?>>Naypyidaw</option>
                            <option value="Bago" <?= $property->city == 'Bago' ? 'selected' : '' ?>>Bago</option>
                            <option value="Mawlamyine" <?= $property->city == 'Mawlamyine' ? 'selected' : '' ?>>Mawlamyine</option>
                            <option value="Taunggyi" <?= $property->city == 'Taunggyi' ? 'selected' : '' ?>>Taunggyi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State/Region</label>
                        <input type="text" class="form-control" id="state" 
                               value="<?= htmlspecialchars($property->state) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" id="zip" 
                               value="<?= htmlspecialchars($property->zip) ?>">
                    </div>

                    <!-- Status (Admin only) -->
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" <?= !$is_admin ? 'disabled' : '' ?>>
                            <option value="available" <?= $property->status == 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="pending" <?= $property->status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="sold" <?= $property->status == 'sold' ? 'selected' : '' ?>>Sold</option>
                            <option value="rented" <?= $property->status == 'rented' ? 'selected' : '' ?>>Rented</option>
                        </select>
                        <?php if (!$is_admin): ?>
                            <small class="text-muted">Only admin can change status</small>
                        <?php endif; ?>
                    </div>

                    <!-- Featured (Admin only) -->
                    <div class="col-md-6">
                        <label class="form-label d-block">Featured</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="featured" 
                                   <?= $property->featured ? 'checked' : '' ?>
                                   <?= !$is_admin ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="featured">
                                Feature this property
                            </label>
                        </div>
                        <?php if (!$is_admin): ?>
                            <small class="text-muted">Only admin can feature properties</small>
                        <?php endif; ?>
                    </div>

                    <!-- Existing Photos -->
                    <?php if (!empty($photos)): ?>
                        <div class="col-12">
                            <hr>
                            <label class="form-label">
                                <i class="fas fa-images text-primary"></i> 
                                Existing Photos (<?= count($photos) ?>)
                            </label>
                            <div class="photo-grid">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="photo-item" id="photo-<?= $photo->id ?>">
                                        <img src="/project/images/<?= $photo->photo_path ?>" alt="Photo">
                                        <?php if ($photo->is_primary): ?>
                                            <div class="primary-badge"><i class="fas fa-star"></i> Main</div>
                                        <?php endif; ?>
                                        <button type="button" class="delete-photo" 
                                                data-photoid="<?= $photo->id ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Add New Photos -->
                    <div class="col-12">
                        <hr>
                        <label class="form-label">
                            <i class="fas fa-plus-circle text-success"></i> 
                            Add New Photos
                        </label>
                        <input type="file" class="form-control" id="new_photos" 
                               accept="image/*" multiple>
                        <small class="text-muted">Select multiple new photos to add</small>
                        <div class="photo-grid" id="newPhotoPreview"></div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-6 mt-4">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                    <div class="col-md-6 mt-4">
                        <a href="<?= $is_admin ? 'admin-properties.php' : 'agent-properties.php' ?>" 
                           class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const isAdmin = <?= $is_admin ? 'true' : 'false' ?>;

            // ===== NEW PHOTO PREVIEW =====
            document.getElementById('new_photos').addEventListener('change', function() {
                const preview = document.getElementById('newPhotoPreview');
                preview.innerHTML = '';
                const files = this.files;
                
                for (let i = 0; i < files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${event.target.result}">
                            <div class="new-badge">NEW</div>
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(files[i]);
                }
            });

            // ===== DELETE EXISTING PHOTO =====
            document.querySelectorAll('.delete-photo').forEach(btn => {
                btn.addEventListener('click', function() {
                    const photoId = this.dataset.photoid;
                    const photoDiv = document.getElementById('photo-' + photoId);

                    if (!confirm('Delete this photo permanently?')) return;

                    showLoading();
                    this.disabled = true;

                    fetch(`/project/actions/properties-delete-photo.php?id=${photoId}`)
                        .then(res => res.json())
                        .then(data => {
                            hideLoading();
                            if (data.success) {
                                photoDiv.remove();
                                showToast('Photo deleted!', 'success');
                            } else {
                                showToast(data.message || 'Failed to delete', 'error');
                                this.disabled = false;
                            }
                        })
                        .catch(() => {
                            hideLoading();
                            showToast('Network error', 'error');
                            this.disabled = false;
                        });
                });
            });

            // ===== TOAST =====
            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    info: 'fa-info-circle'
                };
                const toast = document.createElement('div');
                toast.className = `toast-message ${type}`;
                toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${message}`;
                container.appendChild(toast);
                setTimeout(() => toast.classList.add('show'), 50);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 500);
                }, 3500);
            }

            function showLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
            function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

            // ===== FORM SUBMIT =====
            document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData();
                formData.append('id', document.querySelector('input[name="id"]').value);
                formData.append('title', document.getElementById('title').value.trim());
                formData.append('description', document.getElementById('description').value.trim());
                formData.append('price', document.getElementById('price').value.trim());
                formData.append('listing_type', document.getElementById('listing_type').value);
                formData.append('property_type', document.getElementById('property_type').value);
                formData.append('bedrooms', document.getElementById('bedrooms').value.trim());
                formData.append('bathrooms', document.getElementById('bathrooms').value.trim());
                formData.append('area_sqft', document.getElementById('area_sqft').value.trim());
                formData.append('address', document.getElementById('address').value.trim());
                formData.append('city', document.getElementById('city').value);
                formData.append('state', document.getElementById('state').value.trim());
                formData.append('zip', document.getElementById('zip').value.trim());

                // Admin-only fields
                if (isAdmin) {
                    formData.append('status', document.getElementById('status').value);
                    formData.append('featured', document.getElementById('featured').checked ? 1 : 0);
                }

                // New photos
                const photoInput = document.getElementById('new_photos');
                if (photoInput && photoInput.files.length > 0) {
                    for (let i = 0; i < photoInput.files.length; i++) {
                        formData.append('photos[]', photoInput.files[i]);
                    }
                }

                showLoading();
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                fetch('/project/actions/properties-update.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';

                    if (data.success) {
                        showToast('Property updated successfully! 🎉', 'success');
                        setTimeout(() => {
                            window.location.href = isAdmin ? 'admin-properties.php' : 'agent-properties.php';
                        }, 1500);
                    } else {
                        showToast(data.message || 'Error updating property', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                    showToast('Network error', 'error');
                });
            });
        });
    </script>
</body>
</html>