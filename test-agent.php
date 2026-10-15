<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Start<br>";

session_start();
echo "Step 2: Session started<br>";

include("vendor/autoload.php");
echo "Step 3: Autoload OK<br>";

use Helpers\Auth;
use Libs\Database\MySQL;
use Libs\Database\PropertiesTable;
use Libs\Database\AppointmentsTable;

echo "Step 4: Use statements OK<br>";

$auth = Auth::check();
echo "Step 5: Auth OK — role_id = " . $auth->role_id . ", id = " . $auth->id . "<br>";

$pt = new PropertiesTable(new MySQL);
echo "Step 6: PropertiesTable OK<br>";

$at = new AppointmentsTable(new MySQL);
echo "Step 7: AppointmentsTable OK<br>";

$props = $pt->all();
echo "Step 8: PropertiesTable::all() OK — " . count($props) . " properties<br>";

$rows1 = $pt->getMonthlySoldRentedByUser($auth->id, 12);
echo "Step 9: getMonthlySoldRentedByUser OK — " . count($rows1) . " rows<br>";

$rows2 = $pt->getRevenueStatsByUser($auth->id);
echo "Step 10: getRevenueStatsByUser OK<br>";

$rows3 = $pt->getMonthlyRevenueByUser($auth->id, 12);
echo "Step 11: getMonthlyRevenueByUser OK — " . count($rows3) . " rows<br>";

$appts = $at->getByAgent($auth->id);
echo "Step 12: getByAgent OK — " . count($appts) . " appointments<br>";

echo "<br><strong>✅ ALL STEPS PASSED</strong>";