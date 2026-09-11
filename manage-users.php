<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Libs\Database\MessagesTable;

// Check if logged in and is admin
$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$users_table = new UsersTable(new MySQL);
$messages_table = new MessagesTable(new MySQL);
$user_stats = $users_table->getStats();
$users = $users_table->all();

// Get unread message count
$unread_count = $messages_table->getUnreadCount();

$is_admin = ($auth->role_id == 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - KKG Property Admin</title>
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

        .badge-role {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-role.admin { background: #ef4444; color: white; }
        .badge-role.agent { background: var(--primary); color: white; }
        .badge-role.user { background: var(--gray); color: white; }

        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status.active { background: #dcfce7; color: #166534; }
        .badge-status.suspended { background: #fee2e2; color: #991b1b; }

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
                        <a href="manage-users.php" class="nav-link active"><i class="fas fa-users"></i> Users</a>
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

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-users text-primary"></i> Manage Users</h4>
                <small class="text-muted">View and manage all users</small>
            </div>
            <a href="admin.php" class="btn btn-outline-custom">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number"><?= $user_stats->total ?? 0 ?></div>
                    <div class="label"><i class="fas fa-users"></i> Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number"><?= $user_stats->users ?? 0 ?></div>
                    <div class="label"><i class="fas fa-user"></i> Regular Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number"><?= $user_stats->agents ?? 0 ?></div>
                    <div class="label"><i class="fas fa-user-tie"></i> Agents</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
                    <div class="number"><?= $user_stats->admins ?? 0 ?></div>
                    <div class="label"><i class="fas fa-user-shield"></i> Admins</div>
                </div>
            </div>
        </div>

        <!-- User Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-list text-primary"></i> All Users</h6>
                <span class="text-muted small">Total: <?= count($users) ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $user): ?>
                            <tr id="user-<?= $user->id ?>" class="user-row">
                                <td><?= $user->id ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user->name) ?></strong>
                                    <?php if ($user->id == $auth->id): ?>
                                        <span class="badge bg-info text-white ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user->email) ?></td>
                                <td><?= htmlspecialchars($user->phone) ?></td>
                                <td class="role-cell">
                                    <span class="badge-role <?= $user->role_id == 3 ? 'admin' : ($user->role_id == 2 ? 'agent' : 'user') ?>">
                                        <?= ucfirst($user->role ?? 'User') ?>
                                    </span>
                                </td>
                                <td class="status-cell">
                                    <span class="badge-status <?= $user->suspended ? 'suspended' : 'active' ?>">
                                        <?= $user->suspended ? 'Suspended' : 'Active' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user->id != $auth->id): ?>
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle btn-action" data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-dark">
                                                <a href="#" class="dropdown-item change-role" data-userid="<?= $user->id ?>" data-role="1">👤 User</a>
                                                <a href="#" class="dropdown-item change-role" data-userid="<?= $user->id ?>" data-role="2">🏠 Agent</a>
                                                <a href="#" class="dropdown-item change-role" data-userid="<?= $user->id ?>" data-role="3">🛡️ Admin</a>
                                            </div>
                                        </div>

                                        <?php if ($user->suspended): ?>
                                            <button class="btn btn-sm btn-success btn-action toggle-suspend" data-userid="<?= $user->id ?>" data-action="unsuspend">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning btn-action toggle-suspend" data-userid="<?= $user->id ?>" data-action="suspend">
                                                <i class="fas fa-ban"></i> Suspend
                                            </button>
                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-danger btn-action delete-user" data-userid="<?= $user->id ?>" data-username="<?= htmlspecialchars($user->name) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
        function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

        // Toggle Suspend
        document.querySelectorAll('.toggle-suspend').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userid;
                const action = this.dataset.action;
                const row = document.getElementById('user-' + userId);
                const statusCell = row.querySelector('.status-cell');
                const actionText = action === 'suspend' ? 'Suspend' : 'Activate';

                if (!confirm(`Are you sure you want to ${actionText} this user?`)) return;

                this.disabled = true;
                showLoading();

                const newStatus = action === 'suspend' ? 'Suspended' : 'Active';
                const newBadge = action === 'suspend' ? 'suspended' : 'active';
                statusCell.innerHTML = `<span class="badge-status ${newBadge}">${newStatus}</span>`;

                if (action === 'suspend') {
                    this.innerHTML = '<i class="fas fa-check"></i> Activate';
                    this.dataset.action = 'unsuspend';
                    this.className = 'btn btn-sm btn-success btn-action toggle-suspend';
                } else {
                    this.innerHTML = '<i class="fas fa-ban"></i> Suspend';
                    this.dataset.action = 'suspend';
                    this.className = 'btn btn-sm btn-warning btn-action toggle-suspend';
                }
                this.disabled = false;

                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 1000);

                const actionUrl = action === 'suspend' ? 'actions/suspend.php' : 'actions/unsuspend.php';
                fetch(`${actionUrl}?id=${userId}`)
                    .then(() => {
                        hideLoading();
                        showToast(`User ${actionText}ed successfully!`, 'success');
                        showConfetti();
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error performing action', 'error');
                    });
            });
        });

        // Change Role
        document.querySelectorAll('.change-role').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.dataset.userid;
                const role = this.dataset.role;
                const roleNames = {1: 'User', 2: 'Agent', 3: 'Admin'};
                const row = document.getElementById('user-' + userId);

                if (!confirm(`Change this user to ${roleNames[role]}?`)) return;

                showLoading();
                const roleCell = row.querySelector('.role-cell');
                const badgeClass = role == 3 ? 'admin' : (role == 2 ? 'agent' : 'user');
                roleCell.innerHTML = `<span class="badge-role ${badgeClass}">${roleNames[role]}</span>`;

                row.classList.add('row-highlight');
                setTimeout(() => row.classList.remove('row-highlight'), 1000);

                fetch(`actions/role.php?id=${userId}&role=${role}`)
                    .then(() => {
                        hideLoading();
                        showToast(`Role changed to ${roleNames[role]}!`, 'success');
                        showConfetti();
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error changing role', 'error');
                    });
            });
        });

        // Delete User
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userid;
                const userName = this.dataset.username;
                const row = document.getElementById('user-' + userId);

                if (!confirm(`Delete "${userName}" permanently? This cannot be undone!`)) return;

                this.disabled = true;
                showLoading();
                row.classList.add('row-highlight-delete');
                showToast(`Deleting ${userName}...`, 'info');

                fetch(`actions/delete.php?id=${userId}`)
                    .then(() => {
                        hideLoading();
                        setTimeout(() => {
                            row.remove();
                            showToast(`User ${userName} deleted!`, 'error');
                        }, 600);
                    })
                    .catch(() => {
                        hideLoading();
                        showToast('Error deleting user', 'error');
                    });
            });
        });
    </script>
</body>
</html>