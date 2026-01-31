-- Employee Requirements Table for document submission
-- Run this SQL to create the table

CREATE TABLE IF NOT EXISTS employee_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES user_accounts(id) ON DELETE SET NULL,
    UNIQUE KEY unique_employee_doc (employee_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster lookups
CREATE INDEX idx_employee_requirements_status ON employee_requirements(status);
CREATE INDEX idx_employee_requirements_type ON employee_requirements(document_type);
