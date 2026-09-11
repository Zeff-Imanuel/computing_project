<?php

include("../vendor/autoload.php");

use Carbon\Carbon;
use Helpers\Auth;
use Helpers\HTTP;
use Libs\Database\MySQL;
use Libs\Database\UsersTable;

Auth::check();
HTTP::redirect();

$mysql = new MySQL;
$mysql->connect();

$table = new UsersTable;
$table->insert();

echo Carbon::now()->addDay(5);
