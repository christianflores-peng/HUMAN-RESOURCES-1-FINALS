<?php
// Redirect stub - moved to auth/login.php
header('Location: ../auth/login.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
