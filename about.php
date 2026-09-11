<?php
session_start();
include("vendor/autoload.php");

$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Myanmar Real Estate</title>
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
        body { background: #ffffff; }

        .about-header {
            padding: 60px 0 40px;
            text-align: center;
            background: white;
        }
        .about-header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        .about-header h1 span { color: var(--primary); }
        .about-header .subtitle {
            color: var(--gray);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .big-image-section {
            padding: 20px 0 40px;
        }
        .big-image-section .image-wrapper {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            background: var(--light-gray);
        }
        .big-image-section .image-wrapper img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }

        .about-content {
            padding: 40px 0 60px;
        }
        .about-content .main-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--gray);
        }
        .about-content .main-text strong {
            color: var(--secondary);
        }
        .about-content .highlight-box {
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-top: 30px;
        }
        .about-content .highlight-box .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        .about-content .highlight-box .label {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .stats-section {
            background: var(--secondary);
            padding: 60px 0;
            color: white;
        }
        .stats-section .number {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--accent);
        }
        .stats-section .label {
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
        }

        .values-section {
            padding: 60px 0;
        }
        .value-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            height: 100%;
            transition: all 0.3s;
        }
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .value-card .icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        .value-card h5 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        .value-card p {
            color: var(--gray);
            font-size: 0.95rem;
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
            .about-header h1 { font-size: 2rem; }
            .about-header { padding: 40px 0 30px; }
            .about-header .subtitle { font-size: 1rem; }
            .stats-section .number { font-size: 2rem; }
            .about-content .main-text { font-size: 1rem; }
            .big-image-section .image-wrapper img { height: 250px; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- ===== ABOUT HEADER ===== -->
    <section class="about-header">
        <div class="container">
            <h1>Myanmar Best Real Estate – <span>Elevating Real Estate Experiences</span></h1>
            <p class="subtitle">
                
            </p>
        </div>
    </section>

    <!-- ===== BIG IMAGE ===== -->
    <div class="container big-image-section">
        <div class="image-wrapper">
            <img src="/project/actions/photos/aboutus.jpg" alt="About Myanmar Real Estate">
        </div>
    </div>

    <!-- ===== ABOUT CONTENT ===== -->
    <section class="about-content">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <h2 class="fw-bold mb-3">Who We Are</h2>
                    <div class="main-text">
                        <p>
                            <strong>Myanmar Real Estate</strong> is a trusted name in the Myanmar property market. 
                            With over <strong>10 years of experience</strong>, we have helped thousands of clients 
                            find their dream homes, investment properties, and commercial spaces across Yangon, 
                            Mandalay, and beyond.
                        </p>
                        <p>
                            Our team of <strong>dedicated professionals</strong> understands the local market inside and out. 
                            Whether you're buying, selling, or renting, we provide <strong>personalized service</strong> 
                            tailored to your unique needs.
                        </p>
                        <p>
                            We pride ourselves on our <strong>transparency, integrity, and commitment</strong> to 
                            excellence. From the first viewing to the final handshake, we're with you every step of the way.
                        </p>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="highlight-box">
                        <div class="row text-center g-4">
                            <div class="col-6">
                                <div class="number">10+</div>
                                <div class="label">Years of Experience</div>
                            </div>
                            <div class="col-6">
                                <div class="number">1000+</div>
                                <div class="label">Happy Clients</div>
                            </div>
                            <div class="col-6">
                                <div class="number">500+</div>
                                <div class="label">Properties Sold</div>
                            </div>
                            <div class="col-6">
                                <div class="number">98%</div>
                                <div class="label">Satisfaction Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== STATS SECTION ===== -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-3">
                    <div class="number">10+</div>
                    <div class="label">Years of Excellence</div>
                </div>
                <div class="col-md-3">
                    <div class="number">1,247</div>
                    <div class="label">Properties Available</div>
                </div>
                <div class="col-md-3">
                    <div class="number">856</div>
                    <div class="label">Happy Buyers</div>
                </div>
                <div class="col-md-3">
                    <div class="number">142</div>
                    <div class="label">Trusted Agents</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== VALUES SECTION ===== -->
    <section class="values-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Core <span style="color: var(--primary);">Values</span></h2>
                <p class="text-muted">The principles that guide everything we do</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="fas fa-handshake"></i></div>
                        <h5>Integrity</h5>
                        <p>We believe in honest, transparent dealings with every client. Trust is the foundation of our business.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="fas fa-star"></i></div>
                        <h5>Excellence</h5>
                        <p>We strive for excellence in everything we do, from client service to market knowledge and beyond.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h5>Community</h5>
                        <p>We're committed to building stronger communities by connecting people with their ideal properties.</p>
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
                    <a href="#">Agents</a>
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