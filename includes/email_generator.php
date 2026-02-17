<?php
/**
 * HR1 Module: Automated Email Generation
 * Slate Freight Management System
 * 
 * Generates @slatefreight.com email addresses for new employees
 */

require_once __DIR__ . '/../database/config.php';

/**
 * Generate company email for new employee
 * Algorithm:
 * 1. Get first name and last name
 * 2. Sanitize (lowercase, remove special chars)
 * 3. Check uniqueness in database
 * 4. Add number suffix if duplicate exists
 * 5. Concatenate with domain
 * 
 * @param string $firstName Employee's first name
 * @param string $lastName Employee's last name
 * @param string $middleName Optional middle name for uniqueness
 * @return string Generated email address
 */
function generateCompanyEmail($firstName, $lastName, $middleName = '') {
    $domain = '@slatefreight.com';
    
    // Sanitize names - lowercase, remove special characters and spaces
    $cleanFirst = sanitizeName($firstName);
    $cleanLast = sanitizeName($lastName);
    $cleanMiddle = sanitizeName($middleName);
    
    // Base email format: firstname.lastname@slatefreight.com
    $baseEmail = $cleanFirst . '.' . $cleanLast;
    $proposedEmail = $baseEmail . $domain;
    
    // Check if email already exists
    if (!emailExists($proposedEmail)) {
        return $proposedEmail;
    }
    
    // Try with middle initial if available
    if (!empty($cleanMiddle)) {
        $middleInitial = substr($cleanMiddle, 0, 1);
        $proposedEmail = $cleanFirst . '.' . $middleInitial . '.' . $cleanLast . $domain;
        if (!emailExists($proposedEmail)) {
            return $proposedEmail;
        }
    }
    
    // Add number suffix until unique
    $counter = 1;
    do {
        $proposedEmail = $baseEmail . $counter . $domain;
        $counter++;
    } while (emailExists($proposedEmail) && $counter < 100);
    
    return $proposedEmail;
}

/**
 * Sanitize name for email generation
 * - Convert to lowercase
 * - Remove accents/special characters
 * - Remove spaces
 * - Keep only alphanumeric
 * 
 * @param string $name Name to sanitize
 * @return string Sanitized name
 */
function sanitizeName($name) {
    // Convert to lowercase
    $name = strtolower(trim($name));
    
    // Remove accents
    $name = removeAccents($name);
    
    // Remove all non-alphanumeric characters except dots
    $name = preg_replace('/[^a-z0-9]/', '', $name);
    
    return $name;
}

/**
 * Remove accents from string
 * 
 * @param string $str String with accents
 * @return string String without accents
 */
function removeAccents($str) {
    $accents = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c'
    ];
    return strtr($str, $accents);
}

/**
 * Check if email already exists in database
 * 
 * @param string $email Email to check
 * @return bool True if exists, false otherwise
 */
