<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PaymentsTable;
use Libs\Database\PropertiesTable;
use Libs\Database\UsersTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$payments_table = new PaymentsTable(new MySQL);
$properties_table = new PropertiesTable(new MySQL);
$users_table = new UsersTable(new MySQL);

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$all = $payments_table->all();

$payments = array_filter($all, function($p) use ($search, $status_filter) {
    if ($search && stripos($p->property_title ?? '', $search) === false
                && stripos($p->user_name ?? '', $search) === false) {
        return false;
    }
    if ($status_filter && $p->status !== $status_filter) return false;
    return true;
});
$payments = array_values($payments);

$payment_stats = $payments_table->getStats();
$is_logged_in = true;
$user = $auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - KKG Property Admin</title>
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

        .stat-card {
            background: white;
            padding: 20px 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        .stat-card .number {
            font-size: 1.8rem;
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
        .stat-card.green { border-color: #22c55e; }
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.blue { border-color: var(--primary); }
        .stat-card.red { border-color: #ef4444; }

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
        .badge-status.pending   { background: #fef3c7; color: #92400e; }
        .badge-status.completed { background: #dcfce7; color: #166534; }
        .badge-status.failed    { background: #fee2e2; color: #991b1b; }
        .badge-status.refunded  { background: #e0e7ff; color: #3730a3; }

        .badge-method {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            background: #f1f5f9;
            color: #475569;
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
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-action:hover { transform: scale(1.1); }
        .btn-action.delete { background: #fee2e2; color: #991b1b; }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary-custom:hover {
            background: #1d4ed8;
            color: white;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
    </style>
</head>
<body>

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
                    <li class="nav-item"><a href="admin-properties.php" class="nav-link"><i class="fas fa-home"></i> Properties</a></li>
                    <li class="nav-item"><a href="admin-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li class="nav-item"><a href="admin-payments.php" class="nav-link active"><i class="fas fa-dollar-sign"></i> Payments</a></li>
                    <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> <?= htmlspecialchars($auth->name) ?></a></li>
                    <li class="nav-item"><span class="user-badge"><i class="fas fa-shield-alt"></i> Admin</span></li>
                    <li class="nav-item"><a href="actions/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="toastContainer" class="toast-container"></div>

    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-dollar-sign"></i> Manage <span>Payments</span></h1>
        </div>
    </section>

    <div class="container">

        <!-- ===== STATS ===== -->
        <div class="row g-4 mt-2 mb-4">
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number">$<?= number_format((float)$payment_stats->total_paid, 0) ?></div>
                    <div class="label"><i class="fas fa-check-circle"></i> Total Paid</div>
                    <i class="fas fa-dollar-sign icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number">$<?= number_format((float)$payment_stats->total_pending, 0) ?></div>
                    <div class="label"><i class="fas fa-clock"></i> Pending</div>
                    <i class="fas fa-hourglass-half icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number"><?= (int)$payment_stats->count_completed ?></div>
                    <div class="label"><i class="fas fa-receipt"></i> Completed</div>
                    <i class="fas fa-file-invoice-dollar icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
                    <div class="number">$<?= number_format((float)$payment_stats->total_failed, 0) ?></div>
                    <div class="label"><i class="fas fa-times-circle"></i> Failed</div>
                    <i class="fas fa-ban icon-bg"></i>
                </div>
            </div>
        </div>

        <!-- ===== FILTER BAR ===== -->
        <div class="filter-bar">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" class="form-control" name="search"
                           placeholder="Property or user name..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending"   <?= $status_filter == 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="failed"    <?= $status_filter == 'failed'    ? 'selected' : '' ?>>Failed</option>
                        <option value="refunded"  <?= $status_filter == 'refunded'  ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="admin-payments.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- ===== TABLE ===== -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-list text-primary"></i>
                    <?= count($payments) ?> Payment<?= count($payments) == 1 ? '' : 's' ?>
                </h5>
                <a href="admin-record-payment.php" class="btn-primary-custom">
                    <i class="fas fa-plus"></i> Record Payment
                </a>
            </div>

            <?php if (empty($payments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h5>No Payments Found</h5>
                    <p class="text-muted">Record your first payment to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-center">Change Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr id="row-<?= $pay->id ?>">
                                    <td>#<?= $pay->id ?></td>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($pay->property_title ?? 'N/A') ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;">
                                            <?= htmlspecialchars($pay->property_city ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($pay->user_name ?? 'N/A') ?></td>
                                    <td class="fw-bold">$<?= number_format((float)$pay->amount, 0) ?></td>
                                    <td><span class="badge-method"><?= $pay->payment_method ?></span></td>
                                    <td class="small"><?= date('M d, Y', strtotime($pay->payment_date)) ?></td>
                                    <td>
                                        <span class="badge-status <?= $pay->status ?>" id="status-<?= $pay->id ?>">
                                            <?= ucfirst($pay->status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-select form-select-sm"
                                                onchange="updatePaymentStatus(<?= $pay->id ?>, this)">
                                            <?php
                                            $all_statuses = ['pending', 'completed', 'failed', 'refunded'];
                                            foreach ($all_statuses as $s):
                                                if ($s === $pay->status) continue;
                                            ?>
                                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                            <option value="" disabled selected>Change to...</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn-action delete"
                                                onclick="deletePayment(<?= $pay->id ?>)"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle' };
            const toast = document.createElement('div');
            toast.className = `toast-message ${type}`;
            toast.innerHTML = `<i class="fas ${icons[type]}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        }

        function updatePaymentStatus(id, select) {
            const newStatus = select.value;
            if (!newStatus) return;
            select.disabled = true;

            fetch(`actions/payments-update-status.php?id=${id}&status=${newStatus}`, { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('status-' + id);
                        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        badge.className = 'badge-status ' + newStatus;
                        showToast('Status updated', 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        select.disabled = false;
                        select.selectedIndex = 0;
                        showToast(data.message || 'Failed', 'error');
                    }
                })
                .catch(() => {
                    select.disabled = false;
                    showToast('Network error', 'error');
                });
        }

        function deletePayment(id) {
            if (!confirm('Delete this payment record permanently?')) return;

            fetch(`actions/payments-delete.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Payment deleted', 'success');
                        document.getElementById('row-' + id).remove();
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToast(data.message || 'Failed', 'error');
                    }
                })
                .catch(() => showToast('Network error', 'error'));
        }
    </script>
</body>
</html>