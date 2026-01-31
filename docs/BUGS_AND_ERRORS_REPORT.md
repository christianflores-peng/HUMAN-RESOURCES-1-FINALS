# HR1 System - Bugs & Errors Report
## Comprehensive Security & Code Quality Analysis

**Generated:** January 30, 2026  
**System:** SLATE Freight Management System - HR1 Module

---

## 🔴 **CRITICAL SECURITY VULNERABILITIES**

### **1. Missing Session Security Configuration**
**Severity:** CRITICAL  
**Location:** All PHP files using `session_start()`

**Issue:**
```php
// Current code - INSECURE
session_start();
```

**Problem:**
- No session regeneration after login (Session Fixation vulnerability)
- No secure session cookie settings
- No HTTP-only flag on session cookies
- No SameSite attribute
- Session hijacking risk

**Fix Required:**
```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // If using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_start();

// Regenerate session ID after login
if (isset($_POST['login'])) {
    session_regenerate_id(true);
}
```

**Files Affected:**
- `partials/login.php` (line 2)
- `partials/register-applicant.php` (line 2)
- `partials/register-applicant-documents.php` (line 2)
- `logout.php` (line 2)
- `auth.php` (line 2)
- All protected pages

---

### **2. Insecure Logout Implementation**
**Severity:** HIGH  
**Location:** `logout.php`

**Issue:**
```php
// Current code - WRONG ORDER
session_start();
session_destroy();
$_SESSION = array(); // ❌ This does nothing after session_destroy()
```

**Problem:**
- Session variables cleared AFTER session destroyed
- Session cookie not deleted
- User can still access pages with old session ID

**Fix Required:**
```php
session_start();

// Clear session variables FIRST
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Then destroy session
session_destroy();

// Regenerate new session ID for security
session_regenerate_id(true);

header("Location: index.php");
exit();
```

---

### **3. Path Traversal Vulnerability in File Uploads**
**Severity:** HIGH  
**Location:** `partials/register-applicant-documents.php` (line 40)

**Issue:**
```php
// Current code - VULNERABLE
$resume_path = uniqid() . '_' . basename($_FILES['resume']['name']);
```

**Problem:**
- `basename()` can be bypassed with null bytes
- No sanitization of filename
- Potential directory traversal attack
- Malicious filenames can execute code

**Fix Required:**
```php
// Secure filename generation
$file_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
$safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['resume']['name'], PATHINFO_FILENAME));
$resume_path = uniqid() . '_' . time() . '_' . substr($safe_filename, 0, 50) . '.' . $file_ext;

// Validate extension again
if (!in_array($file_ext, $allowed_exts)) {
    throw new Exception("Invalid file type");
}

// Use absolute path
$full_path = realpath($upload_dir) . DIRECTORY_SEPARATOR . $resume_path;
```

---

### **4. Missing CSRF Protection**
**Severity:** HIGH  
**Location:** All forms (login, registration, application)

**Issue:**
- No CSRF tokens in forms
- Forms vulnerable to Cross-Site Request Forgery attacks
- Attackers can submit forms on behalf of users

**Fix Required:**
```php
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form HTML
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validate on submission
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

**Files Needing CSRF Protection:**
- `partials/login.php`
- `partials/register-applicant.php`
- `partials/register-applicant-documents.php`
- `apply.php`
- All forms in the system

---

### **5. SQL Injection Risk in Error Messages**
**Severity:** MEDIUM  
**Location:** `database/config.php` (line 70-71)

**Issue:**
```php
error_log("Query execution failed: " . $e->getMessage());
throw new PDOException("Query execution failed.");
```

**Problem:**
- Generic error message hides SQL errors
- Debugging is difficult
- But exposing SQL errors in production is dangerous

**Fix Required:**
```php
// Development mode
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Query execution failed: " . $e->getMessage());
    throw new PDOException("Query failed: " . $e->getMessage());
} else {
    // Production mode - log but don't expose
    error_log("Query execution failed: " . $e->getMessage() . " | Query: " . $sql);
    throw new PDOException("Database operation failed. Please try again.");
}
```

---

## 🟠 **HIGH PRIORITY BUGS**

### **6. Race Condition in File Upload**
**Severity:** HIGH  
**Location:** `apply.php` (line 97-102)

**Issue:**
```php
$filename = 'resume_' . $job_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
$resume_path = $upload_dir . $filename;

