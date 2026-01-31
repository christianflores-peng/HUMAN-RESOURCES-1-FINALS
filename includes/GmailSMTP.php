<?php
/**
 * Gmail SMTP Mailer - Working implementation for Gmail authentication
 */

class GmailSMTP {
    private $config;
    private $socket;
    private $lastError = '';
    
    public function __construct() {
        $this->config = include __DIR__ . '/smtp_config.php';
    }
    
    public function send($to, $subject, $body, $fromName = '', $fromEmail = '') {
        if (empty($fromName)) {
            $fromName = $this->config['from_name'];
        }
        if (empty($fromEmail)) {
            $fromEmail = $this->config['from_email'];
        }
        
        try {
            // Connect to Gmail SMTP
            if (!$this->connect()) {
                return false;
            }
            
            // Authenticate
            if (!$this->authenticate()) {
                $this->disconnect();
                return false;
            }
            
            // Send email
            $result = $this->sendMessage($to, $subject, $body, $fromName, $fromEmail);
            
            // Disconnect
            $this->disconnect();
            
            return $result;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("GmailSMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function connect() {
        $host = $this->config['smtp_host'];
        $port = $this->config['smtp_port'];
        
        // Use SSL/TLS connection
        $this->socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])
        );
        
        if (!$this->socket) {
            $this->lastError = "Failed to connect: {$errstr} ({$errno})";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Read greeting
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '220') {
            $this->lastError = "Invalid greeting: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Send EHLO
        $this->sendCommand("EHLO localhost");
        $response = $this->readResponse();
        
        // Start TLS
        $this->sendCommand("STARTTLS");
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '220') {
            $this->lastError = "STARTTLS failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Enable TLS encryption
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $this->lastError = "Failed to enable TLS";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Send EHLO again after TLS
        $this->sendCommand("EHLO localhost");
        $response = $this->readResponse();
        
        return true;
    }
    
    private function authenticate() {
        $username = $this->config['smtp_username'];
        $password = $this->config['smtp_password'];
        
        // Send AUTH LOGIN
        $this->sendCommand("AUTH LOGIN");
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '334') {
            $this->lastError = "AUTH LOGIN failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Send username
        $this->sendCommand(base64_encode($username));
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '334') {
            $this->lastError = "Username rejected: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Send password
        $this->sendCommand(base64_encode($password));
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '235') {
            $this->lastError = "Authentication failed: {$response}";
            error_log("GmailSMTP: Authentication failed. Check your App Password.");
            return false;
        }
        
        error_log("GmailSMTP: Authentication successful");
        return true;
    }
    
    private function sendMessage($to, $subject, $body, $fromName, $fromEmail) {
        // MAIL FROM
        $this->sendCommand("MAIL FROM:<{$fromEmail}>");
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '250') {
            $this->lastError = "MAIL FROM failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // RCPT TO
        $this->sendCommand("RCPT TO:<{$to}>");
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '250') {
            $this->lastError = "RCPT TO failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // DATA
        $this->sendCommand("DATA");
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '354') {
            $this->lastError = "DATA failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        // Build message
        $message = "From: {$fromName} <{$fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";
        
        // Send message
        fwrite($this->socket, $message);
        $response = $this->readResponse();
        if (substr($response, 0, 3) != '250') {
            $this->lastError = "Message send failed: {$response}";
            error_log("GmailSMTP: " . $this->lastError);
            return false;
        }
        
        error_log("GmailSMTP: Email sent successfully to {$to}");
        return true;
    }
    
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
    }
    
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return trim($response);
    }
    
    private function disconnect() {
        if ($this->socket) {
            $this->sendCommand("QUIT");
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}
