<?php
session_start();
include("vendor/autoload.php");

// Define session variables
$is_logged_in = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

$user_name = '';
$user_email = '';
if ($is_logged_in) {
    $user_name = $user->name ?? '';
    $user_email = $user->email ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Myanmar Real Estate</title>
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

        .contact-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 60px 0 40px;
            text-align: center;
            color: white;
        }
        .contact-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
        }
        .contact-header h1 span { color: var(--accent); }
        .contact-header p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
        }

        .contact-section {
            padding: 50px 0;
        }
        .contact-info-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            height: 100%;
        }
        .contact-info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .contact-info-card .icon {
            width: 60px;
            height: 60px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: var(--primary);
        }
        .contact-info-card .label {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .contact-info-card .value {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .contact-form-container {
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        .contact-form-container h3 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .contact-form-container .subtitle {
            color: var(--gray);
            margin-bottom: 25px;
        }
        .contact-form-container .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .contact-form-container .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }
        .contact-form-container .form-control::placeholder {
            color: #94a3b8;
        }
        .contact-form-container .btn-submit {
            background: var(--primary);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .contact-form-container .btn-submit:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: 100%;
        }
        .toast-message {
            padding: 16px 24px;
            border-radius: var(--radius);
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast-message.show { transform: translateX(0); }
        .toast-message.success { background: #22c55e; border-left: 4px solid #16a34a; }
        .toast-message.error { background: #ef4444; border-left: 4px solid #dc2626; }
        .toast-message.info { background: var(--primary); border-left: 4px solid #1d4ed8; }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 9997;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.show { display: flex; }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

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
            .contact-header h1 { font-size: 1.8rem; }
            .contact-form-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <?php include("nav.php"); ?>

    <!-- ===== CONTACT HEADER ===== -->
    <section class="contact-header">
        <div class="container">
            <h1>Contact <span>Us</span></h1>
            <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </section>

    <!-- ===== CONTACT SECTION ===== -->
    <section class="contact-section">
        <div class="container">

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-phone"></i></div>
                        <div class="label">Phone</div>
                        <div class="value">+95 9 123 456 789</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-envelope"></i></div>
                        <div class="label">Email</div>
                        <div class="value">info@myanmarrealestate.com</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="label">Address</div>
                        <div class="value">Yangon, Myanmar</div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-form-container">
                        <h3>Send Us a Message</h3>
                        <p class="subtitle">Fill in the form below and we'll get back to you shortly.</p>

                        <form id="contactForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Your Name</label>
                                    <input type="text" class="form-control" id="contactName" placeholder="John Doe" value="<?= htmlspecialchars($user_name) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Your Email</label>
                                    <input type="email" class="form-control" id="contactEmail" placeholder="john@example.com" value="<?= htmlspecialchars($user_email) ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Subject</label>
                                    <input type="text" class="form-control" id="contactSubject" placeholder="What is this about?" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Message</label>
                                    <textarea class="form-control" id="contactMessage" rows="5" placeholder="Write your message here..." required></textarea>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn-submit" id="sendBtn">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
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
    <script>
        // Wait for DOM to load
        document.addEventListener('DOMContentLoaded', function() {

            // Toast function
            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                if (!container) {
                    console.error('Toast container not found');
                    alert(message);
                    return;
                }
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    info: 'fa-info-circle',
                    warning: 'fa-exclamation-triangle'
                };
                const toast = document.createElement('div');
                toast.className = `toast-message ${type}`;
                toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${message}`;
                container.appendChild(toast);
                setTimeout(() => toast.classList.add('show'), 50);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 500);
                }, 4000);
            }

            function showLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.add('show');
            }

            function hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.remove('show');
            }

            // Get form
            const form = document.getElementById('contactForm');
            if (!form) {
                console.error('Form not found!');
                return;
            }

            // Submit event
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted!');

                const name = document.getElementById('contactName').value.trim();
                const email = document.getElementById('contactEmail').value.trim();
                const subject = document.getElementById('contactSubject').value.trim();
                const message = document.getElementById('contactMessage').value.trim();

                // Validate
                if (!name || !email || !subject || !message) {
                    showToast('Please fill in all fields.', 'error');
                    return;
                }
                if (!email.includes('@')) {
                    showToast('Please enter a valid email address.', 'error');
                    return;
                }

                showLoading();
                const btn = document.getElementById('sendBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                console.log('Sending data:', { name, email, subject, message });

                fetch('actions/send_contact.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, subject, message })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                    if (data.success) {
                        showToast('Message sent successfully! We\'ll get back to you soon.', 'success');
                        form.reset();
                    } else {
                        showToast(data.message || 'Something went wrong. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    hideLoading();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                    showToast('Network error. Please check your connection.', 'error');
                });
            });

            console.log('Contact form ready!');
        });
    </script>
</body>
</html>