<?php
// nav.php - Simple white navbar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand fw-bold text-primary" href="home.php">
            Ko Kyaw Gyi Property
            <small class="d-block text-secondary" style="font-size: 11px;">
                Buy, Sell And Rent Properties Online
            </small>
        </a>

        <!-- Mobile Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">

            <!-- Navigation -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="home.php" class="nav-link <?= $current_page == 'home.php' ? 'active fw-bold' : '' ?>">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="sale.php" class="nav-link <?= $current_page == 'sale.php' ? 'active fw-bold' : '' ?>">
                        For Sale
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rent.php" class="nav-link <?= $current_page == 'rent.php' ? 'active fw-bold' : '' ?>">
                        For Rent
                    </a>
                </li>
                <li class="nav-item">
                    <a href="new-properties.php" class="nav-link <?= $current_page == 'new-properties.php' ? 'active fw-bold' : '' ?>">
                        New Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user-appointments.php" class="nav-link <?= $current_page == 'user-appointments.php' ? 'active fw-bold' : '' ?>">
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="about.php" class="nav-link <?= $current_page == 'about.php' ? 'active fw-bold' : '' ?>">
                        About Us
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contact.php" class="nav-link <?= $current_page == 'contact.php' ? 'active fw-bold' : '' ?>">
                        Contact
                    </a>
                </li>
                <?php if ($is_logged_in && ($user->role_id == 2 || $user->role_id == 3)): ?>
                    <li class="nav-item">
                        <a href="add-property.php" class="nav-link <?= $current_page == 'add-property.php' ? 'active fw-bold' : '' ?>">
                            Add Property
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- User Buttons -->
            <?php if ($is_logged_in): ?>
                <div class="d-flex align-items-center ms-3">
                    <a href="profile.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <?= htmlspecialchars($user->name) ?>
                    </a>

                    <?php if ($user->role_id == 3): ?>
                        <!-- Admin -->
                        <a href="admin.php" class="btn btn-primary btn-sm rounded-pill px-3 ms-2">
                            Admin
                        </a>
                    <?php elseif ($user->role_id == 2): ?>
                        <!-- Agent -->
                        <a href="agent-appointments.php" class="btn btn-primary btn-sm rounded-pill px-3 ms-2">
                            Agent Panel
                        </a>
                    <?php endif; ?>

                    <a href="actions/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-2">
                        Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="ms-3">
                    <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        Login
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm rounded-pill px-3 ms-2">
                        Register
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</nav>

<style>
    .navbar {
        padding: 12px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .navbar-brand {
        line-height: 1.2;
    }

    .navbar .nav-link {
        color: #000307;
        margin: 0 2px;
        padding: 8px 11px;
        border-radius: 6px;
        transition: 0.2s;
        font-size: 0.9rem;
    }

    .navbar .nav-link:hover {
        color: #0d6efd;
        background: #f1f5ff;
    }

    .navbar .nav-link.active {
        color: #0d6efd;
        background: #f1f5ff;
    }

    .navbar .btn {
        transition: 0.2s;
        font-size: 0.85rem;
    }

    .navbar .btn:hover {
        transform: translateY(-1px);
    }

    @media (max-width: 992px) {
        .navbar .nav-link {
            padding: 10px 14px;
            margin: 2px 0;
        }
        .navbar .btn {
            width: 100%;
            text-align: center;
            margin: 4px 0 !important;
        }
        .navbar .d-flex {
            flex-direction: column;
            width: 100%;
            align-items: stretch !important;
        }
        .navbar .ms-3 {
            margin-left: 0 !important;
        }
    }
</style>