if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
    throw new Exception("Failed to upload resume. Please try again.");
}
```

**Problem:**
- File uploaded BEFORE database transaction
- If database insert fails, orphaned file remains
- No cleanup on error
- Disk space waste

**Fix Required:**
```php
// Upload to temporary location first
$temp_path = $upload_dir . 'temp_' . uniqid() . '.' . $file_extension;
move_uploaded_file($_FILES['resume']['tmp_name'], $temp_path);

try {
    // Insert into database
    $application_id = insertRecord($sql, $params);
    
    // Rename to final location only after success
    $final_path = $upload_dir . 'resume_' . $application_id . '.' . $file_extension;
    rename($temp_path, $final_path);
    
} catch (Exception $e) {
    // Clean up temp file on error
    if (file_exists($temp_path)) {
        unlink($temp_path);
    }
    throw $e;
}
```

---

### **7. Hardcoded Role ID**
**Severity:** MEDIUM  
**Location:** `partials/register-applicant-documents.php` (line 49)

**Issue:**
```php
// Hardcoded role_id: 9
"INSERT INTO user_accounts (..., role_id, ...) VALUES (?, ?, ?, ?, ?, 9, 'Active', NOW())"
```

**Problem:**
- Assumes role_id 9 always exists
- Breaks if roles table is modified
- Not maintainable
- Magic number anti-pattern

**Fix Required:**
```php
// Get role ID dynamically
$applicant_role = fetchSingle("SELECT id FROM roles WHERE role_type = 'Applicant' LIMIT 1");

if (!$applicant_role) {
    throw new Exception("Applicant role not found in system");
}

$role_id = $applicant_role['id'];

// Use variable instead of hardcoded value
$user_id = insertRecord(
    "INSERT INTO user_accounts (..., role_id, ...) VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())",
    [$first_name, $last_name, $email, $phone, $password_hash, $role_id]
);
```

---

### **8. Missing Database Transaction**
**Severity:** MEDIUM  
**Location:** `partials/register-applicant-documents.php` (line 47-59)

**Issue:**
```php
// Insert user account
$user_id = insertRecord("INSERT INTO user_accounts...", [...]);

// Insert applicant profile - NO TRANSACTION
$profile_id = insertRecord("INSERT INTO applicant_profiles...", [...]);
```

**Problem:**
- If profile insert fails, user account exists without profile
- Data inconsistency
- Orphaned records
- No atomicity

**Fix Required:**
```php
try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Insert user account
    $user_id = insertRecord("INSERT INTO user_accounts...", [...]);
    
    // Insert applicant profile
    $profile_id = insertRecord("INSERT INTO applicant_profiles...", [$user_id, ...]);
    
    // Commit both or none
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Registration failed: " . $e->getMessage());
}
```

---

### **9. Weak Password Generation**
**Severity:** MEDIUM  
**Location:** `apply.php` (line 139)

**Issue:**
```php
// Weak password generation
$temp_password = bin2hex(random_bytes(4)); // Only 8 characters
```

**Problem:**
- Only 8 characters (too short)
- Only hexadecimal characters (limited charset)
- Predictable pattern
- Vulnerable to brute force

**Fix Required:**
```php
// Generate stronger temporary password
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}

$temp_password = generateSecurePassword(12);
```

---

### **10. No Email Validation on Duplicate Check**
**Severity:** MEDIUM  
**Location:** `apply.php` (line 62-65)

**Issue:**
```php
// Check if already applied
$existing_application = fetchSingle(
    "SELECT id FROM job_applications WHERE job_posting_id = ? AND email = ?",
    [$job_id, $email]
);
```

**Problem:**
- Email already validated with `filter_var()` but not normalized
- `user@example.com` vs `USER@example.com` treated as different
- Case-sensitive comparison
- Allows duplicate applications with different case

**Fix Required:**
```php
// Normalize email to lowercase
$email = strtolower(trim($_POST['email'] ?? ''));

