<?php
session_start();

// Destroy all session data
session_destroy();

// Clear all session variables
$_SESSION = array();

// Redirect to the landing page
header("Location: index.php");
exit();
?>