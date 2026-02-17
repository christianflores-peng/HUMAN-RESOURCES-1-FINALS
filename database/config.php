<?php
/**
 * Database Configuration for HR Management System
 * 
 * This file contains the database connection settings for your HR system.
 * Update the credentials below to match your phpMyAdmin/MySQL setup.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hr1_hr1data');  // Your existing database name
define('DB_USER', 'hr1_hr1slate');         // Production database username
define('DB_PASS', '@g5N#%EFkq+nBrc@');             // Production database password
define('DB_CHARSET', 'utf8mb4');

/**
 * Create database connection using PDO
 * 
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        // If credentials are production ones but running locally (Laragon/XAMPP), try common local defaults.
        $serverName = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
        $httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $isLocal = ($serverName === 'localhost' || $serverName === '127.0.0.1' ||
            str_starts_with($httpHost, 'localhost') || str_starts_with($httpHost, '127.0.0.1') ||
            PHP_SAPI === 'cli');

        $isAccessDenied = (strpos($e->getMessage(), '[1045]') !== false) || ((int)$e->getCode() === 1045);

        if ($isLocal && $isAccessDenied) {
            foreach ([
                ['root', ''],
                ['root', 'root'],
            ] as $creds) {
                try {
                    return new PDO($dsn, $creds[0], $creds[1], $options);
                } catch (PDOException $ignored) {
                    // try next
                }
            }
        }

        error_log("Database connection failed: " . $e->getMessage());
        throw new PDOException("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Test database connection
 * 
 * @return boolean True if connection successful, false otherwise
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Execute a prepared statement with parameters
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for the query
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Log with context for debugging
        error_log("Query execution failed: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));

        // Surface underlying SQL error (useful in development; consider hiding in production)
        throw new PDOException("Query execution failed: " . $e->getMessage(), (int)$e->getCode(), $e);
    }
}

/**
 * Get a single record from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false Single record or false if not found
 */
function fetchSingle($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Get multiple records from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array Array of records
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insert a record and return the inserted ID
 * 
 * @param string $sql SQL insert query
 * @param array $params Parameters for the query
 * @return string Last inserted ID
 */
function insertRecord($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("insertRecord failed - SQLSTATE/Code: " . $e->getCode() . ", Message: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Update or delete records and return affected rows count
 * 
 * @param string $sql SQL update/delete query
 * @param array $params Parameters for the query
 * @return int Number of affected rows
 */
function updateRecord($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

// Set timezone
date_default_timezone_set('Asia/Manila'); // Adjust to your timezone

// Error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
