<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RealEstate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        .register-container h2 {
            font-weight: 700;
            color: #0f172a;
        }
        .register-container .brand i {
            color: #f59e0b;
        }
        .register-container .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
        }
        .register-container .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .register-container .btn-primary {
            background: #2563eb;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }
        .register-container .btn-primary:hover {
            background: #1d4ed8;
        }
        .register-container .link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .register-container .link:hover {
            text-decoration: underline;
        }
        .alert-custom {
            border-radius: 8px;
            padding: 12px 16px;
        }
        .alert-custom.error { background: #fee2e2; color: #991b1b; }
        .alert-custom.success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="register-container">
            <div class="text-center mb-4">
                <a href="index.php" class="brand text-decoration-none text-dark">
                    <h2 class="fw-bold"><i class="fas fa-home"></i> KKG Property</h2>
                </a>
                <h3 class="fw-bold mt-3">Create Account</h3>
                <p class="text-muted">Join us to find your dream home</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert-custom error mb-3">
                    <i class='fas fa-exclamation-circle'></i>
                    <?php 
                        $errors = [
                            'email_exists'    => 'Email already registered!',
                            'missing_fields'  => 'Please fill all fields!',
                            'invalid_name'    => 'Name can only contain letters and spaces.',
                            'invalid_email'   => 'Please enter a valid email address.',
                            'invalid_phone'   => 'Phone number can only contain numbers.',
                            'invalid_address' => 'Address contains invalid characters.',
                            'weak_password'   => 'Password must be at least 6 characters.',
                        ];
                        echo $errors[$_GET['error']] ?? 'Something went wrong.';
                    ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['success'])): ?>
                <div class="alert-custom success mb-3">
                    <i class="fas fa-check-circle"></i> Account created! <a href="index.php" class="fw-bold">Login now</a>
                </div>
            <?php endif; ?>

            <form action="actions/create.php" method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" class="form-control" placeholder="John Doe" name="name"
                           pattern="[A-Za-z\s]+"
                           title="Name can only contain letters and spaces"
                           required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" placeholder="john@example.com" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" class="form-control" placeholder="+1 234 567 890" name="phone"
                           pattern="[0-9+\s-]+"
                           title="Phone number can only contain numbers, spaces, + and -"
                           required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea class="form-control" placeholder="123 Main St, City" name="address" rows="2"
                              pattern="[A-Za-z0-9\s,.\-/]+"
                              title="Address can only contain letters, numbers, and , . - /"
                              required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" placeholder="••••••••" name="password"
                           minlength="6"
                           required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Register as</label>
                    <select name="role_id" class="form-control" required>
                        <option value="1"> Regular User </option>
                        <option value="2"> Agent </option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus"></i> Create Account</button>
            </form>
            <div class="text-center mt-3">
                <span class="text-muted">Already have an account?</span>
                <a href="index.php" class="link">Login</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>