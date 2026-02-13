<?php
/**
 * HR1 Module: Role-Based Access Control Helper
 * Slate Freight Management System
 * 
 * Functions for checking user permissions based on role
 */

require_once __DIR__ . '/../database/config.php';

/**
 * Check if user has permission for a specific action on a module
 * 
 * @param int $userId User ID
 * @param string $module Module name
 * @param string $action Action type (view, create, edit, delete, approve)
 * @return bool True if permitted, false otherwise
 */
function hasPermission($userId, $module, $action = 'view') {
    try {
        $user = getUserWithRole($userId);
        if (!$user || !$user['role_id']) {
            return false;
        }
        
        $column = 'can_' . strtolower($action);
        $validColumns = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve'];
        
        if (!in_array($column, $validColumns)) {
            return false;
        }
        
        $permission = fetchSingle(
            "SELECT {$column}, scope FROM role_permissions 
             WHERE role_id = ? AND module = ?",
            [$user['role_id'], $module]
        );
        
        return $permission && $permission[$column] == 1;
    } catch (Exception $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get permission scope for a user on a module
 * 
 * @param int $userId User ID
 * @param string $module Module name
 * @return string Scope (all, department, own) or null
 */
function getPermissionScope($userId, $module) {
    try {
        $user = getUserWithRole($userId);
        if (!$user || !$user['role_id']) {
            return null;
        }
        
        $permission = fetchSingle(
            "SELECT scope FROM role_permissions 
             WHERE role_id = ? AND module = ?",
            [$user['role_id'], $module]
        );
        
        return $permission ? $permission['scope'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get user details with role information
 * 
 * @param int $userId User ID
 * @return array|null User details with role
 */
function getUserWithRole($userId) {
    try {
        // Try user_accounts table first
        $user = fetchSingle(
            "SELECT ua.*, r.role_name, r.role_type, r.access_level,
                    d.department_name, d.department_code
             FROM user_accounts ua
             LEFT JOIN roles r ON ua.role_id = r.id
             LEFT JOIN departments d ON ua.department_id = d.id
             WHERE ua.id = ?",
            [$userId]
        );
        
        if ($user) {
            return $user;
        }
        
        // Fallback to users table for backward compatibility
        $user = fetchSingle(
            "SELECT id, username, role, full_name, email, phone, department_id
             FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user) {
            // Map old role to role_type
            $user['role_type'] = mapLegacyRole($user['role']);
            $user['role_id'] = getRoleIdByType($user['role_type']);
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("Get user failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Map legacy role names to new role types
 * 
 * @param string $legacyRole Legacy role name
 * @return string New role type
 */
function mapLegacyRole($legacyRole) {
    $adminRoles = ['Administrator', 'admin_Human Resource 1', 'admin_Human Resource 2', 
                   'admin_Human Resource 3', 'admin_Human Resource 4', 'admin_Logistics 1',
                   'admin_Logistics 2', 'admin_Core Transaction 1', 'admin_Core Transaction 2',
                   'admin_Core Transaction 3', 'admin_Financials'];
    
    $hrRoles = ['Applicant Management', 'Recruitment Management', 'New Hire Onboarding',
                'HR Manager', 'Recruiter'];
    
    $managerRoles = ['Fleet Manager', 'Warehouse Manager', 'Logistics Manager', 'Manager', 'Supervisor'];
    
    if (in_array($legacyRole, $adminRoles) || strpos($legacyRole, 'admin_') === 0) {
        return 'Admin';
    }
    if (in_array($legacyRole, $hrRoles)) {
        return 'HR_Staff';
    }
    if (in_array($legacyRole, $managerRoles)) {
        return 'Manager';
    }
    if ($legacyRole === 'Applicant') {
        return 'Applicant';
    }
    
    return 'Employee';
}

/**
 * Get role ID by role type
 * 
 * @param string $roleType Role type (Admin, HR_Staff, Manager, Employee, Applicant)
 * @return int|null Role ID
 */
function getRoleIdByType($roleType) {
    try {
        $role = fetchSingle(
            "SELECT id FROM roles WHERE role_type = ? LIMIT 1",
            [$roleType]
        );
        return $role ? $role['id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Check if user is Admin
 * 
 * @param int $userId User ID
 * @return bool
 */
function isAdmin($userId) {
    $user = getUserWithRole($userId);
    return $user && ($user['role_type'] === 'Admin' || strpos($user['role'] ?? '', 'admin_') === 0);
}

/**
 * Check if user is HR Staff
 * 
 * @param int $userId User ID
 * @return bool
 */
function isHRStaff($userId) {
    $user = getUserWithRole($userId);
    if (!$user) return false;
    
    $hrRoleTypes = ['HR_Staff', 'Admin'];
    $hrRoles = ['Applicant Management', 'Recruitment Management', 'New Hire Onboarding', 'HR Manager', 'Recruiter'];
    
    return in_array($user['role_type'] ?? '', $hrRoleTypes) || in_array($user['role'] ?? '', $hrRoles);
}

/**
 * Check if user is Manager
 * 
 * @param int $userId User ID
 * @return bool
 */
function isManager($userId) {
    $user = getUserWithRole($userId);
    return $user && $user['role_type'] === 'Manager';
}

/**
 * Get employees filtered by user's access level
 * - Admin/HR: All employees
 * - Manager: Only department employees
 * - Employee: Only self
 * 
 * @param int $userId Current user ID
 * @param array $filters Additional filters
 * @return array List of employees
 */
function getAccessibleEmployees($userId, $filters = []) {
    $user = getUserWithRole($userId);
    if (!$user) {
        return [];
    }
    
    $sql = "SELECT ua.*, r.role_name, r.role_type, d.department_name 
            FROM user_accounts ua
            LEFT JOIN roles r ON ua.role_id = r.id
            LEFT JOIN departments d ON ua.department_id = d.id
            WHERE 1=1";
    $params = [];
    
    // Apply role-based filtering
    if ($user['role_type'] === 'Manager') {
        // Manager sees only their department
        $sql .= " AND ua.department_id = ?";
        $params[] = $user['department_id'];
    } elseif ($user['role_type'] === 'Employee') {
        // Employee sees only self
        $sql .= " AND ua.id = ?";
        $params[] = $userId;
    }
    // Admin and HR_Staff see all (no additional filter)
    
    // Apply additional filters
    if (!empty($filters['status'])) {
        $sql .= " AND ua.employment_status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['department_id'])) {
        $sql .= " AND ua.department_id = ?";
        $params[] = $filters['department_id'];
    }
    
    $sql .= " ORDER BY ua.last_name, ua.first_name";
    
    try {
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Get employees failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get applicants filtered by user's access level
 * - Admin/HR: All applicants
 * - Manager: Only applicants for their department's jobs
 * 
 * @param int $userId Current user ID
 * @param array $filters Additional filters
 * @return array List of applicants
 */
function getAccessibleApplicants($userId, $filters = []) {
    $user = getUserWithRole($userId);
    if (!$user) {
        return [];
    }
    
    $sql = "SELECT ja.*, jp.title as job_title, jp.department_id, d.department_name
            FROM job_applications ja
            LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
            LEFT JOIN departments d ON jp.department_id = d.id
            WHERE 1=1";
    $params = [];
    
    // Apply role-based filtering
    if ($user['role_type'] === 'Manager') {
        // Manager sees only applicants for their department
        $sql .= " AND jp.department_id = ?";
        $params[] = $user['department_id'];
    }
    // Admin and HR_Staff see all
    
    // Apply additional filters
    if (!empty($filters['status'])) {
        $sql .= " AND ja.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['job_id'])) {
        $sql .= " AND ja.job_posting_id = ?";
        $params[] = $filters['job_id'];
    }
    
    $sql .= " ORDER BY ja.applied_date DESC";
    
    try {
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Get applicants failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can view specific employee's documents
 * 
 * @param int $viewerId User trying to view
 * @param int $employeeId Employee whose documents are being viewed
 * @return bool
 */
function canViewEmployeeDocuments($viewerId, $employeeId) {
    $viewer = getUserWithRole($viewerId);
    if (!$viewer) {
        return false;
    }
    
    // Admin and HR can view all
    if (in_array($viewer['role_type'], ['Admin', 'HR_Staff'])) {
        return true;
    }
    
    // Self can view own
    if ($viewerId === $employeeId) {
        return true;
    }
    
    // Manager can view their department's employees
    if ($viewer['role_type'] === 'Manager') {
        $employee = getUserWithRole($employeeId);
        return $employee && $employee['department_id'] === $viewer['department_id'];
    }
    
    return false;
}

/**
 * Get documents visible to a user for a specific employee
 * Managers cannot see salary/payroll documents
 * 
 * @param int $viewerId User trying to view
 * @param int $employeeId Employee whose documents are being viewed
 * @return array List of documents
 */
function getVisibleDocuments($viewerId, $employeeId) {
    if (!canViewEmployeeDocuments($viewerId, $employeeId)) {
        return [];
    }
    
    $viewer = getUserWithRole($viewerId);
    
    $sql = "SELECT * FROM employee_documents WHERE user_id = ?";
    $params = [$employeeId];
    
    // Managers cannot see salary-related documents
    if ($viewer['role_type'] === 'Manager') {
        $sql .= " AND document_type NOT IN ('Payslip', 'Salary', 'Tax')";
    }
    
    $sql .= " ORDER BY document_type, created_at DESC";
    
    try {
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get dashboard stats based on user role
 * 
 * @param int $userId User ID
 * @return array Dashboard statistics
 */
function getDashboardStats($userId) {
    $user = getUserWithRole($userId);
    if (!$user) {
        return [];
    }
    
    $stats = [];
    
    try {
        if (in_array($user['role_type'], ['Admin', 'HR_Staff'])) {
            // Full stats for Admin/HR
            $stats['total_employees'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'Active'")['count'] ?? 0;
            $stats['new_applicants'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'new'")['count'] ?? 0;
            $stats['pending_onboarding'] = fetchSingle("SELECT COUNT(DISTINCT user_id) as count FROM employee_onboarding_progress WHERE status = 'Pending'")['count'] ?? 0;
            $stats['open_positions'] = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'active'")['count'] ?? 0;
        } elseif ($user['role_type'] === 'Manager') {
            // Department-specific stats for Manager
            $deptId = $user['department_id'];
            $stats['team_count'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE department_id = ? AND status = 'Active'", [$deptId])['count'] ?? 0;
            $stats['probation_reviews'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE department_id = ? AND employment_status = 'Probation'", [$deptId])['count'] ?? 0;
            $stats['expiring_licenses'] = fetchSingle(
                "SELECT COUNT(*) as count FROM employee_documents ed
                 JOIN user_accounts ua ON ed.user_id = ua.id
                 WHERE ua.department_id = ? AND ed.document_type = 'License' 
                 AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
                [$deptId]
            )['count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Get dashboard stats failed: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Require specific role to access page
 * Redirects to login or access denied if not authorized
 * 
 * @param array $allowedRoles Array of allowed role types
 * @param string $redirectUrl URL to redirect to if not authorized
 */
function requireRole($allowedRoles, $redirectUrl = '../auth/login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: {$redirectUrl}");
        exit();
    }
    
    $user = getUserWithRole($_SESSION['user_id']);
    if (!$user || !in_array($user['role_type'], $allowedRoles)) {
        http_response_code(403);
        echo "Access Denied: You do not have permission to access this page.";
        exit();
    }
}
