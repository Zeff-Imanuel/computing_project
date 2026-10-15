<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\MessagesTable;
use Libs\Database\UsersTable;

// Check if logged in and is admin
$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

// ===== HANDLE ACTIONS =====
if (isset($_GET['mark_read'])) {
    $id = $_GET['mark_read'];
    $messages = new MessagesTable(new MySQL);
    $messages->markAsRead($id);
    header("Location: messages.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $messages = new MessagesTable(new MySQL);
    $messages->delete($id);
    header("Location: messages.php");
    exit();
}

$messages_table = new MessagesTable(new MySQL);
$users_table = new UsersTable(new MySQL);
$messages = $messages_table->all();
$message_stats = $messages_table->getStats();
$user_stats = $users_table->getStats();

// Get unread count
$unread_count = 0;
foreach ($messages as $msg) {
    if ($msg->is_read == 0) {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - KKG Property Admin</title>
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
        .stat-card.orange { border-color: var(--accent); }
        .stat-card.green { border-color: #22c55e; }

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

        .message-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        .message-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .message-card.unread {
            border-left-color: var(--accent);
            background: #fffbeb;
        }
        .message-card .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .message-card .header .sender {
            font-weight: 600;
            font-size: 1.05rem;
        }
        .message-card .header .sender i {
            color: var(--primary);
            margin-right: 8px;
        }
        .message-card .header .date {
            color: var(--gray);
            font-size: 0.85rem;
        }
        .message-card .subject {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 8px;
        }
        .message-card .message-body {
            color: var(--gray);
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        .message-card .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge-unread {
            background: var(--accent);
            color: var(--secondary);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .badge-read {
            background: #dcfce7;
            color: #166534;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- ===== NAVBAR - SAME AS ADMIN ===== -->
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
                        <a href="admin-properties.php" class="nav-link"><i class="fas fa-home"></i> Properties</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin-appointments.php" class="nav-link"><i class="fas fa-calendar"></i> Appointments</a>
                    </li>
                    <li class="nav-item">
    <a href="admin-payments.php" class="nav-link"><i class="fas fa-dollar-sign"></i> Payments</a>
</li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link active"><i class="fas fa-envelope"></i> Messages</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-envelope text-primary"></i> Messages</h4>
                <small class="text-muted">View and manage all contact messages</small>
            </div>
            <a href="admin.php" class="btn btn-outline-custom">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4 fade-in">
            <div class="col-md-4">
                <div class="stat-card blue">
                    <div class="number"><?= $message_stats->total ?? 0 ?></div>
                    <div class="label"><i class="fas fa-envelope"></i> Total Messages</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card orange">
                    <div class="number"><?= $message_stats->unread ?? 0 ?></div>
                    <div class="label"><i class="fas fa-circle text-warning"></i> Unread</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card green">
                    <div class="number"><?= $message_stats->read_count ?? 0 ?></div>
                    <div class="label"><i class="fas fa-check-circle text-success"></i> Read</div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (empty($messages)): ?>
            <div class="alert alert-info text-center py-5 fade-in">
                <i class="fas fa-inbox fa-3x mb-3 d-block text-muted"></i>
                <h5>No messages yet</h5>
                <p class="text-muted">When users send messages, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="fade-in">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-card <?= $msg->is_read == 0 ? 'unread' : '' ?>">
                        <div class="header">
                            <div class="sender">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars($msg->name) ?>
                                <?php if ($msg->is_read == 0): ?>
                                    <span class="badge-unread"><i class="fas fa-circle"></i> New</span>
                                <?php else: ?>
                                    <span class="badge-read"><i class="fas fa-check"></i> Read</span>
                                <?php endif; ?>
                            </div>
                            <div class="date">
                                <i class="fas fa-clock"></i> <?= date('F j, Y g:i A', strtotime($msg->created_at)) ?>
                            </div>
                        </div>
                        <div class="subject">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($msg->subject) ?>
                        </div>
                        <div class="message-body">
                            <i class="fas fa-quote-left text-muted me-1"></i>
                            <?= nl2br(htmlspecialchars($msg->message)) ?>
                        </div>
                        <div class="actions">
                            <div>
                                <span class="text-muted small">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($msg->email) ?>
                                </span>
                            </div>
                            <div class="ms-auto">
                                <?php if ($msg->is_read == 0): ?>
                                    <a href="messages.php?mark_read=<?= $msg->id ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                <?php endif; ?>
                                <a href="messages.php?delete=<?= $msg->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <footer class="text-center text-muted py-3 mt-4">
        <small>&copy; <?= date('Y') ?> KKG Property Admin Panel</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>