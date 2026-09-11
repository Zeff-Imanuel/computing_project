<?php
session_start();  // ✅ MUST BE FIRST

// Destroy session
session_destroy();

header("location: ../index.php");
exit();
?>