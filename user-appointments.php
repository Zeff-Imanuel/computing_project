<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\AppointmentsTable;

$user = Auth::check();

$appointments_table = new AppointmentsTable(new MySQL);
$appointments = $appointments_table->getByUser($user->id);

$is_logged_in = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Myanmar Real Estate</title>
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
            --border-color: #e2e8f0;
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
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
        }
        .page-header h1 span { color: var(--accent); }
        .page-header p {
            color: rgba(255,255,255,0.7);
        }

        .appointments-container {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }

        .appointment-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            margin-bottom: 15px;
            transition: all 0.3s;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .appointment-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .appointment-card.pending { border-left-color: #f59e0b; }
        .appointment-card.confirmed { border-left-color: #22c55e; }
        .appointment-card.completed { border-left-color: #06b6d4; }
        .appointment-card.cancelled { border-left-color: #ef4444; opacity: 0.7; }

        .appointment-card img {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .appointment-card .no-image {
            width: 100px;
            height: 80px;
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
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .appointment-card .info .location {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        .appointment-card .info .datetime {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .appointment-card .info .message {
            color: var(--gray);
            font-size: 0.85rem;
            margin-top: 8px;
            font-style: italic;
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

        .empty-state {
            text-align: center;
            padding: 60px 0;
        }
        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
        }
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .footer {
            background: #0f172a;
            color: rgba(255,255,255,0.7);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        .footer .brand {
            font-size: 1.3rem;
            font-weight: 900;
            color: white;
            text-decoration: none;
        }
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 4px 0;
            display: block;
            font-size: 0.9rem;
        }
        .footer a:hover { color: white; }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.8rem; }
            .appointment-card { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <section class="page-header">
        <div class="container">
            <h1>My <span>Appointments</span></h1>
            <p>Track your property viewing appointments</p>
        </div>
    </section>

    <div class="container">
        <div class="appointments-container">

            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4 class="fw-bold">No Appointments Yet</h4>
                    <p class="text-muted">You haven't booked any property viewings.</p>
                    <a href="sale.php" class="btn-primary-custom mt-2">
                        <i class="fas fa-search"></i> Browse Properties
                    </a>
                </div>
            <?php else: ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-calendar-check text-primary"></i> 
                        Your Appointments (<?= count($appointments) ?>)
                    </h5>
                    <a href="sale.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus"></i> Book New
                    </a>
                </div>

                <?php foreach ($appointments as $apt): ?>
                    <div class="appointment-card <?= $apt->status ?>">
                        <?php if ($apt->property_photo): ?>
                            <img src="/project/images/<?= $apt->property_photo ?>" alt="Property">
                        <?php else: ?>
                            <div class="no-image"><i class="fas fa-home"></i></div>
                        <?php endif; ?>

                        <div class="info">
                            <div class="title"><?= htmlspecialchars($apt->property_title ?? 'Property') ?></div>
                            <div class="location">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?= htmlspecialchars($apt->property_city ?? 'N/A') ?>
                            </div>
                            <div class="datetime">
                                <i class="fas fa-calendar"></i> 
                                <?= date('F j, Y', strtotime($apt->appointment_date)) ?>
                                <i class="fas fa-clock ms-2"></i> 
                                <?= date('g:i A', strtotime($apt->appointment_date)) ?>
                            </div>
                            <?php if ($apt->message): ?>
                                <div class="message">
                                    <i class="fas fa-comment"></i> 
                                    <?= htmlspecialchars($apt->message) ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-user-tie"></i> 
                                    Agent: <?= htmlspecialchars($apt->agent_name ?? 'N/A') ?>
                                </small>
                            </div>
                        </div>

                        <div class="text-end">
                            <span class="badge-status <?= $apt->status ?>">
                                <?= ucfirst($apt->status) ?>
                            </span>
                            <div class="mt-2">
                                <a href="property-detail.php?id=<?= $apt->property_id ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Property
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <a href="home.php" class="brand">Myanmar Real Estate</a>
                    <p class="mt-2" style="color: rgba(255,255,255,0.6);">
                        Buy, Sell And Rent Properties Online.
                    </p>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white fw-bold">Quick Links</h5>
                    <a href="sale.php">For Sale</a>
                    <a href="rent.php">For Rent</a>
                    <a href="new-properties.php">New Properties</a>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white fw-bold">Resources</h5>
                    <a href="#">Property Guide</a>
                    <a href="#">News</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white fw-bold">Follow Us</h5>
                    <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                </div>
            </div>
            <div class="bottom text-center">
                <p>&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>