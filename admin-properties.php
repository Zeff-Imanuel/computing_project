<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$properties_table = new PropertiesTable(new MySQL);

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter   = $_GET['type'] ?? '';

$all = $properties_table->all();

$properties = array_filter($all, function($p) use ($search, $status_filter, $type_filter) {
    if ($search && stripos($p->title, $search) === false
                && stripos($p->city, $search) === false
                && stripos($p->address, $search) === false) {
        return false;
    }
    if ($status_filter && $p->status !== $status_filter) return false;
    if ($type_filter   && $p->listing_type !== $type_filter) return false;
    return true;
});
$properties = array_values($properties);

$is_logged_in = true;
$user = $auth;
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
            --primary-dark: #1e40af;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --gray: #64748b;
            --light-gray: #f1f5f9;
            --border-color: #e2e8f0;
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

        .page-header {
            background: var(--secondary);
            padding: 40px 0 30px;
            color: white;
        }
        .page-header h1 { font-size: 2rem; font-weight: 800; margin: 0; }
        .page-header h1 span { color: var(--accent); }

        .table-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin: 30px 0;
        }

        .filter-bar {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-top: -20px;
            position: relative;
            z-index: 5;
        }

        .table thead th {
            background: var(--light-gray);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--gray);
            border: none;
            padding: 12px;
        }
        .table tbody td { vertical-align: middle; padding: 14px 12px; }

        .prop-thumb {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-status.available { background: #dcfce7; color: #166534; }
        .badge-status.pending   { background: #fef3c7; color: #92400e; }
        .badge-status.sold      { background: #fee2e2; color: #991b1b; }
        .badge-status.rented    { background: #cffafe; color: #155e75; }

        .badge-status::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-type {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-type.sale { background: #dbeafe; color: #1e40af; }
        .badge-type.rent { background: #fef3c7; color: #92400e; }

        /* ===== COMPACT STATUS SELECT ===== */
        .status-select {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 28px 6px 12px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            background-color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 130px;
        }
        .status-select:hover { border-color: var(--primary); }
        .status-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            outline: none;
        }
        .status-select:disabled {
            opacity: 0.6;
            cursor: wait;
            background-color: var(--light-gray);
        }

        .btn-action {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-action:hover { transform: scale(1.1); }
        .btn-action.view   { background: #dbeafe; color: #1e40af; }
        .btn-action.edit   { background: #fef3c7; color: #92400e; }
        .btn-action.delete { background: #fee2e2; color: #991b1b; }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
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
            min-width: 280px;
        }
        .toast-message.show { transform: translateX(0); }
        .toast-message.success { background: #22c55e; }
        .toast-message.error   { background: #ef4444; }
        .toast-message.info    { background: var(--primary); }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.3; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="brand" href="admin.php"><i class="fas fa-home"></i> KKG Property Admin</a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a href="admin.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a href="admin-properties.php" class="nav-link active"><i class="fas fa-home"></i> Properties</a></li>
                    
                    <li class="nav-item"><a href="admin-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Appointments</a></li>
                  <li class="nav-item">
    <a href="admin-payments.php" class="nav-link"><i class="fas fa-dollar-sign"></i> Payments</a>
</li>
                    <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> <?= htmlspecialchars($auth->name) ?></a></li>
                    <li class="nav-item"><span class="user-badge"><i class="fas fa-shield-alt"></i> Admin</span></li>
                    <li class="nav-item"><a href="actions/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="toastContainer" class="toast-container"></div>

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-home"></i> Manage <span>Properties</span></h1>
        </div>
    </section>

    <div class="container">

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" class="form-control" name="search"
                           placeholder="Title, city, or address..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="available" <?= $status_filter == 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="pending"   <?= $status_filter == 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="sold"      <?= $status_filter == 'sold'      ? 'selected' : '' ?>>Sold</option>
                        <option value="rented"    <?= $status_filter == 'rented'    ? 'selected' : '' ?>>Rented</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Listing Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="sale" <?= $type_filter == 'sale' ? 'selected' : '' ?>>For Sale</option>
                        <option value="rent" <?= $type_filter == 'rent' ? 'selected' : '' ?>>For Rent</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="admin-properties.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-list text-primary"></i>
                    <?= count($properties) ?> Propert<?= count($properties) == 1 ? 'y' : 'ies' ?>
                </h5>
                <a href="add-property.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Property
                </a>
            </div>

            <?php if (empty($properties)): ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <h5>No Properties Found</h5>
                    <p>Try adjusting your filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Location</th>
                                <th>Agent</th>
                                <th>Status</th>
                                <th class="text-center" style="width:170px;">Change Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $p): ?>
                                <tr id="row-<?= $p->id ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($p->photo)): ?>
                                                <img src="/project/images/<?= htmlspecialchars($p->photo) ?>"
                                                     class="prop-thumb" alt="">
                                            <?php else: ?>
                                                <div class="prop-thumb d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-home text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold small"><?= htmlspecialchars($p->title) ?></div>
                                                <div class="text-muted" style="font-size:0.72rem;">
                                                    <?= (int)$p->bedrooms ?> bd • <?= (int)$p->bathrooms ?> ba • <?= (int)$p->area_sqft ?> sqft
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-type <?= $p->listing_type ?>">
                                            For <?= ucfirst($p->listing_type) ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold">$<?= number_format($p->price, 0) ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars($p->city) ?>
                                        <div class="text-muted" style="font-size:0.72rem;">
                                            <?= htmlspecialchars($p->state) ?>
                                        </div>
                                    </td>
                                    <td class="small">
                                        <?= htmlspecialchars($p->agent_name ?? '—') ?>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $p->status ?>" id="status-<?= $p->id ?>">
                                            <?= ucfirst($p->status) ?>
                                        </span>
                                    </td>

                                    <!-- ✅ COMPACT STATUS SELECT -->
                                    <td class="text-center">
                                        <select class="status-select"
                                                data-id="<?= $p->id ?>"
                                                onchange="updateStatus(this)">
                                            <?php if ($p->status !== 'available'): ?>
                                                <option value="available">Available</option>
                                            <?php endif; ?>

                                            <?php if ($p->status !== 'pending'): ?>
                                                <option value="pending">Pending</option>
                                            <?php endif; ?>

                                            <?php if ($p->listing_type === 'sale'): ?>
                                                <?php if ($p->status !== 'sold'): ?>
                                                    <option value="sold">Sold</option>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($p->status !== 'rented'): ?>
                                                    <option value="rented">Rented</option>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <option value="" disabled selected>
                                                Change to...
                                            </option>
                                        </select>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="property-detail.php?id=<?= $p->id ?>"
                                               class="btn-action view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="property-edit.php?id=<?= $p->id ?>"
                                               class="btn-action edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-action delete"
                                                    onclick="deleteProperty(<?= $p->id ?>, '<?= htmlspecialchars(addslashes($p->title)) ?>')"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = {
                success: 'fa-check-circle',
                error:   'fa-exclamation-circle',
                info:    'fa-info-circle'
            };
            const toast = document.createElement('div');
            toast.className = `toast-message ${type}`;
            toast.innerHTML = `<i class="fas ${icons[type]}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 50);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 3500);
        }

        // ===== UPDATE STATUS FROM SELECT =====
        function updateStatus(select) {
            const propertyId = select.dataset.id;
            const newStatus  = select.value;

            if (!newStatus) return;

            select.disabled = true;

            fetch(`actions/properties-update-status.php?id=${propertyId}&status=${newStatus}`, {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update badge
                    const badge = document.getElementById('status-' + propertyId);
                    badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    badge.className = 'badge-status ' + newStatus;

                    showToast('Status updated to ' + newStatus, 'success');

                    // Reload page so dropdown rebuilds (removes the now-current status)
                    setTimeout(() => location.reload(), 900);
                } else {
                    select.disabled = false;
                    select.selectedIndex = 0;
                    showToast(data.message || 'Failed to update', 'error');
                }
            })
            .catch(() => {
                select.disabled = false;
                select.selectedIndex = 0;
                showToast('Network error', 'error');
            });
        }

        // ===== DELETE PROPERTY =====
        function deleteProperty(propertyId, title) {
            if (!confirm(`Delete "${title}" permanently?\n\nThis will also delete all photos.`)) return;

            fetch(`actions/properties-delete.php?id=${propertyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Property deleted', 'success');
                        const row = document.getElementById('row-' + propertyId);
                        if (row) row.remove();
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(data.message || 'Failed to delete', 'error');
                    }
                })
                .catch(() => showToast('Network error', 'error'));
        }
    </script>
</body>
</html>