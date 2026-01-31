<?php
/**
 * Secure Session Management Helper
 * Implements security best practices for session handling
 */

/**
 * Start a secure session with proper security settings
 */
function startSecureSession() {
    // Prevent session hijacking
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    
    // Enable secure cookies if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Set session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 1800);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check for session timeout
    checkSessionTimeout();
    
    // Regenerate session ID periodically for security
    regenerateSessionPeriodically();
}

/**
 * Check if session has timed out
 */
function checkSessionTimeout() {
    $timeout_duration = 1800; // 30 minutes
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        if ($elapsed_time > $timeout_duration) {
            destroySecureSession();
            header('Location: /HR1/partials/login.php?timeout=1');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Regenerate session ID periodically (every 5 minutes)
 */
function regenerateSessionPeriodically() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    $regeneration_interval = 300; // 5 minutes
    
    if (time() - $_SESSION['last_regeneration'] > $regeneration_interval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Regenerate session ID after login (prevent session fixation)
 */
function regenerateSessionAfterLogin() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Securely destroy session
 */
function destroySecureSession() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token HTML input field
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication (redirect if not logged in)
 */
function requireAuth($redirect_url = '/HR1/partials/login.php') {
    if (!isAuthenticated()) {
        header('Location: ' . $redirect_url);
        exit();
    }
}
?>
