<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\MessagesTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$properties_table = new PropertiesTable(new MySQL);
$messages_table = new MessagesTable(new MySQL);

// Get all properties
$properties = $properties_table->getAllWithAgent();

// Get stats
$property_stats = $properties_table->getStats();

// Get unread messages
$unread_count = $messages_table->getUnreadCount();

$is_admin = ($auth->role_id == 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - KKG Property Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --gray: #64748b;
            --light-gray: #f1f5f9;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }

        .navbar-custom {
            background: var(--secondary) !important;
            padding: 12px 0;
            box-shadow: var(--shadow-lg);
        }
        .navbar-custom .brand {
            font-size: 1.3rem;
            font-weight: 700;
            color: white !important;
            text-decoration: none;
        }
        .navbar-custom .brand i { color: var(--accent); }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.7) !important;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .navbar-custom .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
        }
        .navbar-custom .nav-link.active {
            color: white !important;
            background: rgba(37, 99, 235, 0.5);
        }
        .user-badge {
            background: #ef4444;
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            padding: 20px 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .stat-card .number { font-size: 2rem; font-weight: 700; color: var(--secondary); }
        .stat-card .label { color: var(--gray); font-size: 0.85rem; }
        .stat-card.blue { border-color: var(--primary); }
        .stat-card.green { border-color: #22c55e; }
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.red { border-color: #ef4444; }
        .stat-card.teal { border-color: #06b6d4; }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-primary-custom:hover {
            background: #1d4ed8;
            transform: scale(1.02);
            color: white;
        }
        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
        }

        .table-container {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .property-thumb {
            width: 60px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--light-gray);
        }

        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status.available { background: #dcfce7; color: #166534; }
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.sold { background: #fee2e2; color: #991b1b; }
        .badge-status.rented { background: #cffafe; color: #155e75; }

        .badge-type {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-type.sale { background: #dbeafe; color: #1e40af; }
        .badge-type.rent { background: #fef3c7; color: #92400e; }

        .badge-featured {
            background: var(--accent);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
        }

        .row-highlight { animation: highlightRow 1s ease; }
        @keyframes highlightRow {
            0% { background-color: rgba(34, 197, 94, 0.3); }
            100% { background-color: transparent; }
        }
        .row-highlight-delete { animation: highlightDelete 0.6s ease forwards; }
        @keyframes highlightDelete {
            0% { background-color: rgba(239, 68, 68, 0.3); }
            100% { opacity: 0; transform: scale(0.95); height: 0; padding: 0; overflow: hidden; }
        }

        .btn-action {
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
        }
        .btn-action:active { transform: scale(0.92); }

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

        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9998;
            overflow: hidden;
        }
        .confetti {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 2px;
            animation: confettiFall linear forwards;
        }
        @keyframes confettiFall {
            0% { transform: translateY(-20px) rotate(0deg) scale(1); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg) scale(0.5); opacity: 0; }
        }
    </style>
</head>
<body>

    <div id="toastContainer" class="toast-container"></div>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="brand" href="admin.php"><i class="fas fa-home"></i> KKG Property Admin</a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin-properties.php" class="nav-link active"><i class="fas fa-home"></i> Properties</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> <?= htmlspecialchars($auth->name) ?></a>
                    </li>
                    <li class="nav-item">
                        <span class="user-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                    </li>
                    <li class="nav-item">
                        <a href="actions/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container mt-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-home text-primary"></i> Manage Properties</h4>
                <small class="text-muted">View, edit, and manage all properties</small>
            </div>
            <div>
                <a href="add-property.php" class="btn btn-primary-custom">
                    <i class="fas fa-plus"></i> Add Property
                </a>
                <a href="admin.php" class="btn btn-outline-custom">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number"><?= (int)($property_stats->total ?? 0) ?></div>
                    <div class="label"><i class="fas fa-home"></i> Total Properties</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number"><?= (int)($property_stats->available ?? 0) ?></div>
                    <div class="label"><i class="fas fa-check-circle"></i> Available</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number"><?= (int)($property_stats->sold ?? 0) ?></div>
                    <div class="label"><i class="fas fa-handshake"></i> Sold</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card teal">
                    <div class="number"><?= (int)($property_stats->rented ?? 0) ?></div>
                    <div class="label"><i class="fas fa-key"></i> Rented</div>
                </div>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-list text-primary"></i> All Properties</h6>
                <span class="text-muted small">Total: <?= count($properties) ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Agent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($properties)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-home fa-2x mb-2 d-block"></i>
                                    No properties yet. <a href="add-property.php">Add the first one!</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($properties as $property): ?>
                                <tr id="property-<?= $property->id ?>">
                                    <td><?= $property->id ?></td>
                                    <td>
                                        <?php if ($property->photo): ?>
                                            <img src="/project/images/<?= $property->photo ?>" class="property-thumb" alt="<?= $property->title ?>">
                                        <?php else: ?>
                                            <div class="property-thumb d-flex align-items-center justify-content-center">
                                                <i class="fas fa-home text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($property->title) ?></strong>
                                        <?php if ($property->featured): ?>
                                            <span class="badge-featured"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-bed"></i> <?= $property->bedrooms ?>
                                            <i class="fas fa-bath ms-2"></i> <?= $property->bathrooms ?>
                                            <i class="fas fa-vector-square ms-2"></i> <?= $property->area_sqft ?> sqft
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge-type <?= $property->listing_type ?>">
                                            <?= strtoupper($property->listing_type) ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?= ucfirst($property->property_type) ?></small>
                                    </td>
                                    <td><strong>$<?= number_format($property->price, 0) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($property->city) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($property->state) ?></small>
                                    </td>
                                    <td class="status-cell">
                                        <span class="badge-status <?= $property->status ?>">
                                            <?= ucfirst($property->status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($property->agent_name ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <!-- Status Dropdown -->
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle btn-action" data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-dark">
                                                <a href="#" class="dropdown-item change-status" data-propertyid="<?= $property->id ?>" data-status="available">✅ Available</a>
                                                <a href="#" class="dropdown-item change-status" data-propertyid="<?= $property->id ?>" data-status="pending">⏳ Pending</a>
                                                <a href="#" class="dropdown-item change-status" data-propertyid="<?= $property->id ?>" data-status="sold">🤝 Sold</a>
                                                <a href="#" class="dropdown-item change-status" data-propertyid="<?= $property->id ?>" data-status="rented">🔑 Rented</a>
                                                <div class="dropdown-divider"></div>
                                                <a href="#" class="dropdown-item toggle-featured" data-propertyid="<?= $property->id ?>">
                                                    <i class="fas fa-star"></i> Toggle Featured
                                                </a>
                                            </div>
                                        </div>

                                        <!-- View -->
                                        <a href="property-detail.php?id=<?= $property->id ?>" class="btn btn-sm btn-info btn-action" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Delete -->
                                        <button class="btn btn-sm btn-danger btn-action delete-property" 
                                                data-propertyid="<?= $property->id ?>" 
                                                data-title="<?= htmlspecialchars($property->title) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <footer class="text-center text-muted py-3 mt-4">
        <small>&copy; <?= date('Y') ?> KKG Property Admin Panel</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
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
            }, 3500);
        }

        function showConfetti() {
            const container = document.createElement('div');
            container.className = 'confetti-container';
            document.body.appendChild(container);
            const colors = ['#2563eb', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'];
            for (let i = 0; i < 60; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = (Math.random() * 8 + 4) + 'px';
                confetti.style.height = (Math.random() * 8 + 4) + 'px';
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                confetti.style.animationDuration = (Math.random() * 2 + 1.5) + 's';
                confetti.style.animationDelay = (Math.random() * 0.8) + 's';
                container.appendChild(confetti);
            }
            setTimeout(() => container.remove(), 3500);
        }

        function showLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
        function hideLoading() { document.getElementById('loadingOverlay').classList.remove('hide', 'show'); }

        // ===== CHANGE STATUS =====
        document.querySelectorAll('.change-status').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const propertyId = this.dataset.propertyid;
                const status = this.dataset.status;
                const row = document.getElementById('property-' + propertyId);
                const statusCell = row.querySelector('.status-cell');

                if (!confirm(`Change status to ${status}?`)) return;

                showLoading();
                
                const badgeClass = status;
                statusCell.innerHTML = `<span class="badge-status ${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;

                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 1000);

                fetch(`actions/properties-update-status.php?id=${propertyId}&status=${status}`)
                    .then(() => {
                        hideLoading();
                        showToast(`Status changed to ${status}!`, 'success');
                        showConfetti();
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error updating status', 'error');
                    });
            });
        });

        // ===== TOGGLE FEATURED =====
        document.querySelectorAll('.toggle-featured').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const propertyId = this.dataset.propertyid;
                const row = document.getElementById('property-' + propertyId);

                showLoading();
                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 1000);

                fetch(`actions/properties-toggle-featured.php?id=${propertyId}`)
                    .then(() => {
                        hideLoading();
                        showToast('Featured status updated!', 'success');
                        showConfetti();
                        setTimeout(() => location.reload(), 800);
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error updating featured status', 'error');
                    });
            });
        });

        // ===== DELETE PROPERTY =====
        document.querySelectorAll('.delete-property').forEach(btn => {
            btn.addEventListener('click', function() {
                const propertyId = this.dataset.propertyid;
                const propertyTitle = this.dataset.title;
                const row = document.getElementById('property-' + propertyId);

                if (!confirm(`Delete "${propertyTitle}" permanently?\n\nThis will also delete all photos!`)) return;

                this.disabled = true;
                showLoading();
                row.classList.add('row-highlight-delete');
                showToast(`Deleting property...`, 'info');

                fetch(`actions/properties-delete.php?id=${propertyId}`)
                    .then(() => {
                        hideLoading();
                        setTimeout(() => {
                            row.remove();
                            showToast(`Property deleted!`, 'error');
                        }, 600);
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error deleting property', 'error');
                    });
            });
        });
    </script>
</body>
</html>