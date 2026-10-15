<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;

$user = Auth::check();

$property_id = $_GET['property_id'] ?? 0;

if (!$property_id) {
    HTTP::redirect("/home.php", "error=invalid_property");
}

$properties_table = new PropertiesTable(new MySQL);
$property = $properties_table->getById($property_id);

if (!$property) {
    HTTP::redirect("/home.php", "error=property_not_found");
}

$is_logged_in = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Myanmar Real Estate</title>
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
        .page-header h1 { font-size: 2.5rem; font-weight: 800; }
        .page-header h1 span { color: var(--accent); }
        .page-header p { color: rgba(255,255,255,0.7); font-size: 1.1rem; }

        .booking-container {
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: var(--shadow-lg);
            margin: -30px auto 40px;
            max-width: 900px;
            position: relative;
            z-index: 10;
        }

        .property-preview {
            background: var(--light-gray);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }
        .property-preview img {
            width: 120px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .property-preview .no-image {
            width: 120px;
            height: 100px;
            background: #e2e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 2rem;
        }
        .property-preview .info h5 { font-weight: 700; margin-bottom: 5px; }
        .property-preview .info .location { color: var(--gray); font-size: 0.9rem; }
        .property-preview .info .price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .form-label { font-weight: 600; color: var(--secondary); }
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }
        .btn-cancel {
            background: transparent;
            color: var(--gray);
            border: 2px solid var(--border-color);
            padding: 14px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            background: var(--light-gray);
            color: var(--secondary);
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .info-box i { color: var(--primary); margin-right: 8px; }

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
        .toast-message.success { background: #22c55e; }
        .toast-message.error { background: #ef4444; }
        .toast-message.info { background: var(--primary); }

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

        @media (max-width: 768px) {
            .booking-container { padding: 20px; }
            .property-preview { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <?php include("nav.php"); ?>

    <div id="toastContainer" class="toast-container"></div>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <section class="page-header">
        <div class="container">
            <h1>Book <span>Appointment</span></h1>
            <p>Schedule a property viewing</p>
        </div>
    </section>

    <div class="container">
        <div class="booking-container">

            <!-- Property Preview -->
            <div class="property-preview">
                <?php if ($property->photo): ?>
                    <img src="images/<?= $property->photo ?>" alt="<?= $property->title ?>">
                <?php else: ?>
                    <div class="no-image"><i class="fas fa-home"></i></div>
                <?php endif; ?>
                <div class="info">
                    <h5><?= htmlspecialchars($property->title) ?></h5>
                    <div class="location">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?= htmlspecialchars($property->address) ?>, 
                        <?= htmlspecialchars($property->city) ?>
                    </div>
                    <div class="price">
                        $<?= number_format($property->price, 0) ?>
                        <?= $property->listing_type == 'rent' ? '/month' : '' ?>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> The agent will contact you to confirm the appointment.
            </div>

            <!-- Booking Form -->
            <form id="appointmentForm">
                <input type="hidden" name="property_id" value="<?= $property->id ?>">
                <input type="hidden" name="agent_id" value="<?= $property->user_id ?>">

                <div class="row g-3">
                    <!-- Date -->
                    <div class="col-md-6">
                        <label class="form-label">Preferred Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="appointmentDate" 
                               name="appointment_date" required
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Time -->
                    <div class="col-md-6">
                        <label class="form-label">Preferred Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="appointmentTime" 
                               name="appointment_time" required>
                    </div>

                    <!-- Your Info (Read-only) -->
                    <div class="col-md-6">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($user->name) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Your Email</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($user->email) ?>" readonly>
                    </div>

                    <!-- Message -->
                    <div class="col-12">
                        <label class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="appointmentMessage" rows="4" 
                                  placeholder="Any specific request or question about the property..."></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-6 mt-3">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-calendar-check"></i> Book Appointment
                        </button>
                    </div>
                    <div class="col-md-6 mt-3">
                        <a href="property-detail.php?id=<?= $property->id ?>" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            const toast = document.createElement('div');
            toast.className = `toast-message ${type}`;
            toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 50);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 3500);
        }

        function showLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
        function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const date = document.getElementById('appointmentDate').value;
            const time = document.getElementById('appointmentTime').value;
            const message = document.getElementById('appointmentMessage').value.trim();
            const propertyId = document.querySelector('input[name="property_id"]').value;
            const agentId = document.querySelector('input[name="agent_id"]').value;

            if (!date || !time) {
                showToast('Please select date and time', 'error');
                return;
            }

            // Combine date and time
            const appointmentDate = date + ' ' + time + ':00';

            showLoading();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';

            // ✅ FIXED: relative path (no leading slash)
            fetch('actions/appointments-create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    property_id: propertyId,
                    agent_id: agentId,
                    appointment_date: appointmentDate,
                    message: message
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check"></i> Book Appointment';

                if (data.success) {
                    showToast('Appointment booked successfully! 🎉', 'success');
                    setTimeout(() => {
                        window.location.href = 'user-appointments.php';
                    }, 1500);
                } else {
                    showToast(data.message || 'Something went wrong', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check"></i> Book Appointment';
                showToast('Network error. Please try again.', 'error');
                console.error('Booking error:', error);
            });
        });
    </script>
</body>
</html>