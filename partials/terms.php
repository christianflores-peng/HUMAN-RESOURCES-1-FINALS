<?php
// Redirect stub - moved to auth/terms.php
header('Location: ../auth/terms.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
