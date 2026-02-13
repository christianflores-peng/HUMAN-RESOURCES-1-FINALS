<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HR1 System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            max-height: calc(100vh - 2rem);
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
            padding: 1.5rem;
            text-align: center;
            color: white;
            flex-shrink: 0;
        }
        
        .header .icon {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .content {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .info-box h3 {
            color: #0369a1;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .info-box p {
            color: #475569;
            line-height: 1.5;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .steps {
            margin: 1rem 0;
        }
        
        .step {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            align-items: start;
        }
        
        .step-number {
            background: #0ea5e9;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        
        .step-content h4 {
            color: #1e293b;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }
        
        .step-content p {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #0ea5e9;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        i[data-lucide] {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon"><i data-lucide="clipboard-pen" style="width: 4rem; height: 4rem;"></i></div>
            <h1>Welcome to HR1!</h1>
            <p>Join our team by submitting your application</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <h3>📢 Important Information</h3>
                <p><strong>To register as a candidate, you must submit a job application through our Careers page.</strong></p>
                <p>We don't have traditional user registration. Instead, you apply for specific positions that match your skills and experience.</p>
            </div>
            
            <div class="steps">
                <h3 style="color: #1e293b; margin-bottom: 0.75rem; font-size: 1rem;">How to Apply:</h3>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Browse Available Positions</h4>
                        <p>Visit our Careers page to see all open job positions</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Find Your Match</h4>
                        <p>Look for positions that match your skills and interests</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Submit Application</h4>
                        <p>Click "Apply Now" and fill out the application form with your details</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Wait for Response</h4>
                        <p>Our HR team will review your application and contact you</p>
                    </div>
                </div>
            </div>
            
            <div class="button-group">
                <a href="../careers.php" class="btn btn-primary">
                    <i data-lucide="briefcase"></i>
                    View Job Openings
                </a>
                <a href="../index.php" class="btn btn-secondary">
                    <i data-lucide="home"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
