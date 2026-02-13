<?php
// Redirect stub - applicant-portal.php moved to auth/applicant-portal.php
header('Location: auth/applicant-portal.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
