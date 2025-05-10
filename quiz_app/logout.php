<?php
require_once __DIR__ . '/includes/config.php';

// Destroy all session data
$_SESSION = array();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>