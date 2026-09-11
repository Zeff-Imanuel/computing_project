<?php
session_start();
include("vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$properties_table = new PropertiesTable(new MySQL);

$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';
$type = $_GET['type'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$bathrooms = $_GET['bathrooms'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$properties = $properties_table->search($search, $type, $city, 'rent', $bedrooms, $price_min, $price_max);

if (!empty($bathrooms)) {
    $properties = array_filter($properties, fn($p) => $p->bathrooms >= $bathrooms);
    $properties = array_values($properties);
}

if ($sort === 'price_low') {
    usort($properties, fn($a, $b) => $a->price - $b->price);
} elseif ($sort === 'price_high') {
    usort($properties, fn($a, $b) => $b->price - $a->price);
}

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties For Rent - Myanmar Real Estate</title>
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
            padding: 50px 0 30px;
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
            font-size: 1.1rem;
        }

        .search-form-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px 30px;
            box-shadow: var(--shadow-lg);
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        .search-form-container .form-control,
        .search-form-container .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            height: 44px;
        }
        .search-form-container .form-control:focus,
        .search-form-container .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }
        .search-form-container .btn-search {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            width: 100%;
            height: 44px;
            transition: all 0.3s;
        }
        .search-form-container .btn-search:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }
        .search-form-container .btn-reset {
            background: transparent;
            color: var(--gray);
            border: 2px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            height: 44px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .search-form-container .btn-reset:hover {
            background: var(--light-gray);
            color: var(--secondary);
        }

        .filters-bar {
            background: white;
            border-radius: var(--radius);
            padding: 15px 20px;
            box-shadow: var(--shadow);
            margin: 25px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filters-bar .results-count {
            font-weight: 600;
            color: var(--secondary);
        }
        .filters-bar .results-count span { color: var(--primary); }
        .sort-dropdown {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* ===== PROPERTY CARD (CLICKABLE) ===== */
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
            height: 220px;
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
            font-size: 1.3rem;
            font-weight: 700;
        }
        .property-card .body { padding: 16px 18px; }
        .property-card .body .title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 4px;
        }
        .property-card .body .location {
            color: var(--gray);
            font-size: 0.85rem;
        }
        .property-card .body .features {
            display: flex;
            gap: 14px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--gray);
        }
        .property-card .body .features i {
            color: var(--primary);
            margin-right: 4px;
        }
        .property-card .body .agent {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }
        .property-card .body .agent .name {
            font-size: 0.85rem;
            font-weight: 500;
        }
        .property-card .body .agent .role {
            font-size: 0.7rem;
            color: var(--gray);
        }

        .no-properties {
            text-align: center;
            padding: 60px 0;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .no-properties i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
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
        .footer .bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 20px;
            margin-top: 30px;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.8rem; }
            .search-form-container { padding: 15px; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <section class="page-header">
        <div class="container">
            <h1>Properties For <span>Rent</span></h1>
            <p>Find the perfect rental property for you</p>
        </div>
    </section>

    <div class="container">
        <div class="search-form-container">
            <form action="rent.php" method="get">
                <div class="row g-2 mb-2">
                    <div class="col-lg-6">
                        <input type="text" class="form-control" placeholder="Search by title, city..." 
                               name="search" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-lg-2">
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="house" <?= $type == 'house' ? 'selected' : '' ?>>House</option>
                            <option value="apartment" <?= $type == 'apartment' ? 'selected' : '' ?>>Apartment</option>
                            <option value="condo" <?= $type == 'condo' ? 'selected' : '' ?>>Condo</option>
                            <option value="villa" <?= $type == 'villa' ? 'selected' : '' ?>>Villa</option>
                            <option value="land" <?= $type == 'land' ? 'selected' : '' ?>>Land</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <select class="form-select" name="city">
                            <option value="">All Cities</option>
                            <option value="Yangon" <?= $city == 'Yangon' ? 'selected' : '' ?>>Yangon</option>
                            <option value="Mandalay" <?= $city == 'Mandalay' ? 'selected' : '' ?>>Mandalay</option>
                            <option value="Naypyidaw" <?= $city == 'Naypyidaw' ? 'selected' : '' ?>>Naypyidaw</option>
                            <option value="Bago" <?= $city == 'Bago' ? 'selected' : '' ?>>Bago</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <select class="form-select" name="bedrooms">
                            <option value="">Bedrooms</option>
                            <option value="1" <?= $bedrooms == '1' ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= $bedrooms == '2' ? 'selected' : '' ?>>2+</option>
                            <option value="3" <?= $bedrooms == '3' ? 'selected' : '' ?>>3+</option>
                            <option value="4" <?= $bedrooms == '4' ? 'selected' : '' ?>>4+</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-lg-2">
                        <select class="form-select" name="bathrooms">
                            <option value="">Bathrooms</option>
                            <option value="1" <?= $bathrooms == '1' ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= $bathrooms == '2' ? 'selected' : '' ?>>2+</option>
                            <option value="3" <?= $bathrooms == '3' ? 'selected' : '' ?>>3+</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select" name="price_min">
                            <option value="">Price (From)</option>
                            <option value="500" <?= $price_min == '500' ? 'selected' : '' ?>>$500/mo</option>
                            <option value="1000" <?= $price_min == '1000' ? 'selected' : '' ?>>$1,000/mo</option>
                            <option value="2000" <?= $price_min == '2000' ? 'selected' : '' ?>>$2,000/mo</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select" name="price_max">
                            <option value="">Price (To)</option>
                            <option value="1000" <?= $price_max == '1000' ? 'selected' : '' ?>>$1,000/mo</option>
                            <option value="2000" <?= $price_max == '2000' ? 'selected' : '' ?>>$2,000/mo</option>
                            <option value="5000" <?= $price_max == '5000' ? 'selected' : '' ?>>$5,000/mo</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> SEARCH
                        </button>
                    </div>
                    <div class="col-lg-2">
                        <a href="rent.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <section class="container py-4">
        <div class="filters-bar">
            <div class="results-count">
                <i class="fas fa-key text-primary"></i>
                <span><?= count($properties) ?></span> 
                <?= count($properties) == 1 ? 'property' : 'properties' ?> found
            </div>
            <form action="rent.php" method="get" class="d-flex align-items-center gap-2">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
                <input type="hidden" name="bedrooms" value="<?= htmlspecialchars($bedrooms) ?>">
                <input type="hidden" name="bathrooms" value="<?= htmlspecialchars($bathrooms) ?>">
                <input type="hidden" name="price_min" value="<?= htmlspecialchars($price_min) ?>">
                <input type="hidden" name="price_max" value="<?= htmlspecialchars($price_max) ?>">
                <label class="text-muted small mb-0">Sort:</label>
                <select name="sort" class="sort-dropdown" onchange="this.form.submit()">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price ↑</option>
                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price ↓</option>
                </select>
            </form>
        </div>

        <?php if (empty($properties)): ?>
            <div class="no-properties">
                <i class="fas fa-key"></i>
                <h4 class="fw-bold">No Properties Found</h4>
                <p class="text-muted">Try adjusting your search filters.</p>
                <a href="rent.php" class="btn-primary-custom mt-2">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($properties as $property): ?>
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
                                <span class="badge-top">FOR RENT</span>
                                <div class="price-tag">
                                    <div class="price">$<?= number_format($property->price, 0) ?> <span style="font-size:0.7rem;font-weight:400;">/mo</span></div>
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
                                <div class="agent">
                                    <div>
                                        <div class="name"><?= $property->agent_name ?? 'Agent' ?></div>
                                        <div class="role">Property Agent</div>
                                    </div>
                                    <a href="property-detail.php?id=<?= $property->id ?>" 
                                       class="btn btn-sm btn-primary-custom ms-auto"
                                       onclick="event.stopPropagation();">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

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