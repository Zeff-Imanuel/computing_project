<?php
session_start();
include("vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\PropertyPhotosTable;

$id = $_GET['id'] ?? 0;

$properties_table = new PropertiesTable(new MySQL);
$property = $properties_table->getById($id);

if (!$property) {
    header("Location: home.php");
    exit();
}

$photo_table = new PropertyPhotosTable(new MySQL);
$photos = $photo_table->getByProperty($id);

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
$is_owner = $is_logged_in && $user->id == $property->user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property->title) ?> - Myanmar Real Estate</title>
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
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }

        .detail-header {
            background: var(--secondary);
            padding: 40px 0 30px;
            color: white;
        }
        .detail-header h1 {
            font-size: 2rem;
            font-weight: 800;
        }
        .detail-header .location {
            color: rgba(255,255,255,0.7);
        }

        .detail-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-top: -20px;
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: var(--radius);
        }
        .thumbnail {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: 0.3s;
        }
        .thumbnail:hover, .thumbnail.active {
            border-color: var(--primary);
        }

        .price-display {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .info-item {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-item:last-child { border-bottom: none; }
        .info-item .label { color: var(--gray); font-size: 0.85rem; }
        .info-item .value { font-weight: 600; }

        /* ✅ BOOK APPOINTMENT BUTTON */
        .btn-book {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-book:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.3);
            color: white;
        }

        .btn-contact {
            background: var(--primary);
            color: white;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-contact:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .btn-login-prompt {
            background: #fef3c7;
            color: #92400e;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px dashed #f59e0b;
            transition: all 0.3s;
        }
        .btn-login-prompt:hover {
            background: #fde68a;
            color: #78350f;
        }

        .agent-info {
            background: var(--light-gray);
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 20px;
        }
        .agent-info .agent-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .footer {
            background: var(--secondary);
            color: rgba(255,255,255,0.7);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        .footer a { color: rgba(255,255,255,0.7); text-decoration: none; }
        .footer a:hover { color: white; }

        @media (max-width: 768px) {
            .main-image { height: 250px; }
            .detail-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- ===== HEADER ===== -->
    <section class="detail-header">
        <div class="container">
            <h1><?= htmlspecialchars($property->title) ?></h1>
            <div class="location">
                <i class="fas fa-map-marker-alt"></i> 
                <?= htmlspecialchars($property->address) ?>, 
                <?= htmlspecialchars($property->city) ?>, 
                <?= $property->state ?>
            </div>
        </div>
    </section>

    <!-- ===== DETAIL CONTENT ===== -->
    <section class="container">
        <div class="detail-card">

            <div class="row g-4">
                <!-- Images -->
                <div class="col-lg-8">
                    <?php if ($property->photo): ?>
                        <img src="/project/images/<?= $property->photo ?>" 
                             alt="<?= $property->title ?>" 
                             class="main-image" id="mainImage">
                    <?php else: ?>
                        <img src="/project/images/default.jpg" 
                             alt="Property" 
                             class="main-image" id="mainImage">
                    <?php endif; ?>

                    <?php if (!empty($photos)): ?>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <?php foreach ($photos as $photo): ?>
                                <img src="/project/images/<?= $photo->photo_path ?>" 
                                     alt="Photo" 
                                     class="thumbnail" 
                                     onclick="document.getElementById('mainImage').src='/project/images/<?= $photo->photo_path ?>'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="mt-4">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-info-circle text-primary"></i> Description
                        </h5>
                        <p class="text-muted" style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($property->description)) ?>
                        </p>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="col-lg-4">

                    <!-- Price -->
                    <div class="price-display">
                        $<?= number_format($property->price, 0) ?>
                        <?php if ($property->listing_type == 'rent'): ?>
                            <span style="font-size: 1rem; color: var(--gray); font-weight: 400;">/month</span>
                        <?php endif; ?>
                    </div>

                    <span class="badge bg-<?= $property->listing_type == 'sale' ? 'primary' : 'warning' ?> mb-3">
                        <?= strtoupper($property->listing_type) ?>
                    </span>

                    <hr>

                    <!-- Property Details -->
                    <div class="info-item">
                        <div class="label">Property Type</div>
                        <div class="value"><?= ucfirst($property->property_type) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Bedrooms</div>
                        <div class="value"><i class="fas fa-bed text-primary"></i> <?= $property->bedrooms ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Bathrooms</div>
                        <div class="value"><i class="fas fa-bath text-primary"></i> <?= $property->bathrooms ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Area</div>
                        <div class="value"><i class="fas fa-vector-square text-primary"></i> <?= $property->area_sqft ?> sqft</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Status</div>
                        <div class="value">
                            <span class="badge bg-<?= $property->status == 'available' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($property->status) ?>
                            </span>
                        </div>
                    </div>

                    <!-- ✅ BOOK APPOINTMENT BUTTON -->
                    <div class="mt-4">
                        <?php if (!$is_logged_in): ?>
                            <!-- Not logged in -->
                            <a href="index.php" class="btn-login-prompt">
                                <i class="fas fa-lock"></i> Login to Book Appointment
                            </a>
                        <?php elseif ($is_owner): ?>
                            <!-- Owner cannot book own property -->
                            <div class="alert alert-info text-center mb-0">
                                <i class="fas fa-info-circle"></i> 
                                This is your property
                            </div>
                        <?php else: ?>
                            <!-- ✅ LOGGED IN USER - SHOW BOOK BUTTON -->
                            <a href="appointment.php?property_id=<?= $property->id ?>" class="btn-book">
                                <i class="fas fa-calendar-check"></i> Book Appointment
                            </a>
                        <?php endif; ?>

                        <a href="contact.php" class="btn-contact mt-2">
                            <i class="fas fa-envelope"></i> Contact Agent
                        </a>
                    </div>

                    <!-- Agent Info -->
                    <div class="agent-info">
                        <div class="d-flex align-items-center gap-3">
                            <div class="agent-avatar">
                                <?= strtoupper(substr($property->agent_name ?? 'A', 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($property->agent_name ?? 'Agent') ?></div>
                                <small class="text-muted">Property Agent</small>
                            </div>
                        </div>
                        <hr>
                        <div class="small text-muted">
                            <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($property->email ?? 'N/A') ?></div>
                            <div class="mt-1"><i class="fas fa-phone"></i> <?= htmlspecialchars($property->phone ?? 'N/A') ?></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <a href="home.php" class="brand" style="color: white; text-decoration: none; font-weight: 800;">
                        Myanmar Real Estate
                    </a>
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
            <div class="bottom text-center" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; margin-top: 30px;">
                <p>&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>