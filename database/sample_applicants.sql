-- Sample Applicants and Job Postings for HR Recruitment Kanban Board
-- Run this in phpMyAdmin to populate the recruitment pipeline

USE `hr1_hr1data`;

-- Insert sample job postings
INSERT INTO job_postings (id, title, description, department_id, employment_type, salary_min, salary_max, location, status, created_by, created_at) VALUES
(1, 'Truck Driver - Class A', 'Looking for experienced Class A CDL drivers for long-haul routes.', 2, 'Full-time', 35000, 50000, 'Manila', 'Open', 1, NOW()),
(2, 'Warehouse Associate', 'General warehouse duties including picking, packing, and inventory.', 4, 'Full-time', 18000, 25000, 'Laguna', 'Open', 1, NOW()),
(3, 'Fleet Coordinator', 'Coordinate fleet operations and driver schedules.', 2, 'Full-time', 30000, 40000, 'Manila', 'Open', 1, NOW()),
(4, 'Forklift Operator', 'Operate forklift in warehouse environment.', 4, 'Full-time', 20000, 28000, 'Laguna', 'Open', 1, NOW()),
(5, 'Logistics Coordinator', 'Manage shipment tracking and customer communication.', 3, 'Full-time', 28000, 38000, 'Manila', 'Open', 1, NOW())
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- Insert sample job applications (applicants in various stages)
-- NEW APPLICATIONS
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(1, 1, 'Juan', 'Dela Cruz', 'juan.delacruz@gmail.com', '09171234567', 'New', DATE_SUB(NOW(), INTERVAL 1 DAY), '/uploads/resume_juan.pdf'),
(2, 1, 'Pedro', 'Santos', 'pedro.santos@gmail.com', '09182345678', 'New', DATE_SUB(NOW(), INTERVAL 2 DAY), '/uploads/resume_pedro.pdf'),
(3, 2, 'Maria', 'Garcia', 'maria.garcia@gmail.com', '09193456789', 'New', NOW(), '/uploads/resume_maria.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- SCREENING
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(4, 1, 'Roberto', 'Reyes', 'roberto.reyes@gmail.com', '09204567890', 'Screening', DATE_SUB(NOW(), INTERVAL 3 DAY), '/uploads/resume_roberto.pdf'),
(5, 3, 'Ana', 'Mendoza', 'ana.mendoza@gmail.com', '09215678901', 'Screening', DATE_SUB(NOW(), INTERVAL 4 DAY), '/uploads/resume_ana.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- FOR INTERVIEW
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(6, 1, 'Carlo', 'Fernandez', 'carlo.fernandez@gmail.com', '09226789012', 'Interview', DATE_SUB(NOW(), INTERVAL 5 DAY), '/uploads/resume_carlo.pdf'),
(7, 4, 'Jose', 'Ramos', 'jose.ramos@gmail.com', '09237890123', 'Interview', DATE_SUB(NOW(), INTERVAL 6 DAY), '/uploads/resume_jose.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- ROAD TEST
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(8, 1, 'Miguel', 'Torres', 'miguel.torres@gmail.com', '09248901234', 'Road_Test', DATE_SUB(NOW(), INTERVAL 7 DAY), '/uploads/resume_miguel.pdf'),
(9, 1, 'Antonio', 'Cruz', 'antonio.cruz@gmail.com', '09259012345', 'Road_Test', DATE_SUB(NOW(), INTERVAL 8 DAY), '/uploads/resume_antonio.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- OFFER SENT
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(10, 1, 'Ricardo', 'Villanueva', 'ricardo.villanueva@gmail.com', '09260123456', 'Offer_Sent', DATE_SUB(NOW(), INTERVAL 10 DAY), '/uploads/resume_ricardo.pdf'),
(11, 3, 'Patricia', 'Luna', 'patricia.luna@gmail.com', '09271234567', 'Offer_Sent', DATE_SUB(NOW(), INTERVAL 12 DAY), '/uploads/resume_patricia.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- HIRED
INSERT INTO job_applications (id, job_posting_id, first_name, last_name, email, phone, status, applied_date, resume_path) VALUES
(12, 1, 'Fernando', 'Aquino', 'fernando.aquino@gmail.com', '09282345678', 'Hired', DATE_SUB(NOW(), INTERVAL 14 DAY), '/uploads/resume_fernando.pdf'),
(13, 2, 'Lucia', 'Morales', 'lucia.morales@gmail.com', '09293456789', 'Hired', DATE_SUB(NOW(), INTERVAL 15 DAY), '/uploads/resume_lucia.pdf')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- Add some audit log entries for demo
INSERT INTO audit_logs (user_id, user_email, action, module, record_id, details, ip_address, created_at) VALUES
(1, 'hr@slatefreight.com', 'EDIT', 'job_applications', 10, 'Changed Status of Applicant #10 to "Offer_Sent"', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'hr@slatefreight.com', 'CREATE', 'user_accounts', 5, 'Created user ricardo.villanueva@slatefreight.com', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'manager@slatefreight.com', 'VIEW', 'employee_documents', 3, 'Viewed 201 File of Pedro Penduko', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE action = VALUES(action);

-- Verify the data
SELECT 'Job Applications by Status:' AS info;
SELECT status, COUNT(*) as count FROM job_applications GROUP BY status;

SELECT 'Sample Applicants Created Successfully!' AS result;
