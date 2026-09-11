<?php
session_start();
include("vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$properties_table = new PropertiesTable(new MySQL);

$featured_properties = $properties_table->getFeatured(6);
$latest_properties = $properties_table->getAvailable(6);
$sale_properties = [];
$rent_properties = [];

foreach ($properties_table->getAvailable(10) as $property) {
    if ($property->listing_type == 'sale') {
        $sale_properties[] = $property;
    } else {
        $rent_properties[] = $property;
    }
}

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Myanmar Real Estate - Buy, Sell And Rent Properties Online</title>
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

        .hero {
            background: linear-gradient(rgba(15, 23, 42, 0.70), rgba(15, 23, 42, 0.80)), 
                        url('actions/photos/background1.webp') center/cover no-repeat;
            padding: 80px 0 100px;
            text-align: center;
            color: white;
            position: relative;
            min-height: 500px;
            display: flex;
            align-items: center;
        }
        .hero h1 {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .hero h1 span { color: var(--accent); }
        .hero .subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 1.1rem;
            margin-bottom: 30px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            max-width: 900px;
            margin: 0 auto;
        }
        .search-box .form-control,
        .search-box .form-select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.9rem;
            background: white;
            height: 44px;
        }
        .search-box .form-control:focus,
        .search-box .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }
        .search-box .search-input-group {
            position: relative;
        }
        .search-box .search-input-group .form-control {
            padding-left: 40px;
        }
        .search-box .search-input-group .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        .search-box .form-control::placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .search-box .btn-search {
            background: #1a56db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.9rem;
            width: 100%;
            height: 44px;
            transition: all 0.3s;
            letter-spacing: 1px;
        }
        .search-box .btn-search:hover {
            background: #1e40af;
            transform: scale(1.02);
            color: white;
        }

        .section-title {
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--secondary);
        }
        .section-title span { color: var(--primary); }

        .property-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            height: 100%;
            cursor: pointer;
        }
        .property-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .property-card .image {
            height: 200px;
            background: var(--light-gray);
            position: relative;
            overflow: hidden;
        }
        .property-card .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .property-card .image .badge-top {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        .property-card .image .badge-top.sale {
            background: var(--primary);
            color: white;
        }
        .property-card .image .badge-top.rent {
            background: var(--accent);
            color: var(--secondary);
        }
        .property-card .image .price-tag {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 15px 15px 10px;
            color: white;
        }
        .property-card .image .price-tag .price {
            font-size: 1.2rem;
            font-weight: 700;
        }
        .property-card .body {
            padding: 14px 16px;
        }
        .property-card .body .title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        .property-card .body .location {
            color: var(--gray);
            font-size: 0.85rem;
        }
        .property-card .body .features {
            display: flex;
            gap: 14px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--gray);
        }
        .property-card .body .features i {
            color: var(--primary);
            margin-right: 4px;
        }

        .category-link {
            text-decoration: none;
            color: var(--secondary);
            display: block;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s;
            font-weight: 500;
        }
        .category-link:last-child { border-bottom: none; }
        .category-link:hover { color: var(--primary); padding-left: 8px; }
        .category-link .badge-count {
            float: right;
            background: var(--light-gray);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .category-link i { width: 24px; }

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
        .footer .brand span { color: var(--accent); }
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 4px 0;
            display: block;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .footer a:hover { color: white; }
        .footer .bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 20px;
            margin-top: 30px;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 1.8rem; }
            .hero { padding: 50px 0 60px; min-height: 350px; }
            .search-box { padding: 15px; }
            .section-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="container">
            <h1>Real Estate in Myanmar</h1>
            <p class="subtitle">Buy, Sell, Rent Property</p>

            <div class="search-box">
                <form action="sale.php" method="get">
                    <div class="row g-2 mb-2">
                        <div class="col-lg-6">
                            <div class="search-input-group">
                                <span class="search-icon"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Eg. Search Ad title or ID" name="search">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <select class="form-select" name="listing_type">
                                <option value="">For Sale / Rent</option>
                                <option value="sale">For Sale</option>
                                <option value="rent">For Rent</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <select class="form-select" name="city">
                                <option value="">All Cities</option>
                                <option value="Yangon">Yangon</option>
                                <option value="Mandalay">Mandalay</option>
                                <option value="Naypyidaw">Naypyidaw</option>
                                <option value="Bago">Bago</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-lg-3">
                            <select class="form-select" name="type">
                                <option value="">All Property Types</option>
                                <option value="house">House</option>
                                <option value="apartment">Apartment</option>
                                <option value="condo">Condo</option>
                                <option value="villa">Villa</option>
                                <option value="land">Land</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select class="form-select" name="bedrooms">
                                <option value="">Bedrooms</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                                <option value="4">4+</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select class="form-select" name="price_min">
                                <option value="">Price (From)</option>
                                <option value="10000">$10,000</option>
                                <option value="50000">$50,000</option>
                                <option value="100000">$100,000</option>
                                <option value="200000">$200,000</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select class="form-select" name="price_max">
                                <option value="">Price (To)</option>
                                <option value="50000">$50,000</option>
                                <option value="100000">$100,000</option>
                                <option value="200000">$200,000</option>
                                <option value="500000">$500,000</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i> SEARCH
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ===== FEATURED PROPERTIES ===== -->
    <section class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Featured <span>Properties</span></h2>
            <a href="sale.php" class="text-primary fw-bold text-decoration-none">View All <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if (empty($featured_properties)): ?>
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                No featured properties yet.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach (array_slice($featured_properties, 0, 6) as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <!-- ✅ CLICKABLE CARD -->
                        <div class="property-card" 
                             onclick="window.location.href='property-detail.php?id=<?= $property->id ?>'">
                            <div class="image">
                                <?php if ($property->photo): ?>
                                    <img src="/project/images/<?= $property->photo ?>" alt="<?= $property->title ?>">
                                <?php else: ?>
                                    <img src="/project/images/default.jpg" alt="Property">
                                <?php endif; ?>
                                <span class="badge-top <?= $property->listing_type ?>">
                                    <?= strtoupper($property->listing_type) ?>
                                </span>
                                <div class="price-tag">
                                    <div class="price">$<?= number_format($property->price, 0) ?></div>
                                </div>
                            </div>
                            <div class="body">
                                <div class="title"><?= htmlspecialchars($property->title) ?></div>
                                <div class="location">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property->city) ?>, <?= $property->state ?>
                                </div>
                                <div class="features">
                                    <span><i class="fas fa-bed"></i> <?= $property->bedrooms ?></span>
                                    <span><i class="fas fa-bath"></i> <?= $property->bathrooms ?></span>
                                    <span><i class="fas fa-vector-square"></i> <?= $property->area_sqft ?> sqft</span>
                                </div>
                                <a href="property-detail.php?id=<?= $property->id ?>" 
                                   class="btn btn-primary-custom btn-sm w-100 mt-2"
                                   onclick="event.stopPropagation();">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===== LATEST PROPERTIES ===== -->
    <section class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Latest <span>Listings</span></h2>
            <a href="new-properties.php" class="text-primary fw-bold text-decoration-none">View All <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if (empty($latest_properties)): ?>
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                No properties listed yet.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach (array_slice($latest_properties, 0, 6) as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <!-- ✅ CLICKABLE CARD -->
                        <div class="property-card" 
                             onclick="window.location.href='property-detail.php?id=<?= $property->id ?>'">
                            <div class="image">
                                <?php if ($property->photo): ?>
                                    <img src="/project/images/<?= $property->photo ?>" alt="<?= $property->title ?>">
                                <?php else: ?>
                                    <img src="/project/images/default.jpg" alt="Property">
                                <?php endif; ?>
                                <span class="badge-top <?= $property->listing_type ?>">
                                    <?= strtoupper($property->listing_type) ?>
                                </span>
                                <div class="price-tag">
                                    <div class="price">$<?= number_format($property->price, 0) ?></div>
                                </div>
                            </div>
                            <div class="body">
                                <div class="title"><?= htmlspecialchars($property->title) ?></div>
                                <div class="location">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property->city) ?>, <?= $property->state ?>
                                </div>
                                <div class="features">
                                    <span><i class="fas fa-bed"></i> <?= $property->bedrooms ?></span>
                                    <span><i class="fas fa-bath"></i> <?= $property->bathrooms ?></span>
                                    <span><i class="fas fa-vector-square"></i> <?= $property->area_sqft ?> sqft</span>
                                </div>
                                <a href="property-detail.php?id=<?= $property->id ?>" 
                                   class="btn btn-primary-custom btn-sm w-100 mt-2"
                                   onclick="event.stopPropagation();">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===== CATEGORIES ===== -->
    <section class="container py-3">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <a href="sale.php" class="category-link">
                    <i class="fas fa-home text-primary"></i> For Sale 
                    <span class="badge-count"><?= count($sale_properties) ?></span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="rent.php" class="category-link">
                    <i class="fas fa-key text-warning"></i> For Rent 
                    <span class="badge-count"><?= count($rent_properties) ?></span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="new-properties.php" class="category-link">
                    <i class="fas fa-star text-success"></i> New Properties
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="user-appointments.php" class="category-link">
                    <i class="fas fa-calendar text-danger"></i> Appointments
                </a>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <a href="home.php" class="brand">Myanmar Real Estate</a>
                    <p class="mt-2" style="color: rgba(255,255,255,0.6);">
                        Buy, Sell And Rent Properties Online. Myanmar's trusted real estate platform.
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
                <div class="col-lg-2 col-6">
                    <h5 class="text-white fw-bold">Support</h5>
                    <a href="#"><i class="fas fa-phone-alt"></i> +95 9 123 456 789</a>
                    <a href="#"><i class="fas fa-envelope"></i> info@myanmarrealestate.com</a>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white fw-bold">Follow Us</h5>
                    <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                </div>
            </div>
            <div class="bottom text-center">
                <p>&copy; <?= date('Y') ?> Myanmar Real Estate. All rights reserved. | Buy, Sell And Rent Properties Online</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>