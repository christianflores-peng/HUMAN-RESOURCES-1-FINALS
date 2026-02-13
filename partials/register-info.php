<?php
// Redirect stub - moved to auth/register-info.php
header('Location: ../auth/register-info.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
