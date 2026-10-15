<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$user = Auth::check();

// Get user's properties (if agent)
$user_properties = [];
if ($user->role_id >= 2) {
    try {
        $properties_table = new PropertiesTable(new MySQL);
        $all_properties = $properties_table->all();
        foreach ($all_properties as $prop) {
            if ($prop->user_id == $user->id) {
                $user_properties[] = $prop;
            }
        }
    } catch (Exception $e) {
        $user_properties = [];
    }
}

$is_logged_in = true;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Myanmar Real Estate</title>
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
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 16px;
        }

        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }

        /* ===== PROFILE HEADER ===== */
        .profile-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 50px 0 60px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(37, 99, 235, 0.08);
            border-radius: 50%;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: var(--shadow-lg);
        }
        .profile-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 700;
            box-shadow: var(--shadow-lg);
            margin: 0 auto;
        }
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .upload-btn-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .badge-role {
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        .badge-role.admin { background: #ef4444; color: white; }
        .badge-role.agent { background: var(--primary); color: white; }
        .badge-role.user { background: #64748b; color: white; }
        
        .badge-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status.active { background: #dcfce7; color: #166534; }
        .badge-status.suspended { background: #fee2e2; color: #991b1b; }

        /* ===== PROFILE CARDS ===== */
        .profile-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px;
            margin-top: -30px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .profile-card .card-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--light-gray);
        }
        .profile-card .card-title i {
            color: var(--primary);
            margin-right: 8px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-item:last-child { border-bottom: none; }
        .info-item .icon {
            width: 38px;
            height: 38px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .info-item .label {
            color: var(--gray);
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-item .value {
            font-weight: 600;
            font-size: 1rem;
            color: var(--secondary);
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
            box-shadow: var(--shadow);
        }
        .btn-danger-custom {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-danger-custom:hover {
            background: #dc2626;
            transform: translateY(-2px);
            color: white;
            box-shadow: var(--shadow);
        }

        .footer {
            background: var(--secondary);
            color: rgba(255,255,255,0.7);
            padding: 30px 0;
            margin-top: 40px;
        }
        .footer a { color: rgba(255,255,255,0.7); text-decoration: none; }
        .footer a:hover { color: white; }

        @media (max-width: 768px) {
            .profile-card { padding: 20px; margin-top: -20px; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- ===== PROFILE HEADER ===== -->
    <div class="profile-header text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php if ($user->photo): ?>
                        <img src="actions/photos/<?= $user->photo ?>" alt="Profile" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <?= strtoupper(substr($user->name, 0, 1)) ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="mt-3 fw-bold"><?= htmlspecialchars($user->name) ?></h2>
                    
                    <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mt-2">
                        <span class="badge-role <?= $user->role_id == 3 ? 'admin' : ($user->role_id == 2 ? 'agent' : 'user') ?>">
                            <i class="fas <?= $user->role_id == 3 ? 'fa-shield-alt' : ($user->role_id == 2 ? 'fa-user-tie' : 'fa-user') ?>"></i>
                            <?= ucfirst($user->role ?? 'User') ?>
                        </span>
                        <span class="badge-status <?= $user->suspended ? 'suspended' : 'active' ?>">
                            <i class="fas <?= $user->suspended ? 'fa-ban' : 'fa-check-circle' ?>"></i>
                            <?= $user->suspended ? 'Suspended' : 'Active' ?>
                        </span>
                    </div>

                    <p class="text-white-50 mt-2">
                        <i class="fas fa-calendar-alt"></i> Member since <?= date('F Y', strtotime($user->created_at)) ?>
                    </p>

                    <form action="actions/upload.php" method="post" enctype="multipart/form-data" class="mt-2">
                        <div class="upload-btn-wrapper">
                            <button class="btn btn-outline-light btn-sm">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                            <input type="file" name="photo" accept="image/*" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PROFILE CONTENT ===== -->
    <div class="container">
        <div class="row justify-content-center">

            <!-- Profile Info - Centered -->
            <div class="col-lg-8">
                <div class="profile-card">
                    <h5 class="card-title"><i class="fas fa-user-circle"></i> Personal Information</h5>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="label">Full Name</div>
                            <div class="value"><?= htmlspecialchars($user->name) ?></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="label">Email Address</div>
                            <div class="value"><?= htmlspecialchars($user->email) ?></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="label">Phone Number</div>
                            <div class="value"><?= htmlspecialchars($user->phone) ?></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="label">Address</div>
                            <div class="value"><?= htmlspecialchars($user->address) ?></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-id-badge"></i></div>
                        <div>
                            <div class="label">User ID</div>
                            <div class="value">#<?= $user->id ?></div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4 d-flex gap-2 flex-wrap">

                        <!-- ✅ EDIT BUTTON (First, Blue, Non-functional) -->
                        <button type="button" class="btn-primary-custom" disabled style="opacity:0.6; cursor:not-allowed;">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>

                        <a href="actions/logout.php" class="btn-danger-custom">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>

                        <?php if ($user->role_id == 3): ?>
                            <a href="admin.php" class="btn-primary-custom">
                                <i class="fas fa-chart-line"></i> Admin Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>