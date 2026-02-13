<?php
// Redirect stub - modals/ renamed to views/
header('Location: ../../views/manager/index.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