// Check with normalized email
$existing_application = fetchSingle(
    "SELECT id FROM job_applications WHERE job_posting_id = ? AND LOWER(email) = ?",
    [$job_id, $email]
);
```

---

## 🟡 **MEDIUM PRIORITY ISSUES**

### **11. Missing Input Sanitization**
**Severity:** MEDIUM  
**Location:** Multiple files

**Issue:**
- Using `trim()` only, no HTML entity encoding
- XSS vulnerability in output
- User input displayed without sanitization

**Fix Required:**
```php
// Input sanitization function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Use on all user inputs
$first_name = sanitizeInput($_POST['first_name'] ?? '');
```

---

### **12. No Rate Limiting**
**Severity:** MEDIUM  
**Location:** `partials/login.php`, `apply.php`

**Issue:**
- No protection against brute force attacks
- Unlimited login attempts
- Unlimited application submissions
- No IP-based throttling

**Fix Required:**
```php
// Simple rate limiting
function checkRateLimit($action, $identifier, $max_attempts = 5, $time_window = 300) {
    $key = $action . '_' . $identifier;
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    // Reset if time window passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if exceeded
    if ($data['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

// Use in login
if (!checkRateLimit('login', $_SERVER['REMOTE_ADDR'])) {
    die('Too many login attempts. Please try again later.');
}
```

---

### **13. Missing File Type Verification**
**Severity:** MEDIUM  
**Location:** `apply.php`, `register-applicant-documents.php`

**Issue:**
```php
// Only checks extension, not actual file content
$file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
```

**Problem:**
- Extension can be spoofed
- Malicious files can be uploaded as .pdf
- No MIME type verification
- No file content validation

**Fix Required:**
```php
// Verify actual file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['resume']['tmp_name']);
finfo_close($finfo);

$allowed_mimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if (!in_array($mime_type, $allowed_mimes)) {
    throw new Exception("Invalid file type. Only PDF and Word documents are allowed.");
}
```

---

### **14. Insecure Direct Object Reference (IDOR)**
**Severity:** MEDIUM  
**Location:** Potential in dashboard pages

**Issue:**
- User IDs passed in URLs without verification
- No ownership validation
- Users can access other users' data

**Fix Required:**
```php
// Always verify ownership
function verifyOwnership($user_id, $resource_id, $resource_table) {
    $resource = fetchSingle(
        "SELECT * FROM {$resource_table} WHERE id = ? AND user_id = ?",
        [$resource_id, $user_id]
    );
    
    if (!$resource) {
        http_response_code(403);
        die('Access denied');
    }
    
    return $resource;
}

// Use in pages
$application = verifyOwnership($_SESSION['user_id'], $_GET['id'], 'job_applications');
```

---

### **15. No Session Timeout**
**Severity:** MEDIUM  
**Location:** All authenticated pages

**Issue:**
- Sessions never expire
- Users stay logged in indefinitely
- Security risk on shared computers

**Fix Required:**
```php
// Add to auth.php or session start
$timeout_duration = 1800; // 30 minutes

if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    
    if ($elapsed_time > $timeout_duration) {
        session_destroy();
        header('Location: ../partials/login.php?timeout=1');
        exit();
    }
}

// Update last activity time
$_SESSION['login_time'] = time();
```

---

## 🟢 **LOW PRIORITY ISSUES**

### **16. Inconsistent Error Handling**
**Severity:** LOW  
**Location:** Multiple files

**Issue:**
- Some errors use exceptions, some use error messages
- Inconsistent error message format
- No centralized error handling

**Fix Required:**
```php
// Create error handler class
class ErrorHandler {
    public static function handle($e, $user_friendly = true) {
        error_log($e->getMessage());
        
        if ($user_friendly) {
            return "An error occurred. Please try again or contact support.";
        }
        
        return $e->getMessage();
    }
}

// Use consistently
try {
    // code
} catch (Exception $e) {
    $error_message = ErrorHandler::handle($e);
}
```

---

### **17. Missing Database Indexes**
**Severity:** LOW  
**Location:** Database tables

**Issue:**
- No indexes on frequently queried columns
- Slow query performance
- Email lookups not optimized

**Fix Required:**
```sql
-- Add indexes for better performance
CREATE INDEX idx_user_email ON user_accounts(personal_email);
CREATE INDEX idx_job_status ON job_applications(status);
CREATE INDEX idx_job_posting ON job_applications(job_posting_id);
CREATE INDEX idx_applied_date ON job_applications(applied_date);
```

---

### **18. No Input Length Validation**
**Severity:** LOW  
**Location:** All form inputs

**Issue:**
- No maximum length validation
- Can overflow database columns
- Buffer overflow risk

**Fix Required:**
```php
// Add length validation
if (strlen($first_name) > 50) {
    throw new Exception("First name is too long (max 50 characters)");
}

if (strlen($cover_letter) > 5000) {
    throw new Exception("Cover letter is too long (max 5000 characters)");
}
```

---

### **19. Hardcoded Paths**
**Severity:** LOW  
**Location:** Multiple files

**Issue:**
```php
// Hardcoded paths
$upload_dir = '../uploads/resumes/';
header('Location: ../pages/applicant-dashboard.php');
```

**Problem:**
- Not portable
- Breaks if directory structure changes
- Difficult to maintain

**Fix Required:**
```php
// Use constants
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/resumes/');

$upload_dir = UPLOAD_PATH;
```

---

### **20. No Logging System**
**Severity:** LOW  
**Location:** Entire system

**Issue:**
- Only error_log() used
- No audit trail
- No user action logging
- Difficult to debug issues

**Fix Required:**
```php
// Create logging system
class Logger {
    public static function log($level, $message, $context = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        // Log to file
        error_log(json_encode($log_entry), 3, BASE_PATH . '/logs/app.log');
        
        // Log to database for critical events
        if (in_array($level, ['ERROR', 'CRITICAL', 'SECURITY'])) {
            // Insert into audit_logs table
        }
    }
}

// Use throughout application
Logger::log('INFO', 'User logged in', ['user_id' => $user_id]);
Logger::log('SECURITY', 'Failed login attempt', ['username' => $username]);
```

---

## 📊 **SUMMARY**

### **Critical Issues:** 5
1. Missing session security configuration
2. Insecure logout implementation
3. Path traversal vulnerability in file uploads
4. Missing CSRF protection
5. SQL injection risk in error messages

### **High Priority:** 5
6. Race condition in file upload
7. Hardcoded role ID
8. Missing database transaction
9. Weak password generation
10. No email validation on duplicate check

### **Medium Priority:** 5
11. Missing input sanitization
12. No rate limiting
13. Missing file type verification
14. Insecure direct object reference (IDOR)
15. No session timeout

### **Low Priority:** 5
16. Inconsistent error handling
17. Missing database indexes
18. No input length validation
19. Hardcoded paths
20. No logging system

---

## 🎯 **RECOMMENDED ACTION PLAN**

### **Phase 1: Immediate (Critical)**
1. ✅ Implement secure session configuration
2. ✅ Fix logout implementation
3. ✅ Add CSRF protection to all forms
4. ✅ Secure file upload handling
5. ✅ Improve error handling

### **Phase 2: Short-term (High Priority)**
1. ✅ Add database transactions
2. ✅ Fix hardcoded values
3. ✅ Implement proper file upload flow
4. ✅ Strengthen password generation
5. ✅ Normalize email handling

### **Phase 3: Medium-term (Medium Priority)**
1. ✅ Add rate limiting
2. ✅ Implement session timeout
3. ✅ Add file type verification
4. ✅ Implement IDOR protection
5. ✅ Add input sanitization

### **Phase 4: Long-term (Low Priority)**
1. ✅ Create logging system
2. ✅ Add database indexes
3. ✅ Standardize error handling
4. ✅ Add input length validation
5. ✅ Refactor hardcoded paths

---

## 🔧 **TESTING CHECKLIST**

- [ ] Test session security with session hijacking tools
- [ ] Test CSRF protection with automated tools
- [ ] Test file upload with malicious files
- [ ] Test SQL injection with SQLMap
- [ ] Test XSS vulnerabilities
- [ ] Test rate limiting effectiveness
- [ ] Test session timeout functionality
- [ ] Test database transaction rollback
- [ ] Perform penetration testing
- [ ] Code review by security expert

---

**Last Updated:** January 30, 2026  
**Reviewed By:** Cascade AI Code Analyzer  
**Next Review:** February 15, 2026
