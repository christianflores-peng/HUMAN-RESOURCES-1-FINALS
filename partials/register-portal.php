<?php
// Redirect stub - moved to auth/register-portal.php
header('Location: ../auth/register-portal.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
