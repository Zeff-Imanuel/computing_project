<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

$auth = Auth::check();

// Only agents (2) and admins (3) can access
if ($auth->role_id < 2) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$appointments_table = new AppointmentsTable(new MySQL);
$appointments = $appointments_table->getByAgent($auth->id);

// Calculate stats
$total = count($appointments);
$pending = 0;
$confirmed = 0;
$completed = 0;
$cancelled = 0;

foreach ($appointments as $apt) {
    switch ($apt->status) {
        case 'pending': $pending++; break;
        case 'confirmed': $confirmed++; break;
        case 'completed': $completed++; break;
        case 'cancelled': $cancelled++; break;
    }
}

$is_admin = ($auth->role_id == 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Appointments - KKG Property</title>
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

        /* ===== NAVBAR ===== */
        .navbar-custom {
            background: white;
            padding: 12px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e9ecef;
        }
        .navbar-custom .brand {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }
        .navbar-custom .brand i { color: var(--accent); }
        .navbar-custom .nav-link {
            color: #000307;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 6px;
            transition: 0.2s;
            font-size: 0.9rem;
        }
        .navbar-custom .nav-link:hover {
            color: var(--primary);
            background: #f1f5ff;
        }
        .navbar-custom .nav-link.active {
            color: var(--primary);
            background: #f1f5ff;
            font-weight: 700;
        }

        .user-badge {
            background: var(--primary);
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* ===== STATS ===== */
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
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.green { border-color: #22c55e; }
        .stat-card.teal { border-color: #06b6d4; }

        /* ===== BUTTONS ===== */
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

        /* ===== TABLE ===== */
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
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-status.pending { background: #fef3c7; color: #92400e; }
        .badge-status.confirmed { background: #dcfce7; color: #166534; }
        .badge-status.completed { background: #cffafe; color: #155e75; }
        .badge-status.cancelled { background: #fee2e2; color: #991b1b; }

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

        /* ===== LOADING ===== */
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

        /* ===== CONFETTI ===== */
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

        @media (max-width: 992px) {
            .navbar-custom .nav-link {
                padding: 10px 14px;
                margin: 2px 0;
            }
            .navbar-custom .btn {
                width: 100%;
                text-align: center;
                margin: 4px 0 !important;
            }
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
            <a class="brand" href="home.php">
                <i class="fas fa-home"></i> KKG Property
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="home.php" class="nav-link">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sale.php" class="nav-link">
                            For Sale
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="rent.php" class="nav-link">
                            For Rent
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="new-properties.php" class="nav-link">
                            New Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="agent-appointments.php" class="nav-link active">
                            <i class="fas fa-calendar"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link">
                            About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="contact.php" class="nav-link">
                            Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-property.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> Add Property
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center ms-3">
                    <a href="profile.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <?= htmlspecialchars($auth->name) ?>
                    </a>
                    <?php if ($is_admin): ?>
                        <a href="admin.php" class="btn btn-primary btn-sm rounded-pill px-3 ms-2">
                            Admin
                        </a>
                    <?php endif; ?>
                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-2">
                        Logout
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container mt-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="fas fa-calendar-check text-primary"></i> Agent Appointments
                </h4>
                <small class="text-muted">Manage appointments for your properties</small>
            </div>
            <a href="add-property.php" class="btn btn-primary-custom">
                <i class="fas fa-plus"></i> Add Property
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number"><?= $total ?></div>
                    <div class="label"><i class="fas fa-calendar"></i> Total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number"><?= $pending ?></div>
                    <div class="label"><i class="fas fa-clock"></i> Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number"><?= $confirmed ?></div>
                    <div class="label"><i class="fas fa-check-circle"></i> Confirmed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card teal">
                    <div class="number"><?= $completed ?></div>
                    <div class="label"><i class="fas fa-flag-checkered"></i> Completed</div>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-list text-primary"></i> My Appointments
                </h6>
                <span class="text-muted small">Total: <?= count($appointments) ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Client</th>
                            <th>Date & Time</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                    No appointments for your properties yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $apt): ?>
                                <tr id="appointment-<?= $apt->id ?>">
                                    <td><?= $apt->id ?></td>
                                    <td>
                                        <?php if ($apt->property_photo): ?>
                                            <img src="/project/images/<?= $apt->property_photo ?>" class="property-thumb" alt="Property">
                                        <?php else: ?>
                                            <div class="property-thumb d-flex align-items-center justify-content-center">
                                                <i class="fas fa-home text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <br>
                                        <small><strong><?= htmlspecialchars($apt->property_title ?? 'N/A') ?></strong></small><br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?= htmlspecialchars($apt->property_city ?? '') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($apt->user_name ?? 'N/A') ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> 
                                            <?= htmlspecialchars($apt->user_email ?? '') ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> 
                                            <?= htmlspecialchars($apt->user_phone ?? '') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <i class="fas fa-calendar"></i> 
                                            <?= date('M j, Y', strtotime($apt->appointment_date)) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('g:i A', strtotime($apt->appointment_date)) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($apt->message): ?>
                                            <small class="text-muted" style="font-style: italic;">
                                                "<?= htmlspecialchars(substr($apt->message, 0, 40)) ?><?= strlen($apt->message) > 40 ? '...' : '' ?>"
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status-cell">
                                        <span class="badge-status <?= $apt->status ?>">
                                            <?= ucfirst($apt->status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- Status Dropdown -->
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle btn-action" data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-dark">
                                                <a href="#" class="dropdown-item change-status" data-id="<?= $apt->id ?>" data-status="pending">⏳ Pending</a>
                                                <a href="#" class="dropdown-item change-status" data-id="<?= $apt->id ?>" data-status="confirmed">✅ Confirmed</a>
                                                <a href="#" class="dropdown-item change-status" data-id="<?= $apt->id ?>" data-status="completed">🏁 Completed</a>
                                                <a href="#" class="dropdown-item change-status" data-id="<?= $apt->id ?>" data-status="cancelled">❌ Cancelled</a>
                                            </div>
                                        </div>

                                        <!-- View Property -->
                                        <a href="property-detail.php?id=<?= $apt->property_id ?>" 
                                           class="btn btn-sm btn-info btn-action" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
        <small>&copy; <?= date('Y') ?> KKG Property Agent Panel</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        function showConfetti() {
            const container = document.createElement('div');
            container.className = 'confetti-container';
            document.body.appendChild(container);
            const colors = ['#2563eb', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
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
        function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

        // ===== CHANGE STATUS =====
        document.querySelectorAll('.change-status').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const status = this.dataset.status;
                const row = document.getElementById('appointment-' + id);
                const statusCell = row.querySelector('.status-cell');

                if (!confirm(`Change status to ${status}?`)) return;

                showLoading();
                statusCell.innerHTML = `<span class="badge-status ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;

                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 1000);

                fetch(`/project/actions/appointments-update.php?id=${id}&status=${status}`)
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
    </script>
</body>
</html>