function emailExists($email) {
    try {
        // Check in user_accounts table
        $result = fetchSingle(
            "SELECT id FROM user_accounts WHERE company_email = ?",
            [$email]
        );
        
        if ($result) {
            return true;
        }
        
        // Also check in users table for backward compatibility
        $result = fetchSingle(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        return $result ? true : false;
    } catch (Exception $e) {
        error_log("Email check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate Employee ID
 * Format: EMP-YYYY-XXXXX (e.g., EMP-2026-00001)
 * 
 * @return string Generated employee ID
 */
function generateEmployeeId() {
    $year = date('Y');
    $prefix = "EMP-{$year}-";
    
    try {
        // Get the last employee ID for this year
        $result = fetchSingle(
            "SELECT employee_id FROM user_accounts 
             WHERE employee_id LIKE ? 
             ORDER BY employee_id DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($result) {
            // Extract the number and increment
            $lastNum = intval(substr($result['employee_id'], -5));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return $prefix . str_pad($newNum, 5, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        error_log("Employee ID generation failed: " . $e->getMessage());
        // Fallback with timestamp
        return $prefix . str_pad(time() % 100000, 5, '0', STR_PAD_LEFT);
    }
}

/**
 * Generate Manager ID
 * Format: MGR-XXX (e.g., MGR-001)
 * 
 * @return string Generated manager ID
 */
function generateManagerId() {
    $prefix = "MGR-";
    
    try {
        $result = fetchSingle(
            "SELECT employee_id FROM user_accounts 
             WHERE employee_id LIKE ? 
             ORDER BY employee_id DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($result) {
            $lastNum = intval(substr($result['employee_id'], -3));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return $prefix . str_pad($newNum, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefix . str_pad(time() % 1000, 3, '0', STR_PAD_LEFT);
    }
}

/**
 * Process applicant hire - Create employee account with company email
 * This is the HIRE button trigger function
 * 
 * @param int $applicantId The applicant's ID from job_applications
 * @param int $roleId The role to assign
 * @param int $departmentId The department to assign
 * @param string $jobTitle The job title
 * @param int $hiredByUserId The HR staff who processed the hire
 * @return array Result with status and employee details
 */
function processApplicantHire($applicantId, $roleId, $departmentId, $jobTitle, $hiredByUserId) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Get applicant details
        $applicant = fetchSingle(
            "SELECT * FROM job_applications WHERE id = ?",
            [$applicantId]
        );
        
        if (!$applicant) {
            throw new Exception("Applicant not found");
        }
        
        // Generate company email
        $companyEmail = generateCompanyEmail(
            $applicant['first_name'],
            $applicant['last_name']
        );
        
        // Generate employee ID
        $employeeId = generateEmployeeId();
        
        // Generate temporary password (to be changed on first login)
        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        try {
            // Direct PDO insert with detailed error catching
            $sql = "INSERT INTO user_accounts (
                employee_id, first_name, last_name, personal_email, company_email,
                phone, password_hash, role_id, department_id, job_title,
                hire_date, employment_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Probation', 'Active')";
            
            $params = [
                $employeeId,
                $applicant['first_name'],
                $applicant['last_name'],
                $applicant['email'],
                $companyEmail,
                $applicant['phone'],
                $passwordHash,
                $roleId,
                $departmentId,
                $jobTitle
            ];
            
            $stmt = $pdo->prepare($sql);
            $executeResult = $stmt->execute($params);
            
            if (!$executeResult) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("INSERT failed - SQLSTATE: {$errorInfo[0]}, Error Code: {$errorInfo[1]}, Message: {$errorInfo[2]}");
            }
            
            $userId = $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            throw new Exception("Database error creating user account: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Failed to create user account: " . $e->getMessage());
        }
        
        // Update applicant status
        updateRecord(
            "UPDATE job_applications SET status = 'hired', hired_date = NOW() WHERE id = ?",
            [$applicantId]
        );
        
        // Log the action (non-critical, don't fail hire if logging fails)
        try {
            logAuditAction($hiredByUserId, 'HIRE', 'job_applications', $applicantId, null, [
                'employee_id' => $employeeId,
                'company_email' => $companyEmail,
                'department_id' => $departmentId
            ], "Hired applicant: {$applicant['first_name']} {$applicant['last_name']}");
            
            // System log for email creation
            logAuditAction(null, 'SYSTEM', 'user_accounts', $userId, null, [
                'company_email' => $companyEmail
            ], "Created user account: {$companyEmail}");
        } catch (Exception $e) {
            error_log("Audit logging failed (non-critical): " . $e->getMessage());
        }
        
        // Initialize onboarding tasks for new hire (non-critical)
        try {
            initializeOnboardingTasks($userId, $departmentId);
        } catch (Exception $e) {
            // Onboarding task initialization is non-critical
        }
        
        // Create welcome post in social_recognitions (non-critical)
        try {
            createWelcomePost($userId, $applicant['first_name'], $applicant['last_name'], $jobTitle);
        } catch (Exception $e) {
            error_log("Welcome post creation failed (non-critical): " . $e->getMessage());
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'employee_id' => $employeeId,
            'company_email' => $companyEmail,
            'temp_password' => $tempPassword,
            'user_id' => $userId,
            'message' => "Account Created: {$companyEmail}"
        ];
        
    } catch (PDOException $e) {
        if (isset($pdo)) {
            try { $pdo->rollBack(); } catch (Exception $rollbackEx) {}
        }
        return [
            'success' => false,
            'message' => 'Database Error: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        if (isset($pdo)) {
            try { $pdo->rollBack(); } catch (Exception $rollbackEx) {}
        }
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Initialize onboarding tasks for new employee
 * 
 * @param int $userId The new employee's user ID
 * @param int $departmentId The department ID
 */
function initializeOnboardingTasks($userId, $departmentId) {
    try {
        // Check if onboarding tables exist before querying
        $pdo = getDBConnection();
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'onboarding_tasks'");
        if ($tableCheck->rowCount() === 0) {
            error_log("Onboarding tasks table does not exist - skipping initialization");
            return;
        }
        
        $progressTableCheck = $pdo->query("SHOW TABLES LIKE 'employee_onboarding_progress'");
        if ($progressTableCheck->rowCount() === 0) {
            error_log("Employee onboarding progress table does not exist - skipping initialization");
            return;
        }
        
        // Get all applicable onboarding tasks
        $tasks = fetchAll(
            "SELECT id FROM onboarding_tasks 
             WHERE department_specific IS NULL OR department_specific = ?",
            [$departmentId]
        );
        
        foreach ($tasks as $task) {
            insertRecord(
                "INSERT INTO employee_onboarding_progress (user_id, task_id, status)
                 VALUES (?, ?, 'Pending')",
                [$userId, $task['id']]
            );
        }
    } catch (Exception $e) {
        error_log("Failed to initialize onboarding tasks: " . $e->getMessage());
    }
}

/**
 * Create a welcome post in social_recognitions for a new hire
 * 
 * @param int $userId The new employee's user ID
 * @param string $firstName Employee's first name
 * @param string $lastName Employee's last name
 * @param string $jobTitle The job title assigned
 */
function createWelcomePost($userId, $firstName, $lastName, $jobTitle) {
    try {
        $pdo = getDBConnection();
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'social_recognitions'");
        if ($tableCheck->rowCount() === 0) {
            error_log("social_recognitions table does not exist - skipping welcome post");
            return;
        }
        
        $message = "Welcome to the SLATE Freight Management team, {$firstName} {$lastName}! "
                 . "We're excited to have you join us as {$jobTitle}. "
                 . "Wishing you a great start and a rewarding journey ahead!";
        
        insertRecord(
            "INSERT INTO social_recognitions (recipient_id, given_by, recognition_type, message, badge_icon, is_system_generated, is_public)
             VALUES (?, NULL, 'Welcome', ?, 'party-popper', 1, 1)",
            [$userId, $message]
        );
    } catch (Exception $e) {
        error_log("Failed to create welcome post: " . $e->getMessage());
    }
}

/**
 * Log audit action
 * 
 * @param int|null $userId User performing the action (null for system)
 * @param string $action Action type
 * @param string $module Module/table name
 * @param int|null $recordId Record ID affected
 * @param array|null $oldValues Previous values
 * @param array|null $newValues New values
 * @param string $detail Description of action
 */
function logAuditAction($userId, $action, $module, $recordId = null, $oldValues = null, $newValues = null, $detail = '') {
    try {
        $userEmail = null;
        if ($userId) {
            $user = fetchSingle("SELECT company_email, personal_email FROM user_accounts WHERE id = ?", [$userId]);
            $userEmail = $user['company_email'] ?? null;
            if (!$userEmail) {
                $userEmail = $user['personal_email'] ?? null;
            }
            
            // Fallback to users table
            if (!$userEmail) {
                $user = fetchSingle("SELECT username FROM users WHERE id = ?", [$userId]);
                $userEmail = $user['username'] ?? 'System';
            }
        }
        
        insertRecord(
            "INSERT INTO audit_logs (
                user_id, user_email, action, module, record_id, record_type,
                old_values, new_values, detail, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $userEmail ?? 'System',
                $action,
                $module,
                $recordId,
                $module,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $detail,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
