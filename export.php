<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;
use Libs\Database\PropertiesTable;
use Libs\Database\MessagesTable;
use Libs\Database\AppointmentsTable;

$auth = Auth::check();
if ($auth->role_id != 3) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$type = $_GET['type'] ?? '';
$filename = "export_" . $type . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'users':
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Address', 'Role', 'Status', 'Created At']);
        $users_table = new UsersTable(new MySQL);
        $users = $users_table->all();
        foreach ($users as $user) {
            fputcsv($output, [
                $user->id, $user->name, $user->email, $user->phone,
                $user->address, $user->role ?? 'User',
                $user->suspended ? 'Suspended' : 'Active', $user->created_at
            ]);
        }
        break;

    case 'properties':
        fputcsv($output, ['ID', 'Title', 'Type', 'Listing', 'Price', 'Bedrooms', 'Bathrooms', 'Area', 'Address', 'City', 'State', 'Status', 'Agent', 'Created At']);
        $properties_table = new PropertiesTable(new MySQL);
        $properties = $properties_table->all();
        foreach ($properties as $prop) {
            fputcsv($output, [
                $prop->id, $prop->title, $prop->property_type, $prop->listing_type,
                $prop->price, $prop->bedrooms, $prop->bathrooms, $prop->area_sqft,
                $prop->address, $prop->city, $prop->state, $prop->status,
                $prop->agent_name ?? 'N/A', $prop->created_at
            ]);
        }
        break;

    case 'messages':
        fputcsv($output, ['ID', 'Name', 'Email', 'Subject', 'Message', 'Status', 'Created At']);
        $messages_table = new MessagesTable(new MySQL);
        $messages = $messages_table->all();
        foreach ($messages as $msg) {
            fputcsv($output, [
                $msg->id, $msg->name, $msg->email, $msg->subject,
                $msg->message, $msg->is_read ? 'Read' : 'Unread', $msg->created_at
            ]);
        }
        break;

    case 'appointments':
        fputcsv($output, ['ID', 'Property', 'User', 'User Email', 'User Phone', 'Agent', 'Date', 'Message', 'Status', 'Created At']);
        $appointments_table = new AppointmentsTable(new MySQL);
        $appointments = $appointments_table->all();
        foreach ($appointments as $app) {
            fputcsv($output, [
                $app->id, $app->property_title ?? 'N/A', $app->user_name ?? 'N/A',
                $app->user_email ?? 'N/A', $app->user_phone ?? 'N/A',
                $app->agent_name ?? 'N/A', $app->appointment_date,
                $app->message, $app->status, $app->created_at
            ]);
        }
        break;

    // ✅ NEW: Export Property Status Breakdown
    case 'property_status':
        fputcsv($output, ['Status', 'Count', 'Percentage']);
        
        $properties_table = new PropertiesTable(new MySQL);
        $stats = $properties_table->getStats();
        $total = (int)($stats->total ?? 0);
        
        $statuses = [
            'Available' => (int)($stats->available ?? 0),
            'Pending' => (int)($stats->pending ?? 0),
            'Sold' => (int)($stats->sold ?? 0),
            'Rented' => (int)($stats->rented ?? 0)
        ];
        
        foreach ($statuses as $status => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) . '%' : '0%';
            fputcsv($output, [$status, $count, $percentage]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['TOTAL', $total, '100%']);
        break;

    // ✅ NEW: Export Property Types Breakdown
    case 'property_types':
        fputcsv($output, ['Property Type', 'Count', 'Percentage']);
        
        $properties_table = new PropertiesTable(new MySQL);
        $types = $properties_table->getTypeBreakdown();
        $stats = $properties_table->getStats();
        $total = (int)($stats->total ?? 0);
        
        foreach ($types as $type_item) {
            $percentage = $total > 0 ? round(($type_item->count / $total) * 100, 2) . '%' : '0%';
            fputcsv($output, [
                ucfirst($type_item->property_type),
                $type_item->count,
                $percentage
            ]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['TOTAL', $total, '100%']);
        break;

    default:
        fputcsv($output, ['Error', 'Invalid export type']);
        break;
}

fclose($output);
exit();
?>