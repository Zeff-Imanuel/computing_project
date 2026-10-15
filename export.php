<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Libs\Database\MessagesTable;
use Libs\Database\PropertiesTable;
use Libs\Database\AppointmentsTable;
use Libs\Database\PaymentsTable;

// ===== AUTH: admin only =====
$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$type = $_GET['type'] ?? '';

if (!$type) {
    HTTP::redirect("/admin.php", "error=invalid_export");
}

$users_table        = new UsersTable(new MySQL);
$messages_table     = new MessagesTable(new MySQL);
$properties_table   = new PropertiesTable(new MySQL);
$appointments_table = new AppointmentsTable(new MySQL);
$payments_table     = new PaymentsTable(new MySQL);

$db = (new MySQL)->connect();

// ===== Helper: send CSV headers =====
function send_csv_headers($filename)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// ===== Helper: open output + BOM =====
function open_csv()
{
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    return $out;
}

try {
    switch ($type) {

        // ==========================================
        // USERS
        // ==========================================
        case 'users':
            send_csv_headers('users_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Created At']);

            $users = $db->query("
                SELECT u.id, u.name, u.email, u.phone,
                       COALESCE(r.name, 'User') AS role_name,
                       u.created_at
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
            ")->fetchAll();

            foreach ($users as $u) {
                fputcsv($out, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->phone ?? '',
                    trim($u->role_name),
                    $u->created_at
                ]);
            }
            fclose($out);
            exit;

        // ==========================================
        // PROPERTIES
        // ==========================================
        case 'properties':
            send_csv_headers('properties_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, [
                'ID', 'Title', 'Type', 'Listing', 'Price', 'Bedrooms',
                'Bathrooms', 'Area (sqft)', 'Address', 'City', 'State',
                'ZIP', 'Status', 'Featured', 'Agent', 'Created At'
            ]);

            $rows = $db->query("
                SELECT p.*, u.name AS agent_name
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
            ")->fetchAll();

            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->title,
                    $p->property_type,
                    $p->listing_type,
                    $p->price,
                    $p->bedrooms,
                    $p->bathrooms,
                    $p->area_sqft,
                    $p->address,
                    $p->city,
                    $p->state,
                    $p->zip,
                    $p->status,
                    $p->featured ? 'Yes' : 'No',
                    $p->agent_name ?? 'N/A',
                    $p->created_at
                ]);
            }
            fclose($out);
            exit;

        // ==========================================
        // MESSAGES
        // ==========================================
        case 'messages':
            send_csv_headers('messages_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['ID', 'Name', 'Email', 'Subject', 'Message', 'Read', 'Created At']);

            $rows = $db->query("
                SELECT * FROM messages
                ORDER BY created_at DESC
            ")->fetchAll();

            foreach ($rows as $m) {
                fputcsv($out, [
                    $m->id,
                    $m->name,
                    $m->email,
                    $m->subject,
                    $m->message,
                    ($m->is_read ?? 0) ? 'Yes' : 'No',
                    $m->created_at
                ]);
            }
            fclose($out);
            exit;

        // ==========================================
        // APPOINTMENTS
        // ==========================================
        case 'appointments':
            send_csv_headers('appointments_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, [
                'ID', 'Property', 'User', 'Agent', 'Date', 'Status', 'Message', 'Created At'
            ]);

            $rows = $db->query("
                SELECT a.*,
                       p.title AS property_title,
                       u.name  AS user_name,
                       ag.name AS agent_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                LEFT JOIN users ag      ON a.agent_id    = ag.id
                ORDER BY a.created_at DESC
            ")->fetchAll();

            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->id,
                    $a->property_title ?? 'N/A',
                    $a->user_name      ?? 'N/A',
                    $a->agent_name     ?? 'N/A',
                    $a->appointment_date,
                    $a->status,
                    $a->message ?? '',
                    $a->created_at
                ]);
            }
            fclose($out);
            exit;

        // ==========================================
        // PAYMENTS
        // ==========================================
        case 'payments':
            send_csv_headers('payments_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, [
                'ID', 'Property', 'User', 'Amount', 'Method', 'Date', 'Status', 'Notes'
            ]);

            $rows = $db->query("
                SELECT pay.*,
                       p.title AS property_title,
                       u.name  AS user_name
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                LEFT JOIN users u      ON pay.user_id     = u.id
                ORDER BY pay.payment_date DESC
            ")->fetchAll();

            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->property_title ?? 'N/A',
                    $p->user_name      ?? 'N/A',
                    $p->amount,
                    $p->payment_method,
                    $p->payment_date,
                    $p->status,
                    $p->notes ?? ''
                ]);
            }
            fclose($out);
            exit;

        // ==========================================
        // PROPERTY STATUS BREAKDOWN
        // ==========================================
        case 'property_status':
            send_csv_headers('property_status_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['Status', 'Count', 'Percentage']);

            $stats = $properties_table->getStats();
            $total = max(1, (int)$stats->total);

            $breakdown = [
                'Available' => (int)$stats->available,
                'Pending'   => (int)$stats->pending,
                'Sold'      => (int)$stats->sold,
                'Rented'    => (int)$stats->rented,
            ];

            foreach ($breakdown as $label => $count) {
                fputcsv($out, [
                    $label,
                    $count,
                    number_format(($count / $total) * 100, 2) . '%'
                ]);
            }
            fputcsv($out, ['TOTAL', (int)$stats->total, '100.00%']);
            fclose($out);
            exit;

        // ==========================================
        // PROPERTY TYPES BREAKDOWN
        // ==========================================
        case 'property_types':
            send_csv_headers('property_types_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['Property Type', 'Count']);

            $types = $properties_table->getTypeBreakdown();
            foreach ($types as $t) {
                fputcsv($out, [ucfirst($t->property_type), (int)$t->count]);
            }
            fclose($out);
            exit;

        // ==========================================
        // MONTHLY SALES / RENTALS
        // ==========================================
        case 'monthly_sales':
            send_csv_headers('monthly_sales_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['Month', 'Sold', 'Rented', 'Total']);

            $data = $properties_table->getMonthlySoldRented(12);
            $map = [];
            foreach ($data as $row) { $map[$row->month] = $row; }

            $total_sold = 0; $total_rented = 0;

            for ($i = 11; $i >= 0; $i--) {
                $key    = date('Y-m', strtotime("-$i months"));
                $label  = date('M Y', strtotime("-$i months"));
                $sold   = isset($map[$key]) ? (int)$map[$key]->sold_count   : 0;
                $rented = isset($map[$key]) ? (int)$map[$key]->rented_count : 0;

                $total_sold   += $sold;
                $total_rented += $rented;

                fputcsv($out, [$label, $sold, $rented, $sold + $rented]);
            }

            fputcsv($out, ['TOTAL', $total_sold, $total_rented, $total_sold + $total_rented]);
            fclose($out);
            exit;

        // ==========================================
        // MONTHLY REVENUE
        // ==========================================
        case 'revenue':
            send_csv_headers('revenue_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['Month', 'Sales Revenue', 'Rental Income', 'Total Revenue']);

            $data = $properties_table->getMonthlyRevenue(12);
            $map = [];
            foreach ($data as $row) { $map[$row->month] = $row; }

            $tot_s = 0; $tot_r = 0;

            for ($i = 11; $i >= 0; $i--) {
                $key   = date('Y-m', strtotime("-$i months"));
                $label = date('M Y', strtotime("-$i months"));

                $s = isset($map[$key]) ? (float)$map[$key]->sales_revenue  : 0;
                $r = isset($map[$key]) ? (float)$map[$key]->rental_revenue : 0;

                $tot_s += $s;
                $tot_r += $r;

                fputcsv($out, [$label, $s, $r, $s + $r]);
            }

            fputcsv($out, ['TOTAL', $tot_s, $tot_r, $tot_s + $tot_r]);
            fclose($out);
            exit;

        // ==========================================
        // MONTHLY PAYMENTS
        // ==========================================
        case 'monthly_payments':
            send_csv_headers('monthly_payments_' . date('Y-m-d') . '.csv');
            $out = open_csv();

            fputcsv($out, ['Month', 'Completed Amount', 'Pending Amount', 'Transaction Count']);

            $data = $payments_table->getMonthlyPayments(12);
            $map = [];
            foreach ($data as $row) { $map[$row->month] = $row; }

            $total_completed = 0;
            $total_pending   = 0;
            $total_count     = 0;

            for ($i = 11; $i >= 0; $i--) {
                $key   = date('Y-m', strtotime("-$i months"));
                $label = date('M Y', strtotime("-$i months"));

                $completed = isset($map[$key]) ? (float)$map[$key]->completed_amount : 0;
                $pending   = isset($map[$key]) ? (float)$map[$key]->pending_amount   : 0;
                $count     = isset($map[$key]) ? (int)$map[$key]->count              : 0;

                $total_completed += $completed;
                $total_pending   += $pending;
                $total_count     += $count;

                fputcsv($out, [$label, $completed, $pending, $count]);
            }

            fputcsv($out, ['TOTAL', $total_completed, $total_pending, $total_count]);
            fclose($out);
            exit;

        // ==========================================
        // UNKNOWN
        // ==========================================
        default:
            HTTP::redirect("/admin.php", "error=invalid_export");
    }

} catch (PDOException $e) {
    error_log("Export error: " . $e->getMessage());
    HTTP::redirect("/admin.php", "error=export_failed");
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    HTTP::redirect("/admin.php", "error=export_failed");
}