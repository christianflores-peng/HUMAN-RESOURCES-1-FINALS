<?php
/**
 * PHPMailer - Standalone version for SLATE HR System
 * This is a simplified implementation for Gmail SMTP
 */

class PHPMailer {
    private $host = 'smtp.gmail.com';
    private $port = 587;
    private $username = '';
    private $password = '';
    private $from = '';
    private $fromName = '';
    private $to = [];
    private $subject = '';
    private $body = '';
    private $isHTML = true;
    private $charset = 'UTF-8';
    
    public function __construct() {
        // Load SMTP config if exists
        $configFile = __DIR__ . '/smtp_config.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (isset($config['smtp_host'])) $this->host = $config['smtp_host'];
            if (isset($config['smtp_port'])) $this->port = $config['smtp_port'];
            if (isset($config['smtp_username'])) $this->username = $config['smtp_username'];
            if (isset($config['smtp_password'])) $this->password = $config['smtp_password'];
            if (isset($config['from_email'])) $this->from = $config['from_email'];
            if (isset($config['from_name'])) $this->fromName = $config['from_name'];
        }
    }
    
    public function setFrom($email, $name = '') {
        $this->from = $email;
        $this->fromName = $name;
    }
    
    public function addAddress($email, $name = '') {
        $this->to[] = ['email' => $email, 'name' => $name];
    }
    
    public function Subject($subject) {
        $this->subject = $subject;
    }
    
    public function Body($body) {
        $this->body = $body;
    }
    
    public function isHTML($isHTML = true) {
        $this->isHTML = $isHTML;
    }
    
    public function send() {
        // Validate configuration
        if (empty($this->username) || empty($this->password)) {
            error_log("PHPMailer: SMTP credentials not configured");
            return false;
        }
        
        if (empty($this->to)) {
            error_log("PHPMailer: No recipient specified");
            return false;
        }
        
        // Connect to SMTP server
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("PHPMailer: Failed to connect to SMTP server: {$errstr} ({$errno})");
            return false;
        }
        
        // Read server response
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            error_log("PHPMailer: Invalid server response: {$response}");
            fclose($socket);
            return false;
        }
        
        // Send EHLO
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($socket, 515);
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            error_log("PHPMailer: STARTTLS failed: {$response}");
            fclose($socket);
            return false;
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("PHPMailer: Failed to enable TLS encryption");
            fclose($socket);
            return false;
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($socket, 515);
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($this->username) . "\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($this->password) . "\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '235') {
            error_log("PHPMailer: Authentication failed: {$response}");
            fclose($socket);
            return false;
        }
        
        // Send MAIL FROM
        fputs($socket, "MAIL FROM: <{$this->from}>\r\n");
        $response = fgets($socket, 515);
        
        // Send RCPT TO
        foreach ($this->to as $recipient) {
            fputs($socket, "RCPT TO: <{$recipient['email']}>\r\n");
            $response = fgets($socket, 515);
        }
        
        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        
        // Build email headers and body
        $headers = "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        if ($this->isHTML) {
            $headers .= "Content-Type: text/html; charset={$this->charset}\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset={$this->charset}\r\n";
        }
        $headers .= "Subject: {$this->subject}\r\n";
        
        foreach ($this->to as $recipient) {
            $headers .= "To: {$recipient['email']}\r\n";
        }
        
        $message = $headers . "\r\n" . $this->body . "\r\n.\r\n";
        fputs($socket, $message);
        $response = fgets($socket, 515);
        
        // Send QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        if (substr($response, 0, 3) == '250') {
            error_log("PHPMailer: Email sent successfully to: " . $this->to[0]['email']);
            return true;
        } else {
            error_log("PHPMailer: Failed to send email: {$response}");
            return false;
        }
    }
}
