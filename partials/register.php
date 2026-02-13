<?php
// Redirect stub - moved to auth/register.php
header('Location: ../auth/register.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
