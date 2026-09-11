<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;

$user = Auth::check();
if ($user->role_id < 2) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Myanmar Real Estate</title>
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
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
        }
        .page-header h1 span { color: var(--accent); }
        .page-header p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
        }

        .form-container {
            background: white;
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin: 30px 0;
        }
        .form-container .form-label {
            font-weight: 600;
        }
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
        .form-container .form-control::placeholder {
            color: #94a3b8;
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
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .form-container .btn-cancel:hover {
            background: #475569;
        }

        /* ✅ PHOTO PREVIEW */
        .preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            background: var(--light-gray);
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .primary-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(34, 197, 94, 0.9);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            text-align: center;
            padding: 3px;
            text-transform: uppercase;
        }

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
        .toast-message.success { background: #22c55e; border-left: 4px solid #16a34a; }
        .toast-message.error { background: #ef4444; border-left: 4px solid #dc2626; }
        .toast-message.info { background: var(--primary); border-left: 4px solid #1d4ed8; }

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

        .footer {
            background: #0f172a;
            color: rgba(255,255,255,0.7);
            padding: 40px 0 20px;
            margin-top: 40px;
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
            <h1>Add <span>Property</span></h1>
            <p>List your property for sale or rent</p>
        </div>
    </section>

    <section class="container">
        <div class="form-container">
            <form id="addPropertyForm" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Property Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" placeholder="e.g. Luxury Villa in Golden Valley" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" rows="4" placeholder="Describe your property..." required></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" placeholder="250000" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Listing Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="listing_type" required>
                            <option value="sale">For Sale</option>
                            <option value="rent">For Rent</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Property Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="property_type" required>
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="condo">Condo</option>
                            <option value="villa">Villa</option>
                            <option value="land">Land</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" class="form-control" id="bedrooms" placeholder="3" value="3">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" class="form-control" id="bathrooms" placeholder="2" step="0.5" value="2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Area (sqft)</label>
                        <input type="number" class="form-control" id="area_sqft" placeholder="1800" value="1800">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" placeholder="123 Main Street" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">City <span class="text-danger">*</span></label>
                        <select class="form-select" id="city" required>
                            <option value="Yangon">Yangon</option>
                            <option value="Mandalay">Mandalay</option>
                            <option value="Naypyidaw">Naypyidaw</option>
                            <option value="Bago">Bago</option>
                            <option value="Mawlamyine">Mawlamyine</option>
                            <option value="Taunggyi">Taunggyi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State/Region</label>
                        <input type="text" class="form-control" id="state" placeholder="Yangon Region" value="Yangon Region">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" id="zip" placeholder="11001">
                    </div>

                    <!-- MULTIPLE PHOTOS UPLOAD -->
                    <div class="col-md-12">
                        <label class="form-label">
                            Property Photos 
                            <span class="text-muted small">(Select multiple)</span>
                        </label>
                        <input type="file" class="form-control" id="photos" accept="image/*" multiple>
                        <small class="text-muted">You can select multiple photos at once. First photo will be the main image.</small>
                        <div class="preview-images" id="photoPreview"></div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="featured" value="1">
                            <label class="form-check-label fw-semibold" for="featured">
                                <i class="fas fa-star text-warning"></i> Feature this property (Admin only)
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-plus-circle"></i> Add Property
                        </button>
                    </div>
                    <div class="col-md-12 mt-2">
                        <a href="home.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ===== PHOTO PREVIEW =====
            document.getElementById('photos').addEventListener('change', function(e) {
                const preview = document.getElementById('photoPreview');
                preview.innerHTML = '';
                const files = this.files;
                
                for (let i = 0; i < files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${event.target.result}">
                            ${i === 0 ? '<div class="primary-badge">Main</div>' : ''}
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(files[i]);
                }
            });

            // ===== TOAST =====
            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                if (!container) { alert(message); return; }
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    info: 'fa-info-circle',
                    warning: 'fa-exclamation-triangle'
                };
                const toast = document.createElement('div');
                toast.className = `toast-message ${type}`;
                toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${message}`;
                container.appendChild(toast);
                setTimeout(() => toast.classList.add('show'), 50);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 500);
                }, 4000);
            }

            function showLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
            function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

            // ===== FORM SUBMIT =====
            const form = document.getElementById('addPropertyForm');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData();
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
                formData.append('featured', document.getElementById('featured').checked ? 1 : 0);

                // Multiple photos
                const photoInput = document.getElementById('photos');
                if (photoInput && photoInput.files.length > 0) {
                    for (let i = 0; i < photoInput.files.length; i++) {
                        formData.append('photos[]', photoInput.files[i]);
                    }
                }

                // Validation
                const title = document.getElementById('title').value.trim();
                const description = document.getElementById('description').value.trim();
                const price = document.getElementById('price').value.trim();
                const address = document.getElementById('address').value.trim();

                if (!title || !description || !price || !address) {
                    showToast('Please fill in all required fields.', 'error');
                    return;
                }

                showLoading();
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

                fetch('/project/actions/propertiescreate.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-plus-circle"></i> Add Property';

                    if (data.success) {
                        showToast(data.message || 'Property added successfully! 🎉', 'success');
                        form.reset();
                        document.getElementById('photoPreview').innerHTML = '';
                        
                        // ✅ ROLE-BASED REDIRECT
                        setTimeout(() => {
                            <?php if ($user->role_id == 3): ?>
                                // Admin → Admin Properties
                                window.location.href = 'admin-properties.php';
                            <?php else: ?>
                                // Agent → Profile Page
                                window.location.href = 'profile.php';
                            <?php endif; ?>
                        }, 2000);
                    } else {
                        showToast(data.message || 'Something went wrong.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-plus-circle"></i> Add Property';
                    showToast('Network error. Please check your connection.', 'error');
                });
            });
        });
    </script>
</body>
</html>