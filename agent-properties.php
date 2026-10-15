<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\AppointmentsTable;

$auth = Auth::check();

if ($auth->role_id < 2) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$properties_table   = new PropertiesTable(new MySQL);
$appointments_table = new AppointmentsTable(new MySQL);

$all_properties = $properties_table->all();

$properties = [];
foreach ($all_properties as $prop) {
    if ($prop->user_id == $auth->id) {
        $properties[] = $prop;
    }
}

// Stats
$total = count($properties);
$available = 0; $pending = 0; $sold = 0; $rented = 0;

foreach ($properties as $p) {
    switch ($p->status) {
        case 'available': $available++; break;
        case 'pending':   $pending++;   break;
        case 'sold':      $sold++;      break;
        case 'rented':    $rented++;    break;
    }
}

// Appointments
$appointments = $appointments_table->getByAgent($auth->id);

$appt_pending = 0; $appt_confirmed = 0; $appt_completed = 0; $appt_cancelled = 0;

foreach ($appointments as $a) {
    switch ($a->status) {
        case 'pending':   $appt_pending++;   break;
        case 'confirmed': $appt_confirmed++; break;
        case 'completed': $appt_completed++; break;
        case 'cancelled': $appt_cancelled++; break;
    }
}

$is_logged_in = true;
$user = $auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Agent Dashboard</title>
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
        .stat-card.green { border-color: #22c55e; }
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.red { border-color: #ef4444; }
        .stat-card.teal { border-color: #06b6d4; }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: white;
            transform: scale(1.02);
        }

        .table-container {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .table thead th {
            background: var(--light-gray);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--gray);
            border: none;
            padding: 14px 12px;
            letter-spacing: 0.5px;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 16px 12px;
        }

        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status.available { background: #dcfce7; color: #166534; }
        .badge-status.pending   { background: #fef3c7; color: #92400e; }
        .badge-status.sold      { background: #fee2e2; color: #991b1b; }
        .badge-status.rented    { background: #cffafe; color: #155e75; }

        /* ===== STATUS SELECT (screenshot style) ===== */
        .status-select {
            font-size: 0.82rem;
            font-weight: 600;
            padding: 8px 32px 8px 14px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 140px;
            color: var(--secondary);
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%230f172a' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        .status-select:hover { border-color: var(--primary); }
        .status-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
            outline: none;
        }
        .status-select:disabled { opacity: 0.6; cursor: wait; }

        /* ===== ACTION ICON BUTTONS ===== */
        .action-btns {
            display: inline-flex;
            gap: 6px;
            justify-content: flex-end;
        }
        .btn-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-icon:hover { transform: scale(1.1); }
        .btn-icon.view   { background: #dbeafe; color: #1e40af; }
        .btn-icon.edit   { background: #fef3c7; color: #92400e; }
        .btn-icon.delete { background: #fee2e2; color: #991b1b; }

        /* ===== APPOINTMENT SECTION ===== */
        .appointments-section { margin-top: 30px; }
        .appointment-card {
            background: white;
            border-radius: var(--radius);
            padding: 18px 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            align-items: center;
            transition: all 0.3s;
        }
        .appointment-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
        .appointment-card.pending   { border-left-color: #f59e0b; }
        .appointment-card.confirmed { border-left-color: #22c55e; }
        .appointment-card.completed { border-left-color: #06b6d4; }
        .appointment-card.cancelled { border-left-color: #ef4444; opacity: 0.7; }

        .appointment-card img {
            width: 110px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }
        .appointment-card .no-image {
            width: 110px;
            height: 90px;
            background: var(--light-gray);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.5rem;
        }
        .appointment-card .info { flex: 1; }
        .appointment-card .info .title {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }
        .appointment-card .info .location {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 6px;
        }
        .appointment-card .info .datetime {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .appointment-card .info .user {
            font-size: 0.85rem;
            color: var(--secondary);
            margin-bottom: 4px;
        }
        .appointment-card .info .message {
            color: var(--gray);
            font-size: 0.82rem;
            font-style: italic;
            margin-top: 6px;
        }

        .badge-apt-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-apt-status.pending   { background: #fef3c7; color: #92400e; }
        .badge-apt-status.confirmed { background: #dcfce7; color: #166534; }
        .badge-apt-status.completed { background: #cffafe; color: #155e75; }
        .badge-apt-status.cancelled { background: #fee2e2; color: #991b1b; }

        .btn-action {
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.78rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
        }
        .btn-action:hover { transform: scale(1.05); }
        .btn-confirm  { background: #22c55e; color: white; }
        .btn-complete { background: #06b6d4; color: white; }
        .btn-cancel   { background: #ef4444; color: white; }
        .btn-action:disabled { opacity: 0.5; cursor: not-allowed; }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        .toast-message {
            padding: 16px 24px;
            border-radius: var(--radius);
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
            margin-bottom: 10px;
            animation: slideIn 0.4s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(120%); }
            to   { transform: translateX(0); }
        }
        .toast-message.success { background: #22c55e; }
        .toast-message.error   { background: #ef4444; }

        .footer {
            background: #0f172a;
            color: rgba(255,255,255,0.7);
            padding: 30px 0 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.8rem; }
            .appointment-card { flex-direction: column; text-align: center; }
            .status-select { min-width: 110px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <div id="toastContainer" class="toast-container"></div>

    <section class="page-header">
        <div class="container">
            <h1>My <span>Properties</span></h1>
            <p>Manage your listings and appointments</p>
        </div>
    </section>

    <section class="container mt-4">

        <!-- ===== STAT CARDS ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="number"><?= $total ?></div>
                    <div class="label"><i class="fas fa-home"></i> Total Listings</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card green">
                    <div class="number"><?= $available ?></div>
                    <div class="label"><i class="fas fa-check-circle"></i> Available</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card red">
                    <div class="number"><?= $sold ?></div>
                    <div class="label"><i class="fas fa-handshake"></i> Sold</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card teal">
                    <div class="number"><?= $rented ?></div>
                    <div class="label"><i class="fas fa-key"></i> Rented</div>
                </div>
            </div>
        </div>

        <!-- ===== PROPERTY LIST ===== -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list text-primary"></i> Property List
            </h5>
            <a href="add-property.php" class="btn-primary-custom">
                <i class="fas fa-plus"></i> Add New Property
            </a>
        </div>

        <div class="table-container">
            <?php if (empty($properties)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-home fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold">No Properties Yet</h5>
                    <p class="text-muted">Start by adding your first property listing.</p>
                    <a href="add-property.php" class="btn-primary-custom mt-2">
                        <i class="fas fa-plus"></i> Add Property
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>City</th>
                                <th>Status</th>
                                <th class="text-center">Change Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $p): ?>
                                <tr id="property-<?= $p->id ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($p->photo): ?>
                                                <img src="images/<?= $p->photo ?>"
                                                     style="width:45px;height:45px;object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <div style="width:45px;height:45px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-home text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold small"><?= htmlspecialchars($p->title) ?></div>
                                                <div class="text-muted" style="font-size:0.75rem;">
                                                    <?= $p->bedrooms ?> bd • <?= $p->bathrooms ?> ba • <?= $p->area_sqft ?> sqft
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark text-uppercase" style="font-size:0.7rem;">
                                            <?= $p->listing_type ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold">$<?= number_format($p->price, 0) ?></td>
                                    <td class="small"><?= htmlspecialchars($p->city) ?></td>
                                    <td class="status-cell">
                                        <span class="badge-status <?= $p->status ?>">
                                            <?= ucfirst($p->status) ?>
                                        </span>
                                    </td>

                                    <!-- ✅ CHANGE STATUS COLUMN -->
                                    <td class="text-center">
                                        <select class="status-select"
                                                data-id="<?= $p->id ?>"
                                                onchange="changePropertyStatus(this)">
                                            <?php
                                            if ($p->listing_type === 'sale') {
                                                $options = ['available', 'pending', 'sold'];
                                            } else {
                                                $options = ['available', 'pending', 'rented'];
                                            }
                                            foreach ($options as $opt):
                                                if ($opt === $p->status) continue;
                                            ?>
                                                <option value="<?= $opt ?>"><?= ucfirst($opt) ?></option>
                                            <?php endforeach; ?>
                                            <option value="" disabled selected>Change to...</option>
                                        </select>
                                    </td>

                                    <!-- ✅ ACTIONS COLUMN -->
                                    <td class="text-center">
                                        <div class="action-btns">
                                            <a href="property-detail.php?id=<?= $p->id ?>"
                                               class="btn-icon view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="property-edit.php?id=<?= $p->id ?>"
                                               class="btn-icon edit" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button class="btn-icon delete"
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

        <!-- ===== APPOINTMENTS SECTION ===== -->
        <div class="appointments-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-calendar-check text-primary"></i> My Appointments
                </h5>
                <span class="text-muted small"><?= count($appointments) ?> total</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card orange">
                        <div class="number" style="font-size:1.5rem;"><?= $appt_pending ?></div>
                        <div class="label"><i class="fas fa-clock"></i> Pending</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card green">
                        <div class="number" style="font-size:1.5rem;"><?= $appt_confirmed ?></div>
                        <div class="label"><i class="fas fa-check-circle"></i> Confirmed</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card teal">
                        <div class="number" style="font-size:1.5rem;"><?= $appt_completed ?></div>
                        <div class="label"><i class="fas fa-flag-checkered"></i> Completed</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card red">
                        <div class="number" style="font-size:1.5rem;"><?= $appt_cancelled ?></div>
                        <div class="label"><i class="fas fa-times-circle"></i> Cancelled</div>
                    </div>
                </div>
            </div>

            <?php if (empty($appointments)): ?>
                <div class="table-container text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold">No Appointments</h5>
                    <p class="text-muted">No appointment requests for your properties yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $a): ?>
                    <div class="appointment-card <?= $a->status ?>" id="appointment-<?= $a->id ?>">
                        <?php if (!empty($a->property_photo)): ?>
                            <img src="images/<?= htmlspecialchars($a->property_photo) ?>" alt="Property">
                        <?php else: ?>
                            <div class="no-image"><i class="fas fa-home"></i></div>
                        <?php endif; ?>

                        <div class="info">
                            <div class="title"><?= htmlspecialchars($a->property_title ?? 'Property') ?></div>
                            <div class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($a->property_city ?? 'N/A') ?>
                            </div>
                            <div class="datetime">
                                <i class="fas fa-calendar"></i>
                                <?= date('F j, Y', strtotime($a->appointment_date)) ?>
                                <i class="fas fa-clock ms-2"></i>
                                <?= date('g:i A', strtotime($a->appointment_date)) ?>
                            </div>
                            <div class="user">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($a->user_name ?? 'N/A') ?>
                                &nbsp;•&nbsp;
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($a->user_email ?? 'N/A') ?>
                            </div>
                            <?php if (!empty($a->message)): ?>
                                <div class="message">
                                    <i class="fas fa-comment"></i>
                                    <?= htmlspecialchars($a->message) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-end">
                            <div>
                                <span class="badge-apt-status <?= $a->status ?>">
                                    <?= ucfirst($a->status) ?>
                                </span>
                            </div>

                            <div class="d-flex flex-column gap-1 mt-2">
                                <?php if ($a->status === 'pending'): ?>
                                    <button class="btn-action btn-confirm"
                                            onclick="updateAppointmentStatus(<?= $a->id ?>, 'confirmed', this)">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                <?php endif; ?>

                                <?php if ($a->status === 'confirmed'): ?>
                                    <button class="btn-action btn-complete"
                                            onclick="updateAppointmentStatus(<?= $a->id ?>, 'completed', this)">
                                        <i class="fas fa-flag-checkered"></i> Complete
                                    </button>
                                <?php endif; ?>

                                <?php if (in_array($a->status, ['pending', 'confirmed'])): ?>
                                    <button class="btn-action btn-cancel"
                                            onclick="updateAppointmentStatus(<?= $a->id ?>, 'cancelled', this)">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </section>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle' };
            const toast = document.createElement('div');
            toast.className = `toast-message ${type}`;
            toast.innerHTML = `<i class="fas ${icons[type]}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        }

        // ===== PROPERTY STATUS CHANGE =====
        function changePropertyStatus(select) {
            const id = select.dataset.id;
            const newStatus = select.value;

            if (!newStatus) return;

            if (!confirm(`Change status to "${newStatus}"?`)) {
                select.selectedIndex = select.options.length - 1;
                return;
            }

            select.disabled = true;

            const row = document.getElementById('property-' + id);
            const statusCell = row.querySelector('.status-cell');

            fetch(`actions/properties-update-status.php?id=${id}&status=${newStatus}`, {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusCell.innerHTML = `<span class="badge-status ${newStatus}">${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}</span>`;
                    showToast('Status changed to ' + newStatus, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    select.disabled = false;
                    select.selectedIndex = select.options.length - 1;
                    showToast(data.message || 'Failed to update', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                select.disabled = false;
                select.selectedIndex = select.options.length - 1;
                showToast('Network error', 'error');
            });
        }

        // ===== DELETE PROPERTY =====
        function deleteProperty(id, title) {
            if (!confirm(`Delete "${title}" permanently?\n\nThis will also delete all photos.`)) return;

            fetch(`actions/properties-delete.php?id=${id}`, { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Property deleted', 'success');
                        const row = document.getElementById('property-' + id);
                        if (row) row.remove();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Failed to delete', 'error');
                    }
                })
                .catch(() => showToast('Network error', 'error'));
        }

        // ===== APPOINTMENT STATUS UPDATE =====
        function updateAppointmentStatus(id, newStatus, btn) {
            if (!confirm(`Mark this appointment as ${newStatus}?`)) return;

            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`actions/appointments-update.php?id=${id}&status=${newStatus}`, {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Appointment marked as ' + newStatus, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    showToast(data.message || 'Failed to update', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                showToast('Network error', 'error');
            });
        }
    </script>
</body>
</html>