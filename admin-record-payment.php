<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\UsersTable;

$auth = Auth::check();

if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$properties_table = new PropertiesTable(new MySQL);
$users_table      = new UsersTable(new MySQL);

$all_props = $properties_table->all();
$properties = array_filter($all_props, function($p) {
    return in_array($p->status, ['sold', 'rented', 'pending', 'available']);
});
$properties = array_values($properties);

$users = $users_table->all();

$is_logged_in = true;
$user = $auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - KKG Property Admin</title>
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
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.15);
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

        .form-container {
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: var(--shadow-lg);
            margin: -30px auto 40px;
            max-width: 800px;
            position: relative;
            z-index: 10;
        }

        .form-label {
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .form-label .required { color: #ef4444; }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control[readonly] {
            background-color: #f1f5f9;
            cursor: not-allowed;
        }

        .form-help {
            font-size: 0.78rem;
            color: var(--gray);
            margin-top: 4px;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: scale(1.02);
            color: white;
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        .btn-cancel {
            background: transparent;
            color: var(--gray);
            border: 2px solid var(--border-color);
            padding: 12px 35px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background: var(--light-gray);
            color: var(--secondary);
        }

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
            <h1><i class="fas fa-plus-circle"></i> Record <span>Payment</span></h1>
            <p class="text-muted mt-2 mb-0">Log a payment made for a sold or rented property</p>
        </div>
    </section>

    <div class="container">
        <div class="form-container">

            <form id="paymentForm">
                <div class="row g-3">

                    <!-- Property -->
                    <div class="col-md-6">
                        <label class="form-label">Property <span class="required">*</span></label>
                        <select class="form-select" id="property_id" required onchange="fillAmount(this)">
                            <option value="" data-price="">-- Select a property --</option>
                            <?php foreach ($properties as $p): ?>
                                <option value="<?= $p->id ?>" data-price="<?= $p->price ?>">
                                    #<?= $p->id ?> — <?= htmlspecialchars($p->title) ?>
                                    (<?= $p->listing_type ?> • <?= $p->status ?> • $<?= number_format($p->price, 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">Only properties for sale or rent</div>
                    </div>

                    <!-- User -->
                    <div class="col-md-6">
                        <label class="form-label">User (Buyer / Renter) <span class="required">*</span></label>
                        <select class="form-select" id="user_id" required>
                            <option value="">-- Select a user --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u->id ?>">
                                    <?= htmlspecialchars($u->name) ?> — <?= htmlspecialchars($u->email) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Amount (auto-filled, read-only) -->
                    <div class="col-md-6">
                        <label class="form-label">Amount ($) <span class="required">*</span></label>
                        <input type="number" class="form-control" id="amount"
                               step="0.01" min="0.01" required readonly
                               placeholder="Select a property first">
                        <div class="form-help">Auto-filled from the selected property</div>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-6">
                        <label class="form-label">Payment Method <span class="required">*</span></label>
                        <select class="form-select" id="payment_method" required>
                            <option value="cash">💵 Cash</option>
                            <option value="bank" selected>🏦 Bank Transfer</option>
                            <option value="card">💳 Card</option>
                            <option value="online">🌐 Online</option>
                        </select>
                    </div>

                    <!-- Payment Date -->
                    <div class="col-md-6">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" id="payment_date"
                               required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="required">*</span></label>
                        <select class="form-select" id="status" required>
                            <option value="completed" selected>✅ Completed</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="failed">❌ Failed</option>
                            <option value="refunded">↩️ Refunded</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" rows="3"
                                  placeholder="e.g. Initial deposit / Final payment / Rental for October"></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-6 mt-4">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-save"></i> Save Payment Record
                        </button>
                    </div>
                    <div class="col-md-6 mt-4">
                        <a href="admin-payments.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== AUTO-FILL AMOUNT FROM SELECTED PROPERTY =====
        function fillAmount(select) {
            const price = select.options[select.selectedIndex].getAttribute('data-price');
            const amountField = document.getElementById('amount');
            if (price && parseFloat(price) > 0) {
                amountField.value = parseFloat(price).toFixed(2);
            } else {
                amountField.value = '';
            }
        }

        function showToast(message, type = 'success') {
            const c = document.getElementById('toastContainer');
            const t = document.createElement('div');
            t.className = `toast-message ${type}`;
            t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            c.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const property_id = document.getElementById('property_id').value;
            const user_id     = document.getElementById('user_id').value;
            const amount      = document.getElementById('amount').value;
            const method      = document.getElementById('payment_method').value;
            const pay_date    = document.getElementById('payment_date').value;
            const status      = document.getElementById('status').value;
            const notes       = document.getElementById('notes').value;

            if (!property_id || !user_id || !amount || !pay_date) {
                showToast('Please fill in all required fields', 'error');
                return;
            }

            if (parseFloat(amount) <= 0) {
                showToast('Amount must be greater than 0', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('property_id',    property_id);
            formData.append('user_id',        user_id);
            formData.append('amount',         amount);
            formData.append('payment_method', method);
            formData.append('payment_date',   pay_date.replace('T', ' ') + ':00');
            formData.append('status',         status);
            formData.append('notes',          notes);

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            fetch('actions/payments-create.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Payment recorded successfully! 🎉', 'success');
                    setTimeout(() => window.location.href = 'admin-payments.php', 1200);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Payment Record';
                    showToast(data.message || 'Failed to save', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Payment Record';
                showToast('Network error', 'error');
            });
        });
    </script>
</body>
</html>