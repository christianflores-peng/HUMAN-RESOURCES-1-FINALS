<?php
// Redirect stub - login.php moved to auth/login-redirect.php
header('Location: auth/login-redirect.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
