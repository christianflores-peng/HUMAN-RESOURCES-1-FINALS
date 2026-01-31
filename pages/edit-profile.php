<?php
session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

// Check if user is logged in and is an Applicant
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$user = getUserWithRole($_SESSION['user_id']);
if (!$user || $user['role_type'] !== 'Applicant') {
    header('Location: ../partials/login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $error_message = 'First name and last name are required.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error_message = 'Please enter a valid phone number (10-11 digits).';
    } else {
        try {
            // Update user_accounts
            $updated = updateRecord(
                "UPDATE user_accounts 
                 SET first_name = ?, last_name = ?, phone = ?, address = ?
                 WHERE id = ?",
                [$first_name, $last_name, $phone, $address, $_SESSION['user_id']]
            );
            
            if ($updated) {
                // Update session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                
                $success_message = 'Profile updated successfully!';
                
                // Refresh user data
                $user = getUserWithRole($_SESSION['user_id']);
            } else {
                $error_message = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Get current profile data
$profile = fetchSingle(
    "SELECT * FROM user_accounts WHERE id = ?",
    [$_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SLATE HR</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            color: #f8fafc;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 54, 0.8);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 1.5rem;
            color: #0ea5e9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(51, 65, 85, 0.9);
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .form-container {
            background: rgba(30, 41, 54, 0.8);
            padding: 2rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-label .required {
            color: #ef4444;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-info {
            background: rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(14, 165, 233, 0.3);
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .form-info p {
            color: #0ea5e9;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(58, 69, 84, 0.5);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span class="material-symbols-outlined">edit</span>
                Edit Profile
            </h1>
            <a href="applicant-dashboard.php" class="btn btn-secondary">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Dashboard
            </a>
        </div>

        <!-- Form -->
        <div class="form-container">
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span class="material-symbols-outlined">error</span>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <div class="form-info">
                <p>
                    <span class="material-symbols-outlined">info</span>
                    Update your personal information. Email address cannot be changed.
                </p>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            First Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="first_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($profile['first_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Last Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="last_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($profile['last_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($profile['personal_email']); ?>"
                            disabled
                            style="opacity: 0.6; cursor: not-allowed;"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"
                            placeholder="09123456789"
                            pattern="[0-9]{10,11}"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea 
                            name="address" 
                            class="form-textarea"
                            placeholder="Enter your complete address"
                        ><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="applicant-dashboard.php" class="btn btn-secondary">
                        <span class="material-symbols-outlined">cancel</span>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">save</span>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
