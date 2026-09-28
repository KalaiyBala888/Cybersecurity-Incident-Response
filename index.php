<?php
// CyberIR - Index Redirect
// Automatically redirect to login page

if (file_exists(__DIR__ . '/login.php')) {
    header("Location: /cyber_ir/login.php");
    exit();
} else {
    die("CyberIR installation incomplete. Missing login.php file.");
}
?>
