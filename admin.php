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
use Libs\Database\PaymentsTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$users_table        = new UsersTable(new MySQL);
$messages_table     = new MessagesTable(new MySQL);
$properties_table   = new PropertiesTable(new MySQL);
$appointments_table = new AppointmentsTable(new MySQL);
$payments_table     = new PaymentsTable(new MySQL);

$user_stats         = $users_table->getStats();
$message_stats      = $messages_table->getStats();
$property_stats     = $properties_table->getStats();
$appointment_stats  = $appointments_table->getStats();
$payment_stats      = $payments_table->getStats();

$unread_count          = $messages_table->getUnreadCount();
$pending_appointments  = $appointments_table->getPendingCount();

// Revenue stats
$revenue_stats = $properties_table->getRevenueStats();

// Monthly revenue chart data
$monthly_revenue_rows = $properties_table->getMonthlyRevenue(12);
$revenue_map = [];
foreach ($monthly_revenue_rows as $r) { $revenue_map[$r->month] = $r; }

$chart_months = [];
$chart_revenue_sales   = [];
$chart_revenue_rentals = [];

for ($i = 11; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $chart_months[] = $label;
    $chart_revenue_sales[]   = isset($revenue_map[$key]) ? (float)$revenue_map[$key]->sales_revenue   : 0;
    $chart_revenue_rentals[] = isset($revenue_map[$key]) ? (float)$revenue_map[$key]->rental_revenue  : 0;
}

$is_admin = true;
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
            height: 100%;
        }
        .chart-card h6 {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 15px;
        }

        .section-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            height: 100%;
        }
        .section-card h5 {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--light-gray);
        }
        .section-card h5 i { color: var(--primary); margin-right: 8px; }

        /* ===== REPORT GENERATION ===== */
        .report-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--secondary);
            background: white;
            border: 2px solid var(--light-gray);
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .report-btn:hover {
            border-color: #22c55e;
            color: #16a34a;
            background: #f0fdf4;
            transform: translateY(-2px);
        }
        .report-btn i {
            width: 30px;
            height: 30px;
            background: #f0fdf4;
            color: #16a34a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .report-btn:hover i {
            background: #16a34a;
            color: white;
        }
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
                        <a href="admin-payments.php" class="nav-link"><i class="fas fa-dollar-sign"></i> Payments</a>
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

    <div class="container mt-4">

        <!-- Welcome -->
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-chart-line fs-4 me-3"></i>
            <div>
                <h5 class="mb-0">Welcome back, <?= htmlspecialchars($auth->name) ?>! 👋</h5>
                <small>Here's your reports and analytics dashboard</small>
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

        <!-- ===== REVENUE CARDS ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="number">$<?= number_format((float)$revenue_stats->total_sales, 0) ?></div>
                    <div class="label"><i class="fas fa-handshake"></i> Total Sales Revenue</div>
                    <i class="fas fa-dollar-sign icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card teal">
                    <div class="number">$<?= number_format((float)$revenue_stats->total_rentals, 0) ?></div>
                    <div class="label"><i class="fas fa-key"></i> Total Rental Income</div>
                    <i class="fas fa-coins icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="number">$<?= number_format((float)($revenue_stats->year_sales ?? 0) + (float)($revenue_stats->year_rentals ?? 0), 0) ?></div>
                    <div class="label"><i class="fas fa-calendar-check"></i> This Year Revenue</div>
                    <i class="fas fa-chart-line icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="number">$<?= number_format((float)($revenue_stats->month_revenue ?? 0), 0) ?></div>
                    <div class="label"><i class="fas fa-calendar-day"></i> This Month Revenue</div>
                    <i class="fas fa-clock icon-bg"></i>
                </div>
            </div>
        </div>

        <!-- ===== CHARTS ROW ===== -->
        <div class="row g-4 mb-4">
            <div class="col-md-5">
                <div class="chart-card">
                    <h6><i class="fas fa-chart-pie text-primary"></i> Property Status Breakdown</h6>
                    <div style="height: 260px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="chart-card">
                    <h6><i class="fas fa-dollar-sign text-success"></i> Monthly Revenue — Sales vs Rentals (Last 12 Months)</h6>
                    <div style="height: 260px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== REPORT GENERATION ===== -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="section-card">
                    <h5><i class="fas fa-file-csv"></i> Generate Reports</h5>

                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle"></i>
                        Download data as CSV files for use in Excel or other tools.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=users" class="report-btn">
                                <i class="fas fa-users"></i>
                                Users Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=properties" class="report-btn">
                                <i class="fas fa-home"></i>
                                Properties Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=appointments" class="report-btn">
                                <i class="fas fa-calendar"></i>
                                Appointments Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=payments" class="report-btn">
                                <i class="fas fa-dollar-sign"></i>
                                Payments Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=messages" class="report-btn">
                                <i class="fas fa-envelope"></i>
                                Messages Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=property_status" class="report-btn">
                                <i class="fas fa-chart-pie"></i>
                                Property Status
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=property_types" class="report-btn">
                                <i class="fas fa-building"></i>
                                Property Types
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=monthly_sales" class="report-btn">
                                <i class="fas fa-chart-line"></i>
                                Monthly Sales
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=revenue" class="report-btn">
                                <i class="fas fa-coins"></i>
                                Revenue Report
                            </a>
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <a href="export.php?type=monthly_payments" class="report-btn">
                                <i class="fas fa-receipt"></i>
                                Monthly Payments
                            </a>
                        </div>
                    </div>
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
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } }
                }
            }
        });

        // ===== MONTHLY REVENUE =====
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_months) ?>,
                datasets: [
                    {
                        label: 'Sales Revenue',
                        data: <?= json_encode($chart_revenue_sales) ?>,
                        backgroundColor: '#22c55e',
                        borderRadius: 6,
                        stack: 'revenue'
                    },
                    {
                        label: 'Rental Income',
                        data: <?= json_encode($chart_revenue_rentals) ?>,
                        backgroundColor: '#06b6d4',
                        borderRadius: 6,
                        stack: 'revenue'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { padding: 15 } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        callbacks: {
                            label: function(ctx) {
                                return ` ${ctx.dataset.label}: $${Number(ctx.parsed.y).toLocaleString()}`;
                            },
                            footer: function(items) {
                                const total = items.reduce((s, i) => s + i.parsed.y, 0);
                                return 'Total: $' + total.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => '$' + Number(v).toLocaleString() }
                    },
                    x: { stacked: true, grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>