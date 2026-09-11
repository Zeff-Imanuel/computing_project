<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RealEstate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        .login-container h2 {
            font-weight: 700;
            color: #0f172a;
        }
        .login-container .brand i {
            color: #f59e0b;
        }
        .login-container .subtitle {
            color: #64748b;
        }
        .login-container .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
        }
        .login-container .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .login-container .btn-primary {
            background: #2563eb;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }
        .login-container .btn-primary:hover {
            background: #1d4ed8;
        }
        .login-container .link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .login-container .link:hover {
            text-decoration: underline;
        }
        .alert-custom {
            border-radius: 8px;
            padding: 12px 16px;
        }
        .alert-custom.error { background: #fee2e2; color: #991b1b; }
        .alert-custom.success { background: #dcfce7; color: #166534; }
        .alert-custom.warning { background: #fef3c7; color: #92400e; }
        .alert-custom.danger { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="login-container">
            <div class="text-center mb-4">
                <a href="index.php" class="brand text-decoration-none text-dark">
                    <h2 class="fw-bold"><i class="fas fa-home"></i> KKG Property</h2>
                </a>
                <h3 class="fw-bold mt-3">Welcome Back</h3>
                <p class="subtitle">Login to your account</p>
            </div>

            <!-- ⭐ UNAUTHORIZED ERROR MESSAGE ⭐ -->
            <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="alert-custom danger mb-3">
                    <i class="fas fa-exclamation-circle"></i> You don't have permission to access this page!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['incorrect'])): ?>
                <div class="alert-custom error mb-3">
                    <i class="fas fa-exclamation-circle"></i> Incorrect email or password
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['required'])): ?>
                <div class="alert-custom warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i> Please login to continue
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['account'])): ?>
                <div class="alert-custom success mb-3">
                    <i class="fas fa-check-circle"></i> Account created successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['suspended'])): ?>
                <div class="alert-custom error mb-3">
                    <i class="fas fa-ban"></i> Account suspended. Contact admin.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success']) && $_GET['success'] == 'login'): ?>
                <div class="alert-custom success mb-3">
                    <i class="fas fa-check-circle"></i> Welcome back! 👋
                </div>
            <?php endif; ?>

            <form action="actions/login.php" method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" placeholder="john@example.com" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" placeholder="••••••••" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="text-center mt-3">
                <span class="text-muted">Don't have an account?</span>
                <a href="register.php" class="link">Register</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>