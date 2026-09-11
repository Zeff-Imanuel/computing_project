<?php
session_start();
include("vendor/autoload.php");

use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$properties_table = new PropertiesTable(new MySQL);

// Get filter parameters
$keyword = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$city = $_GET['city'] ?? '';
$listing_type = $_GET['listing_type'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Get properties with filters
$properties = $properties_table->search($keyword, $type, $city, $listing_type, $bedrooms, $price_min, $price_max);

// Sort results
if ($sort === 'price_low') {
    usort($properties, fn($a, $b) => $a->price - $b->price);
} elseif ($sort === 'price_high') {
    usort($properties, fn($a, $b) => $b->price - $a->price);
} elseif ($sort === 'oldest') {
    usort($properties, fn($a, $b) => strtotime($a->created_at) - strtotime($b->created_at));
}

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

// Build active filters array
$active_filters = [];
if ($keyword) $active_filters[] = "Search: <strong>$keyword</strong>";
if ($type) $active_filters[] = "Type: <strong>" . ucfirst($type) . "</strong>";
if ($city) $active_filters[] = "City: <strong>$city</strong>";
if ($listing_type) $active_filters[] = "For <strong>" . ucfirst($listing_type) . "</strong>";
if ($bedrooms) $active_filters[] = "Bedrooms: <strong>$bedrooms+</strong>";
if ($price_min) $active_filters[] = "Min: <strong>$" . number_format($price_min) . "</strong>";
if ($price_max) $active_filters[] = "Max: <strong>$" . number_format($price_max) . "</strong>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Myanmar Real Estate</title>
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
            padding: 40px 0 30px;
            text-align: center;
            color: white;
        }
        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
        }
        .page-header h1 span { color: var(--accent); }
        .page-header p {
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
        }

        /* ===== SEARCH FORM ===== */
        .search-form-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px 30px;
            box-shadow: var(--shadow-lg);
            margin-top: -40px;
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
            padding: 10px 30px;
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

        /* ===== FILTERS BAR ===== */
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
        .filters-bar .results-count span {
            color: var(--primary);
        }
        .sort-dropdown {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* ===== ACTIVE FILTERS ===== */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        .filter-tag {
            background: #eff6ff;
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tag a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
        .clear-all {
            background: #fee2e2;
            color: #991b1b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
        }
        .clear-all:hover {
            background: #fecaca;
            color: #991b1b;
        }

        /* ===== PROPERTY CARDS ===== */
        .property-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            height: 100%;
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
            font-size: 1.3rem;
            font-weight: 700;
        }
        .property-card .body {
            padding: 16px 18px;
        }
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

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .no-properties {
            text-align: center;
            padding: 80px 0;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .no-properties i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
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
            .page-header h1 { font-size: 1.5rem; }
            .search-form-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="container">
            <h1>Find Your <span>Perfect Property</span></h1>
            <p>Search from our wide range of properties</p>
        </div>
    </section>

    <!-- ===== SEARCH FORM ===== -->
    <div class="container">
        <div class="search-form-container">
            <form action="search.php" method="get" id="searchForm">
                <!-- Row 1 -->
                <div class="row g-2 mb-2">
                    <div class="col-lg-6">
                        <input type="text" class="form-control" placeholder="Search by title, description, or city..." 
                               name="search" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div class="col-lg-2">
                        <select class="form-select" name="listing_type">
                            <option value="">For Sale/Rent</option>
                            <option value="sale" <?= $listing_type == 'sale' ? 'selected' : '' ?>>For Sale</option>
                            <option value="rent" <?= $listing_type == 'rent' ? 'selected' : '' ?>>For Rent</option>
                        </select>
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
                            <option value="Mawlamyine" <?= $city == 'Mawlamyine' ? 'selected' : '' ?>>Mawlamyine</option>
                            <option value="Taunggyi" <?= $city == 'Taunggyi' ? 'selected' : '' ?>>Taunggyi</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="row g-2">
                    <div class="col-lg-2">
                        <select class="form-select" name="bedrooms">
                            <option value="">Bedrooms</option>
                            <option value="1" <?= $bedrooms == '1' ? 'selected' : '' ?>>1+ Bedrooms</option>
                            <option value="2" <?= $bedrooms == '2' ? 'selected' : '' ?>>2+ Bedrooms</option>
                            <option value="3" <?= $bedrooms == '3' ? 'selected' : '' ?>>3+ Bedrooms</option>
                            <option value="4" <?= $bedrooms == '4' ? 'selected' : '' ?>>4+ Bedrooms</option>
                            <option value="5" <?= $bedrooms == '5' ? 'selected' : '' ?>>5+ Bedrooms</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select" name="price_min">
                            <option value="">Price (From)</option>
                            <option value="10000" <?= $price_min == '10000' ? 'selected' : '' ?>>$10,000</option>
                            <option value="50000" <?= $price_min == '50000' ? 'selected' : '' ?>>$50,000</option>
                            <option value="100000" <?= $price_min == '100000' ? 'selected' : '' ?>>$100,000</option>
                            <option value="200000" <?= $price_min == '200000' ? 'selected' : '' ?>>$200,000</option>
                            <option value="500000" <?= $price_min == '500000' ? 'selected' : '' ?>>$500,000</option>
                            <option value="1000000" <?= $price_min == '1000000' ? 'selected' : '' ?>>$1,000,000</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select" name="price_max">
                            <option value="">Price (To)</option>
                            <option value="50000" <?= $price_max == '50000' ? 'selected' : '' ?>>$50,000</option>
                            <option value="100000" <?= $price_max == '100000' ? 'selected' : '' ?>>$100,000</option>
                            <option value="200000" <?= $price_max == '200000' ? 'selected' : '' ?>>$200,000</option>
                            <option value="500000" <?= $price_max == '500000' ? 'selected' : '' ?>>$500,000</option>
                            <option value="1000000" <?= $price_max == '1000000' ? 'selected' : '' ?>>$1,000,000</option>
                            <option value="5000000" <?= $price_max == '5000000' ? 'selected' : '' ?>>$5,000,000+</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> SEARCH
                        </button>
                    </div>
                    <div class="col-lg-2">
                        <a href="search.php" class="btn btn-outline-secondary w-100" style="height: 44px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== RESULTS ===== -->
    <section class="container py-4">

        <!-- Filters Bar -->
        <div class="filters-bar">
            <div class="results-count">
                <i class="fas fa-home text-primary"></i>
                <span><?= count($properties) ?></span> 
                <?= count($properties) == 1 ? 'property' : 'properties' ?> found
            </div>
            <form action="search.php" method="get" class="d-flex align-items-center gap-2">
                <!-- Keep all current filters -->
                <input type="hidden" name="search" value="<?= htmlspecialchars($keyword) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
                <input type="hidden" name="listing_type" value="<?= htmlspecialchars($listing_type) ?>">
                <input type="hidden" name="bedrooms" value="<?= htmlspecialchars($bedrooms) ?>">
                <input type="hidden" name="price_min" value="<?= htmlspecialchars($price_min) ?>">
                <input type="hidden" name="price_max" value="<?= htmlspecialchars($price_max) ?>">
                
                <label class="text-muted small mb-0">Sort by:</label>
                <select name="sort" class="sort-dropdown" onchange="this.form.submit()">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </form>
        </div>

        <!-- Active Filters -->
        <?php if (!empty($active_filters)): ?>
            <div class="active-filters">
                <?php foreach ($active_filters as $filter): ?>
                    <span class="filter-tag">
                        <i class="fas fa-filter"></i>
                        <?= $filter ?>
                    </span>
                <?php endforeach; ?>
                <a href="search.php" class="clear-all">
                    <i class="fas fa-times"></i> Clear All Filters
                </a>
            </div>
        <?php endif; ?>

        <!-- Results Grid -->
        <?php if (empty($properties)): ?>
            <div class="no-properties">
                <i class="fas fa-search"></i>
                <h4 class="fw-bold">No Properties Found</h4>
                <p class="text-muted">Try adjusting your search filters or browse all properties.</p>
                <a href="search.php" class="btn-primary-custom mt-2">
                    <i class="fas fa-redo"></i> Clear Search
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="property-card">
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
                                    <div class="price">$<?= number_format($property->price, 0) ?><?= $property->listing_type == 'rent' ? ' <span style="font-size:0.7rem;font-weight:400;">/mo</span>' : '' ?></div>
                                </div>
                            </div>
                            <div class="body">
                                <div class="title"><?= htmlspecialchars($property->title) ?></div>
                                <div class="location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($property->city) ?>, <?= htmlspecialchars($property->state) ?>
                                </div>
                                <div class="features">
                                    <span><i class="fas fa-bed"></i> <?= $property->bedrooms ?></span>
                                    <span><i class="fas fa-bath"></i> <?= $property->bathrooms ?></span>
                                    <span><i class="fas fa-vector-square"></i> <?= $property->area_sqft ?> sqft</span>
                                </div>
                                <div class="agent">
                                    <div>
                                        <div class="name"><?= $property->agent_name ?? 'Agent' ?></div>
                                        <div class="role"><?= ucfirst($property->property_type) ?></div>
                                    </div>
                                    <a href="property-detail.php?id=<?= $property->id ?>" class="btn btn-sm btn-primary-custom ms-auto">
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
                    <a href="search.php">Search</a>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white fw-bold">Resources</h5>
                    <a href="#">Property Guide</a>
                    <a href="#">News</a>
                    <a href="#">Careers</a>
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