<?php
// Terms & Conditions page - can be included or accessed directly
session_start();

// Get parameters for redirect after accepting terms
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - HR1 Management System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a1929;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            padding: 1rem;
        }

        .terms-container {
            width: 100%;
            max-width: 520px;
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .terms-header {
            text-align: left;
            margin-bottom: 1rem;
        }

        .terms-header h1 {
            font-size: 1.4rem;
            color: #ffffff;
            font-weight: 600;
        }

        .terms-content {
            background: #2a3544;
            border-radius: 8px;
            padding: 1.25rem;
            max-height: 320px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .terms-content::-webkit-scrollbar {
            width: 8px;
        }

        .terms-content::-webkit-scrollbar-track {
            background: #1e2936;
            border-radius: 4px;
        }

        .terms-content::-webkit-scrollbar-thumb {
            background: #0ea5e9;
            border-radius: 4px;
        }

        .terms-title {
            text-align: center;
            margin-bottom: 1rem;
        }

        .terms-title h2 {
            color: #0ea5e9;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .terms-title p {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .terms-section {
            margin-bottom: 1rem;
        }

        .terms-section h3 {
            color: #60a5fa;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .terms-section p {
            color: #cbd5e1;
            line-height: 1.5;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }

        .terms-section ul {
            list-style: none;
            padding-left: 0;
        }

        .terms-section ul li {
            color: #cbd5e1;
            line-height: 1.5;
            margin-bottom: 0.35rem;
            padding-left: 1.25rem;
            position: relative;
            font-size: 0.8rem;
        }

        .terms-section ul li::before {
            content: "•";
            position: absolute;
            left: 0.4rem;
            color: #0ea5e9;
        }

        .legal-basis {
            background: rgba(14, 165, 233, 0.1);
            border-left: 3px solid #0ea5e9;
            padding: 0.75rem;
            margin-top: 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .legal-basis strong {
            color: #60a5fa;
        }

        .legal-basis span {
            color: #e2e8f0;
        }

        .agreement-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .agreement-section input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0ea5e9;
            flex-shrink: 0;
        }

        .agreement-section label {
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }

        .button-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .btn {
            padding: 0.625rem 1.5rem;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            min-width: 120px;
        }

        .btn-secondary {
            background: #475569;
            color: #ffffff;
            border-color: #475569;
        }

        .btn-secondary:hover {
            background: #64748b;
            border-color: #64748b;
        }

        .btn-primary {
            background: #0ea5e9;
            color: #ffffff;
            border-color: #0ea5e9;
        }

        .btn-primary:hover {
            background: #0284c7;
            border-color: #0284c7;
        }

        .btn-primary:disabled {
            background: #475569;
            border-color: #475569;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-primary:disabled:hover {
            background: #475569;
        }

        @media (max-width: 768px) {
            .terms-container {
                padding: 1.25rem;
            }

            .terms-content {
                max-height: 280px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <div class="terms-container">
        <div class="terms-header">
            <h1>Terms & Conditions</h1>
        </div>

        <div class="terms-content">
            <div class="terms-title">
                <h2>SLATE Freight Management System</h2>
                <p>Supplier Registration Terms & Conditions</p>
            </div>

            <div class="terms-section">
                <h3>1. Registration Agreement & Commitment</h3>
                <p>By registering as a supplier with SLATE Freight Management System, you agree to comply with all terms and conditions outlined herein. This agreement becomes effective upon successful registration and approval by our procurement team.</p>
                <ul>
                    <li>All information provided must be accurate and truthful</li>
                    <li>Suppliers must maintain current business registration and permits</li>
                    <li>Registration does not guarantee business opportunities</li>
                    <li>SLATE reserves the right to approve or reject supplier applications</li>
                </ul>
                <div class="legal-basis">
                    <strong>Legal Basis:</strong> <span>Republic Act No. 8792 (Electronic Commerce Act of 2000) and Republic Act No. 10173 (Data Privacy Act of 2012)</span>
                </div>
            </div>

            <div class="terms-section">
                <h3>2. Data Privacy & Information Security</h3>
                <p>We are committed to protecting your personal and business information in accordance with the Data Privacy Act of 2012.</p>
                <ul>
                    <li>Personal data will be collected, processed, and stored securely</li>
                    <li>Information will only be used for supplier management purposes</li>
                    <li>Data will not be shared with third parties without consent</li>
                    <li>You have the right to access, correct, or delete your data</li>
                    <li>We implement industry-standard security measures</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>3. Supplier Responsibilities</h3>
                <p>As a registered supplier, you agree to:</p>
                <ul>
                    <li>Provide accurate company information and documentation</li>
                    <li>Maintain valid business licenses and certifications</li>
                    <li>Update your profile when business information changes</li>
                    <li>Respond to inquiries and requests in a timely manner</li>
                    <li>Comply with all applicable laws and regulations</li>
                    <li>Maintain professional standards in all communications</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>4. Account Security</h3>
                <p>You are responsible for maintaining the confidentiality of your account credentials.</p>
                <ul>
                    <li>Keep your password secure and confidential</li>
                    <li>Notify us immediately of any unauthorized access</li>
                    <li>Do not share your account with others</li>
                    <li>Use strong passwords and change them regularly</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>5. Business Conduct</h3>
                <p>All suppliers must adhere to ethical business practices:</p>
                <ul>
                    <li>No fraudulent or deceptive practices</li>
                    <li>Honest representation of products and services</li>
                    <li>Compliance with anti-corruption laws</li>
                    <li>Fair pricing and transparent billing</li>
                    <li>Respect for intellectual property rights</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>6. Service Availability</h3>
                <p>While we strive for continuous availability:</p>
                <ul>
                    <li>System may be unavailable during maintenance</li>
                    <li>We do not guarantee uninterrupted access</li>
                    <li>Features may be updated or modified</li>
                    <li>We reserve the right to suspend accounts for violations</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>7. Termination</h3>
                <p>Either party may terminate this agreement:</p>
                <ul>
                    <li>Suppliers may close their account at any time</li>
                    <li>We may suspend or terminate accounts for violations</li>
                    <li>Termination does not affect existing obligations</li>
                    <li>Data will be retained as required by law</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>8. Limitation of Liability</h3>
                <p>SLATE Freight Management System shall not be liable for:</p>
                <ul>
                    <li>Indirect, incidental, or consequential damages</li>
                    <li>Loss of profits or business opportunities</li>
                    <li>Data loss due to technical failures</li>
                    <li>Actions of third parties</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>9. Modifications to Terms</h3>
                <p>We reserve the right to modify these terms at any time. Continued use of the system after changes constitutes acceptance of the modified terms.</p>
            </div>

            <div class="terms-section">
                <h3>10. Governing Law</h3>
                <p>These terms are governed by the laws of the Republic of the Philippines. Any disputes shall be resolved in the appropriate courts of the Philippines.</p>
            </div>

            <div class="terms-section">
                <h3>11. Contact Information</h3>
                <p>For questions about these terms, contact us at:</p>
                <ul>
                    <li>Email: support@slatefreight.com</li>
                    <li>Phone: +63 (2) 8123-4567</li>
                    <li>Address: SLATE Freight Management System, Manila, Philippines</li>
                </ul>
            </div>
        </div>

        <div class="agreement-section">
            <input type="checkbox" id="agree-checkbox" onchange="toggleProceedButton()">
            <label for="agree-checkbox">I have read and agree to the Terms & Conditions</label>
        </div>

        <div class="button-group">
            <button class="btn btn-secondary" onclick="window.location.href='<?php echo $job_id ? '../careers.php' : 'login.php'; ?>'">Back</button>
            <button class="btn btn-primary" id="proceed-btn" disabled onclick="proceedToRegistration()">Proceed to Registration</button>
        </div>
    </div>

    <script>
        function toggleProceedButton() {
            const checkbox = document.getElementById('agree-checkbox');
            const proceedBtn = document.getElementById('proceed-btn');
            proceedBtn.disabled = !checkbox.checked;
        }

        function proceedToRegistration() {
            const checkbox = document.getElementById('agree-checkbox');
            if (checkbox.checked) {
                <?php if ($job_id && $type === 'applicant'): ?>
                // Redirect to apply page with job_id
                window.location.href = '../public/apply.php?job_id=<?php echo $job_id; ?>&terms_accepted=true';
                <?php else: ?>
                // Redirect to applicant registration with terms acceptance flag
                window.location.href = 'register-applicant.php?terms_accepted=true';
                <?php endif; ?>
            }
        }
    </script>
</body>
</html>
