<?php
// Redirect stub - moved to auth/register-employee.php
header('Location: ../auth/register-employee.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
