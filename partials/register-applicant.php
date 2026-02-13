<?php
// Redirect stub - moved to auth/register-applicant.php
header('Location: ../auth/register-applicant.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
