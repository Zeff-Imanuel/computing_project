<?php
session_start();
include("vendor/autoload.php");

use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\AppointmentsTable;

$auth = Auth::check();

if ($auth->role_id < 2) {
    HTTP::redirect("/index.php", "error=unauthorized");
}

$properties_table   = new PropertiesTable(new MySQL);
$appointments_table = new AppointmentsTable(new MySQL);

$all = $properties_table->all();
$properties = [];
foreach ($all as $p) {
    if ($p->user_id == $auth->id) {
        $properties[] = $p;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Agent Properties</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("nav.php"); ?>
    
    <div class="container mt-4">
        <h1>TEST PAGE</h1>
        <p>Found <?= count($properties) ?> properties</p>
        <ul>
            <?php foreach ($properties as $p): ?>
                <li><?= htmlspecialchars($p->title) ?> — <?= $p->status ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>