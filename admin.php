<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Libs\Database\MessagesTable;
use Libs\Database\PropertiesTable;
use Libs\Database\AppointmentsTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$users_table = new UsersTable(new MySQL);
$messages_table = new MessagesTable(new MySQL);
$properties_table = new PropertiesTable(new MySQL);
$appointments_table = new AppointmentsTable(new MySQL);

$user_stats = $users_table->getStats();
$message_stats = $messages_table->getStats();
$property_stats = $properties_table->getStats();
$appointment_stats = $appointments_table->getStats();

$recent_users = $users_table->getRecent(5);
$recent_messages = $messages_table->getRecent(5);
$recent_properties = $properties_table->getRecent(5);

$property_types = $properties_table->getTypeBreakdown();

$unread_count = $messages_table->getUnreadCount();
$pending_appointments = $appointments_table->getPendingCount();

$is_admin = ($auth->role_id == 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KKG Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
        }
        .stat-card .label {
            color: var(--gray);
            font-size: 0.85rem;
        }
        .stat-card .icon-bg {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.1;
        }
        .stat-card.blue { border-color: var(--primary); }
        .stat-card.green { border-color: #22c55e; }
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.red { border-color: #ef4444; }
        .stat-card.purple { border-color: #8b5cf6; }
        .stat-card.teal { border-color: #06b6d4; }

        .chart-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .chart-card .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .chart-card h6 {
            font-weight: 700;
            margin-bottom: 0;
            color: var(--secondary);
        }
        .btn-export-sm {
            background: #22c55e;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
        }
        .btn-export-sm:hover {
            background: #16a34a;
            transform: scale(1.05);
            color: white;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }
        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
        }
        .btn-export {
            background: #22c55e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }
        .btn-export:hover {
            background: #16a34a;
            transform: scale(1.02);
            color: white;
        }

        .table-container {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .badge-role {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-role.admin { background: #ef4444; color: white; }
        .badge-role.agent { background: var(--primary); color: white; }
        .badge-role.user { background: var(--gray); color: white; }

        .section-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .section-title span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: var(--primary); }

        .list-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .list-item:last-child { border-bottom: none; }
        .list-item .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .list-item .info { flex: 1; min-width: 0; }
        .list-item .name {
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .list-item .meta {
            color: var(--gray);
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-messages { position: relative; }
        .btn-messages .badge-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: 700;
            border: 2px solid white;
        }

        @media (max-width: 768px) {
            .stat-card .number { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

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
                        <a href="admin.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin-properties.php" class="nav-link"><i class="fas fa-home"></i> Properties</a>
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

        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-chart-line fs-4 me-3"></i>
            <div>
                <h5 class="mb-0">Welcome back, <?= htmlspecialchars($auth->name) ?>! 👋</h5>
                <small>Here's your Reports & Analytics Dashboard</small>
            </div>
        </div>

        <!-- ===== STATS CARDS ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number"><?= (int)($user_stats->total ?? 0) ?></div>
                    <div class="label"><i class="fas fa-users"></i> Total Users</div>
                    <i class="fas fa-users icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number"><?= (int)($property_stats->total ?? 0) ?></div>
                    <div class="label"><i class="fas fa-home"></i> Total Properties</div>
                    <i class="fas fa-home icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number"><?= (int)($message_stats->total ?? 0) ?></div>
                    <div class="label"><i class="fas fa-envelope"></i> Messages</div>
                    <i class="fas fa-envelope icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card purple">
                    <div class="number"><?= (int)($appointment_stats->total ?? 0) ?></div>
                    <div class="label"><i class="fas fa-calendar"></i> Appointments</div>
                    <i class="fas fa-calendar icon-bg"></i>
                </div>
            </div>
        </div>

        <!-- ===== SECOND ROW STATS ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number"><?= (int)($property_stats->available ?? 0) ?></div>
                    <div class="label"><i class="fas fa-check-circle"></i> Available</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number"><?= (int)($property_stats->pending ?? 0) ?></div>
                    <div class="label"><i class="fas fa-clock"></i> Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
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

        <!-- ===== QUICK ACTIONS + EXPORT ===== -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="p-3 bg-white rounded-3 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="fas fa-bolt text-warning"></i> Quick Actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="manage-users.php" class="btn btn-primary-custom"><i class="fas fa-users"></i> Manage Users</a>
                        <a href="admin-properties.php" class="btn btn-primary-custom"><i class="fas fa-home"></i> Properties</a>
                        <a href="add-property.php" class="btn btn-primary-custom"><i class="fas fa-plus"></i> Add Property</a>
                        <a href="admin-appointments.php" class="btn btn-primary-custom">
                            <i class="fas fa-calendar"></i> Appointments
                            <?php if ($pending_appointments > 0): ?>
                                <span class="badge bg-danger"><?= $pending_appointments ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="btn btn-primary-custom btn-messages">
                            <i class="fas fa-envelope"></i> Messages
                            <?php if ($unread_count > 0): ?>
                                <span class="badge-count"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php" class="btn btn-outline-custom"><i class="fas fa-user"></i> My Profile</a>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3"><i class="fas fa-download text-success"></i> Export Data</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="export.php?type=users" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Users
                        </a>
                        <a href="export.php?type=properties" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Properties
                        </a>
                        <a href="export.php?type=messages" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Messages
                        </a>
                        <a href="export.php?type=appointments" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Appointments
                        </a>
                        <a href="export.php?type=property_status" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Property Status
                        </a>
                        <a href="export.php?type=property_types" class="btn-export">
                            <i class="fas fa-file-csv"></i> Export Property Types
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== CHARTS ROW ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h6><i class="fas fa-chart-pie text-primary"></i> Property Status Breakdown</h6>
                        <a href="export.php?type=property_status" class="btn-export-sm">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                    <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-header">
                        <h6><i class="fas fa-chart-bar text-primary"></i> Property Types</h6>
                        <a href="export.php?type=property_types" class="btn-export-sm">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                    <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== RECENT ACTIVITY ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="table-container">
                    <div class="section-title">
                        <span><i class="fas fa-user-plus"></i> Recent Users</span>
                        <a href="export.php?type=users" class="btn-export-sm">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php if (empty($recent_users)): ?>
                        <p class="text-muted small">No users yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_users as $user): ?>
                            <div class="list-item">
                                <div class="avatar"><?= strtoupper(substr($user->name, 0, 1)) ?></div>
                                <div class="info">
                                    <div class="name"><?= htmlspecialchars($user->name) ?></div>
                                    <div class="meta"><?= htmlspecialchars($user->email) ?></div>
                                </div>
                                <span class="badge-role <?= $user->role_id == 3 ? 'admin' : ($user->role_id == 2 ? 'agent' : 'user') ?>">
                                    <?= ucfirst($user->role ?? 'User') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="table-container">
                    <div class="section-title">
                        <span><i class="fas fa-home"></i> Recent Properties</span>
                        <a href="export.php?type=properties" class="btn-export-sm">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php if (empty($recent_properties)): ?>
                        <p class="text-muted small">No properties yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_properties as $property): ?>
                            <div class="list-item">
                                <div class="avatar"><i class="fas fa-home"></i></div>
                                <div class="info">
                                    <div class="name"><?= htmlspecialchars($property->title) ?></div>
                                    <div class="meta"><?= htmlspecialchars($property->city) ?> • $<?= number_format($property->price, 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="table-container">
                    <div class="section-title">
                        <span><i class="fas fa-envelope"></i> Recent Messages</span>
                        <a href="export.php?type=messages" class="btn-export-sm">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php if (empty($recent_messages)): ?>
                        <p class="text-muted small">No messages yet.</p>
                    <?php else: ?>
                        <?php foreach ($recent_messages as $msg): ?>
                            <div class="list-item">
                                <div class="avatar"><?= strtoupper(substr($msg->name, 0, 1)) ?></div>
                                <div class="info">
                                    <div class="name"><?= htmlspecialchars($msg->name) ?></div>
                                    <div class="meta"><?= htmlspecialchars($msg->subject) ?></div>
                                </div>
                                <?php if ($msg->is_read == 0): ?>
                                    <span class="badge bg-warning">New</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <footer class="text-center text-muted py-3 mt-4">
        <small>&copy; <?= date('Y') ?> KKG Property Admin Panel</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== STATUS CHART =====
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Available', 'Pending', 'Sold', 'Rented'],
                datasets: [{
                    data: [
                        <?= (int)($property_stats->available ?? 0) ?>,
                        <?= (int)($property_stats->pending ?? 0) ?>,
                        <?= (int)($property_stats->sold ?? 0) ?>,
                        <?= (int)($property_stats->rented ?? 0) ?>
                    ],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#06b6d4'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 12 } }
                    }
                }
            }
        });

        // ===== PROPERTY TYPES CHART =====
        new Chart(document.getElementById('typeChart'), {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    $type_labels = [];
                    foreach ($property_types as $type) {
                        $type_labels[] = "'" . ucfirst($type->property_type) . "'";
                    }
                    echo implode(', ', $type_labels);
                    ?>
                ],
                datasets: [{
                    label: 'Properties',
                    data: [
                        <?php 
                        $type_counts = [];
                        foreach ($property_types as $type) {
                            $type_counts[] = $type->count;
                        }
                        echo implode(', ', $type_counts);
                        ?>
                    ],
                    backgroundColor: ['#2563eb', '#f59e0b', '#22c55e', '#8b5cf6', '#ef4444', '#06b6d4'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { font: { size: 11 } } }
                }
            }
        });
    </script>
</body>
</html>