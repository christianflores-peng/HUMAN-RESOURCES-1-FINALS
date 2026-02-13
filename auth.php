<?php
// Redirect stub - auth.php moved to auth/auth.php
header('Location: auth/auth.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
